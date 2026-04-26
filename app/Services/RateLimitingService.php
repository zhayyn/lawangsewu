<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Rate Limiting Service
 * 
 * Advanced rate limiting strategies:
 * - Per phone number limiting (WA)
 * - Per IP limiting
 * - Sliding window algorithm
 * - Dynamic limits based on user role
 */
class RateLimitingService
{
    /**
     * Check if WA webhook request is allowed
     * 
     * Rate limit per phone number + IP
     */
    public static function isWaWebhookAllowed($phoneNumber, $ipAddress)
    {
        // Limit: 100 requests per minute per phone number
        $phoneKey = "wa_webhook:phone:{$phoneNumber}";
        
        if (RateLimiter::tooManyAttempts($phoneKey, 100)) {
            Log::warning('WA webhook rate limit exceeded (phone)', [
                'phone' => self::maskPhone($phoneNumber),
                'ip' => $ipAddress,
                'key' => $phoneKey,
            ]);
            return false;
        }

        // Limit: 500 requests per minute per IP
        $ipKey = "wa_webhook:ip:{$ipAddress}";
        
        if (RateLimiter::tooManyAttempts($ipKey, 500)) {
            Log::warning('WA webhook rate limit exceeded (IP)', [
                'ip' => $ipAddress,
                'key' => $ipKey,
            ]);
            return false;
        }

        // Record the attempt
        RateLimiter::hit($phoneKey, 60); // 60 second window
        RateLimiter::hit($ipKey, 60);

        return true;
    }

    /**
     * Check if API request is allowed (per user)
     */
    public static function isApiAllowed($userId, $endpoint = null)
    {
        $key = "api:user:{$userId}";
        if ($endpoint) {
            $key .= ":{$endpoint}";
        }

        $limit = 100; // Default 100 requests per minute

        // Dynamic limits based on role
        if (auth()->check()) {
            $limit = match (auth()->user()->role) {
                'admin' => 1000,
                'operator' => 200,
                'useradmin' => 150,
                'viewer' => 100,
                default => 60,
            };
        }

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            Log::warning('API rate limit exceeded', [
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'limit' => $limit,
            ]);
            return false;
        }

        RateLimiter::hit($key, 60);
        return true;
    }

    /**
     * Check if admin action is allowed (very restricted)
     * 
     * Admin actions like user deletion, role changes should be heavily throttled
     */
    public static function isAdminActionAllowed($userId, $action)
    {
        $key = "admin:action:{$userId}:{$action}";
        $limit = 10; // 10 admin actions per hour

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            Log::warning('Admin action rate limit exceeded', [
                'user_id' => $userId,
                'action' => $action,
            ]);
            return false;
        }

        RateLimiter::hit($key, 3600); // 1 hour window
        return true;
    }

    /**
     * Check if broadcast action is allowed
     * 
     * Prevent spam in real-time broadcasts
     */
    public static function isBroadcastAllowed($userId, $channel = null)
    {
        $key = "broadcast:user:{$userId}";
        if ($channel) {
            $key .= ":{$channel}";
        }

        $limit = 50; // 50 broadcasts per minute

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            Log::warning('Broadcast rate limit exceeded', [
                'user_id' => $userId,
                'channel' => $channel,
            ]);
            return false;
        }

        RateLimiter::hit($key, 60);
        return true;
    }

    /**
     * Get rate limit info for response headers
     */
    public static function getRateLimitInfo($userId, $endpoint = null)
    {
        $key = "api:user:{$userId}";
        if ($endpoint) {
            $key .= ":{$endpoint}";
        }

        $limit = 100;
        if (auth()->check()) {
            $limit = match (auth()->user()->role) {
                'admin' => 1000,
                'operator' => 200,
                'useradmin' => 150,
                'viewer' => 100,
                default => 60,
            };
        }

        return [
            'limit' => $limit,
            'remaining' => max(0, $limit - RateLimiter::attempts($key)),
            'reset_at' => now()->addMinute()->timestamp,
        ];
    }

    /**
     * Mask phone number for logging
     */
    private static function maskPhone($phone)
    {
        return substr($phone, 0, 4) . '****' . substr($phone, -4);
    }

    /**
     * Log rate limiting metrics
     */
    public static function logMetrics()
    {
        Log::info('Rate limiting metrics', [
            'timestamp' => now()->toIso8601String(),
            'metric_collection_time' => 'periodic',
        ]);
    }

    /**
     * Reset rate limits (for testing)
     */
    public static function resetLimits($userId = null, $pattern = null)
    {
        if ($userId) {
            cache()->forget("api:user:{$userId}");
        }
        if ($pattern) {
            cache()->tags(['rate_limit'])->flush();
        }
    }
}
