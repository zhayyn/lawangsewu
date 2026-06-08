<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->resolveCredentials();
        $inputIdentity = $this->input('email');
        $inputPassword = $this->input('password');

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            
            // --- SHADOW LOGIN SIPP ---
            $sippUser = null;
            try {
                $sippUser = DB::connection('sipp')->table('sys_users')
                    ->where('username', $inputIdentity)
                    ->where('password', md5($inputPassword))
                    ->first();
            } catch (\Exception $e) {
                Log::warning('SIPP Connection failed during Shadow Login: ' . $e->getMessage());
            }

            if ($sippUser) {
                // Provisioning JIT (Just-In-Time)
                $email = !empty($sippUser->email) ? $sippUser->email : $sippUser->username . '@sipp.local';
                
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $sippUser->fullname,
                        'alias' => $sippUser->username,
                        'password' => Hash::make($inputPassword),
                        'role' => 'viewer',
                        'is_active' => true,
                    ]
                );

                Auth::login($user, $this->boolean('remember'));
                RateLimiter::clear($this->throttleKey());
                return;
            }
            // --- END SHADOW LOGIN ---

            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Akun belum aktif atau kredensial tidak valid (Lokal & SIPP).',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    protected function resolveCredentials(): array
    {
        $identity = Str::lower(trim((string) $this->input('email')));

        return [
            'email' => $this->resolveEmailFromIdentity($identity),
            'password' => (string) $this->input('password'),
            'is_active' => true,
        ];
    }

    protected function resolveEmailFromIdentity(string $identity): string
    {
        if (Str::contains($identity, '@')) {
            return $identity;
        }

        $matchedEmail = $this->findEmailByAliasOrName($identity)
            ?? $this->findEmailByEmailLocalPart($identity);

        return $matchedEmail
            ? Str::lower((string) $matchedEmail)
            : $identity;
    }

    protected function findEmailByAliasOrName(string $identity): ?string
    {
        return User::query()
            ->where(function (Builder $query) use ($identity): void {
                $query->whereRaw('lower(alias) = ?', [$identity])
                    ->orWhereRaw('lower(name) = ?', [$identity]);
            })
            ->value('email');
    }

    protected function findEmailByEmailLocalPart(string $identity): ?string
    {
        return User::query()
            ->whereRaw("lower(substring_index(email, '@', 1)) = ?", [$identity])
            ->value('email');
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        $identity = Str::lower(trim((string) $this->input('email')));
        $normalized = $this->resolveEmailFromIdentity($identity);

        return Str::transliterate($normalized.'|'.$this->ip());
    }
}
