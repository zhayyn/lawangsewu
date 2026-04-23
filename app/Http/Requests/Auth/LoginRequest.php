<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->resolveCredentials();

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Akun belum aktif atau kredensial tidak valid.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Resolve the submitted identity into login credentials.
     *
     * Accepts either the real email or a short alias such as "ptsp1".
     *
     * @return array<string, mixed>
     */
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

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
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

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $identity = Str::lower(trim((string) $this->input('email')));
        $normalized = $this->resolveEmailFromIdentity($identity);

        return Str::transliterate($normalized.'|'.$this->ip());
    }
}
