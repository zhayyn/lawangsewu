<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OAuth 2.0 Service
 * 
 * Implements OAuth 2.0 authentication:
 * - Google OAuth integration
 * - Authorization code flow
 * - Token refresh management
 * - User provisioning
 */
class OAuth2Service
{
    /**
     * Generate authorization URL for Google OAuth
     */
    public static function getGoogleAuthorizationUrl($state = null)
    {
        $state = $state ?? Str::random(32);
        
        $params = [
            'client_id' => config('security.auth.oauth.google.client_id'),
            'redirect_uri' => config('security.auth.oauth.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public static function exchangeCodeForToken($code)
    {
        try {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('security.auth.oauth.google.client_id'),
                'client_secret' => config('security.auth.oauth.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('security.auth.oauth.google.redirect_uri'),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('OAuth token exchange failed', [
                'status' => $response->status(),
                'error' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('OAuth token exchange exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get user info from Google
     */
    public static function getUserInfo($accessToken)
    {
        try {
            $response = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v1/userinfo?alt=json');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch user info', [
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch user info', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Verify ID token (JWT)
     */
    public static function verifyIdToken($idToken)
    {
        try {
            // Verify JWT signature with Google's public keys
            // This is a simplified version - use a JWT library in production
            
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                return null;
            }

            // Decode payload (second part)
            $payload = json_decode(base64_decode($parts[1]), true);

            // Verify issuer
            if ($payload['iss'] !== 'https://accounts.google.com') {
                return null;
            }

            // Verify audience (client ID)
            if ($payload['aud'] !== config('security.auth.oauth.google.client_id')) {
                return null;
            }

            // Verify expiration
            if ($payload['exp'] < time()) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            Log::error('ID token verification failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Refresh access token
     */
    public static function refreshAccessToken($refreshToken)
    {
        try {
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('security.auth.oauth.google.client_id'),
                'client_secret' => config('security.auth.oauth.google.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Token refresh failed', ['status' => $response->status()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Token refresh exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Revoke refresh token
     */
    public static function revokeRefreshToken($refreshToken)
    {
        try {
            $response = Http::post('https://oauth2.googleapis.com/revoke', [
                'token' => $refreshToken,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Token revocation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Provision or update user from OAuth info
     */
    public static function provisionUser($googleUserInfo, $idToken = null)
    {
        try {
            // Find or create user
            $user = \App\Models\User::firstOrCreate(
                ['google_id' => $googleUserInfo['id']],
                [
                    'email' => $googleUserInfo['email'],
                    'name' => $googleUserInfo['name'],
                    'avatar_url' => $googleUserInfo['picture'] ?? null,
                    'role' => 'viewer', // Default role
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            // Update user info if different
            $user->update([
                'name' => $googleUserInfo['name'],
                'avatar_url' => $googleUserInfo['picture'] ?? $user->avatar_url,
            ]);

            Log::info('User provisioned from OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
                'google_id' => $googleUserInfo['id'],
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::error('User provisioning failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create API token for user (Laravel Passport/Sanctum)
     */
    public static function createApiToken($user, $deviceName = 'web')
    {
        try {
            // Using Laravel Sanctum
            $token = $user->createToken($deviceName);

            Log::info('API token created', [
                'user_id' => $user->id,
                'device' => $deviceName,
            ]);

            return $token->plainTextToken;
        } catch (\Exception $e) {
            Log::error('API token creation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Log OAuth error for security audit
     */
    public static function logOAuthError($error, $description = null)
    {
        Log::warning('OAuth error detected', [
            'error' => $error,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
