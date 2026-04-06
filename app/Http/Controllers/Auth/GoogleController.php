<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->setScopes(['openid', 'email', 'profile'])
            ->with([
                'response_mode' => 'form_post',
                'include_granted_scopes' => 'false',
                'access_type' => 'online',
            ])
            ->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $isNewGoogleAccount = false;
            $googleUser = null;
            $superAdminEmail = strtolower((string) config('auth.super_admin_email'));

            try {
                $googleUser = Socialite::driver('google')->user();
            } catch (InvalidStateException $stateException) {
                Log::warning('Google callback invalid state, retrying stateless', [
                    'message' => $stateException->getMessage(),
                    'type' => get_class($stateException),
                ]);
                $googleUser = Socialite::driver('google')->stateless()->user();
            }

            if (!$googleUser || !$googleUser->email) {
                throw new \Exception('Gagal mendapatkan data email dari Google.');
            }
            
            $user = User::where('email', $googleUser->email)->first();
            $isConfiguredSuperAdmin = $superAdminEmail !== ''
                && strtolower((string) $googleUser->email) === $superAdminEmail;

            if (!$user) {
                $isNewGoogleAccount = true;
                $user = User::create([
                    'email' => $googleUser->email,
                    'name' => $googleUser->name ?? $googleUser->nickname ?? explode('@', $googleUser->email)[0],
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(Str::random(24)),
                    'is_active' => $isConfiguredSuperAdmin,
                    'role' => $isConfiguredSuperAdmin ? 'admin' : 'viewer',
                    'is_superadmin' => $isConfiguredSuperAdmin,
                ]);

                Log::info('New Google account registered', [
                    'email' => $user->email,
                    'google_id' => $user->google_id,
                ]);
            } else {
                $user->fill([
                    'google_id' => $user->google_id ?: $googleUser->id,
                    'avatar' => $googleUser->avatar ?: $user->avatar,
                    'is_active' => $isConfiguredSuperAdmin ? true : $user->is_active,
                    'role' => $isConfiguredSuperAdmin ? 'admin' : $user->role,
                    'is_superadmin' => $isConfiguredSuperAdmin ? true : $user->is_superadmin,
                    'email_verified_at' => $isConfiguredSuperAdmin
                        ? ($user->email_verified_at ?? now())
                        : $user->email_verified_at,
                ]);

                if ($user->isDirty()) {
                    $user->save();
                }
            }

            if (!$user->is_active) {
                Log::info('Login attempt for inactive account', ['email' => $user->email]);

                return redirect()
                    ->route('access.pending', ['reason' => $isNewGoogleAccount ? 'unregistered' : 'pending'])
                    ->with('flash', [
                        'message' => $isNewGoogleAccount
                            ? 'Akun Google Anda telah dicatat. Admin akan mengaktifkan akses Anda segera.'
                            : 'Akun Anda sedang menunggu persetujuan dari admin.',
                        'email' => config('auth.super_admin_email'),
                    ]);
            }

            Auth::login($user, true);

            Log::info('User logged in via Google', ['id' => $user->id, 'email' => $user->email]);

            return redirect()->intended(route('dashboard'));
            
        } catch (\Exception $e) {
            Log::error('Google Auth Failure', [
                'message' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);

            return redirect()
                ->route('access.pending', ['reason' => 'error'])
                ->with('error', 'Otentikasi Google gagal: ' . $e->getMessage());
        }
    }
}
