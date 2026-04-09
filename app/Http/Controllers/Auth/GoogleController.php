<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\GoogleAccessAllowlist;
use App\Models\User;
use App\Services\GoogleIdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGoogle(): RedirectResponse
    {
        Log::info('Google OAuth redirect initiated', [
            'redirect_uri' => config('services.google.redirect'),
            'app_url' => config('app.url'),
            'session_domain' => config('session.domain'),
            'session_secure' => config('session.secure'),
        ]);

        return Socialite::driver('google')
            ->setScopes(['openid', 'email', 'profile'])
            ->with([
                'response_mode' => 'form_post',
                'access_type' => 'online',
            ])
            ->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            Log::info('Google OAuth callback received', [
                'method' => $request->method(),
                'has_code' => $request->filled('code'),
                'has_error' => $request->filled('error'),
                'has_state' => $request->filled('state'),
                'redirect_uri' => config('services.google.redirect'),
            ]);

            if ($request->filled('error')) {
                $oauthError = (string) $request->string('error');
                $oauthDescription = (string) $request->string('error_description');

                Log::warning('Google OAuth callback returned error', [
                    'error' => $oauthError,
                    'description' => $oauthDescription,
                ]);

                return redirect()
                    ->route('access.pending', ['reason' => 'error'])
                    ->with('error', 'Login Google dibatalkan atau gagal di sisi Google. Silakan coba lagi.');
            }

            if (! $request->filled('code')) {
                Log::warning('Google OAuth callback missing code parameter', [
                    'method' => $request->method(),
                    'query_keys' => array_keys($request->query()),
                    'request_keys' => array_keys($request->request->all()),
                ]);

                return redirect()
                    ->route('login')
                    ->with('error', 'Gagal menyelesaikan login Google (kode otorisasi tidak ditemukan). Silakan coba lagi.');
            }

            try {
                $googleUser = Socialite::driver('google')->user();
            } catch (InvalidStateException $stateException) {
                Log::warning('Google callback invalid state, retrying stateless', [
                    'message' => $stateException->getMessage(),
                    'type' => get_class($stateException),
                ]);

                $googleUser = Socialite::driver('google')->stateless()->user();
            }

            if (! $googleUser || ! $googleUser->email) {
                throw new \Exception('Gagal mendapatkan data email dari Google.');
            }

            return $this->completeGoogleLogin([
                'id' => (string) $googleUser->id,
                'email' => (string) $googleUser->email,
                'name' => $googleUser->name ?: $googleUser->nickname,
                'avatar' => $googleUser->avatar,
            ]);
        } catch (\Exception $e) {
            Log::error('Google Auth Failure', [
                'message' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return redirect()
                ->route('access.pending', ['reason' => 'error'])
                ->with('error', 'Otentikasi Google gagal: ' . $e->getMessage());
        }
    }

    public function handleGoogleCredential(Request $request, GoogleIdTokenVerifier $verifier): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validate([
                'credential' => ['required', 'string'],
            ]);

            $identity = $verifier->verify($validated['credential']);

            Log::info('Google ID token received', [
                'email' => $identity['email'],
                'google_id' => $identity['id'],
                'method' => $request->method(),
            ]);

            $redirect = $this->completeGoogleLogin($identity);

            return response()->json([
                'redirect' => $redirect->getTargetUrl(),
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Token Google tidak lengkap.',
                'errors' => $exception->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Google Credential Failure', [
                'message' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500),
            ]);

            return response()->json([
                'message' => 'Otentikasi Google gagal: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * @param  array{id: string, email: string, name: ?string, avatar: ?string}  $identity
     */
    private function completeGoogleLogin(array $identity): RedirectResponse
    {
        $isNewGoogleAccount = false;
        $superAdminEmail = strtolower((string) config('auth.super_admin_email'));
        $email = strtolower(trim((string) ($identity['email'] ?? '')));
        $googleId = trim((string) ($identity['id'] ?? ''));
        $derivedName = trim((string) ($identity['name'] ?? ''));
        $avatar = filled($identity['avatar'] ?? null) ? (string) $identity['avatar'] : null;

        if ($email === '') {
            throw new \RuntimeException('Gagal mendapatkan data email dari Google.');
        }

        if ($googleId === '') {
            throw new \RuntimeException('Gagal mendapatkan ID akun Google.');
        }

        if ($derivedName === '') {
            $derivedName = explode('@', $email)[0];
        }

        $user = User::whereRaw('lower(email) = ?', [$email])->first();
        $isConfiguredSuperAdmin = $superAdminEmail !== ''
            && $email === $superAdminEmail;

        $allowlistEntry = GoogleAccessAllowlist::query()->where('email', $email)->first();
        $isAllowlisted = $isConfiguredSuperAdmin || (bool) $allowlistEntry;

        if (! $user && ! $isAllowlisted) {
            Log::info('Google login blocked by allowlist', ['email' => $email]);

            return redirect()
                ->route('access.pending', ['reason' => 'not-allowed'])
                ->with('flash', [
                    'message' => 'Akun Google Anda belum terdaftar dalam daftar akses. Silakan hubungi admin untuk mendaftarkan email Anda.',
                    'email' => config('auth.super_admin_email'),
                ]);
        }

        if (! $user) {
            $isNewGoogleAccount = true;
            $user = User::create([
                'email' => $email,
                'name' => $derivedName,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'password' => Hash::make(Str::random(24)),
                'is_active' => $isConfiguredSuperAdmin || (bool) $allowlistEntry?->auto_activate,
                'role' => $isConfiguredSuperAdmin ? 'admin' : 'viewer',
                'is_superadmin' => $isConfiguredSuperAdmin,
            ]);
            $user->email_verified_at = now();
            $user->save();

            Log::info('New Google account registered', [
                'email' => $user->email,
                'google_id' => $user->google_id,
            ]);
        } else {
            $user->fill([
                'google_id' => $user->google_id ?: $googleId,
                'avatar' => $avatar ?: $user->avatar,
                'is_active' => $isConfiguredSuperAdmin
                    ? true
                    : ($allowlistEntry?->auto_activate ? true : $user->is_active),
                'role' => $isConfiguredSuperAdmin ? 'admin' : $user->role,
                'is_superadmin' => $isConfiguredSuperAdmin ? true : $user->is_superadmin,
            ]);

            if (! $user->email_verified_at) {
                $user->email_verified_at = now();
            }

            if ($user->isDirty()) {
                $user->save();
            }
        }

        if (! $user->is_active) {
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
    }
}
