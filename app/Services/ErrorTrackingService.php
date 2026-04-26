<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Error Tracking Service
 * 
 * Integrates with error tracking platforms (Sentry, Rollbar, etc):
 * - Captures exceptions and errors
 * - Tracks error patterns
 * - Provides error context and breadcrumbs
 * - Sends alerts for critical errors
 */
class ErrorTrackingService
{
    /**
     * Initialize error tracking (Sentry)
     */
    public static function initialize()
    {
        if (!config('observability.error_tracking.enabled')) {
            return;
        }

        // Sentry is initialized via Laravel 11's config/integrations
        // This service provides additional context tracking
    }

    /**
     * Capture exception with context
     */
    public static function captureException($exception, $context = [])
    {
        if (!config('observability.error_tracking.enabled')) {
            Log::error('Exception', array_merge([
                'error' => $exception->getMessage(),
                'exception_class' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ], $context));
            return;
        }

        try {
            // Send to Sentry if configured
            if (function_exists('sentry')) {
                $scope = \Sentry\captureException($exception);
                
                // Add context
                foreach ($context as $key => $value) {
                    \Sentry\setContext('custom', [$key => $value]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error sending to Sentry', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Capture error message (non-exception)
     */
    public static function captureMessage($message, $level = 'info', $context = [])
    {
        if (!config('observability.error_tracking.enabled')) {
            Log::log($level, $message, $context);
            return;
        }

        try {
            if (function_exists('sentry')) {
                \Sentry\captureMessage($message, \Sentry\Severity::levelFromString($level));
            }
        } catch (\Exception $e) {
            Log::error('Error sending message to Sentry', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add breadcrumb for debugging
     */
    public static function addBreadcrumb($message, $data = [], $level = 'info', $category = 'default')
    {
        if (!config('observability.error_tracking.enabled')) {
            return;
        }

        try {
            if (function_exists('sentry')) {
                \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                    \Sentry\Breadcrumb::LEVEL_DEBUG,
                    \Sentry\Breadcrumb::TYPE_DEFAULT,
                    $category,
                    $message,
                    $data
                ));
            }
        } catch (\Exception $e) {
            Log::debug('Error adding breadcrumb', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add user context to error reports
     */
    public static function setUserContext($user)
    {
        if (!config('observability.error_tracking.enabled')) {
            return;
        }

        try {
            if (function_exists('sentry')) {
                \Sentry\setUser([
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->name,
                    'ip_address' => request()->ip(),
                ]);
            }
        } catch (\Exception $e) {
            Log::debug('Error setting Sentry user', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Track error pattern
     */
    public static function trackErrorPattern($errorKey, $data = [])
    {
        $cacheKey = "error_pattern:{$errorKey}";
        $count = cache()->increment($cacheKey, 1, 3600); // 1 hour window

        if ($count > 10) {
            Log::critical('Error pattern detected', array_merge([
                'error_key' => $errorKey,
                'occurrence_count' => $count,
            ], $data));

            // Send alert
            self::alertCriticalErrorPattern($errorKey, $count, $data);
        }
    }

    /**
     * Alert on critical error patterns
     */
    private static function alertCriticalErrorPattern($errorKey, $count, $data = [])
    {
        // Send Slack notification
        // send_slack_alert("Critical Error Pattern", "Error: {$errorKey}, Count: {$count}");
    }

    /**
     * Track WA service errors
     */
    public static function trackWaError($operation, $error, $context = [])
    {
        self::captureMessage(
            "WA Operation Failed: {$operation}",
            'error',
            array_merge([
                'operation' => $operation,
                'error' => $error,
            ], $context)
        );

        self::trackErrorPattern("wa:{$operation}", $context);
    }

    /**
     * Track SIPP query errors
     */
    public static function trackSippError($query, $error, $context = [])
    {
        self::captureMessage(
            "SIPP Query Failed",
            'error',
            array_merge([
                'query' => substr($query, 0, 100),
                'error' => $error,
            ], $context)
        );

        self::trackErrorPattern('sipp_query_failed', $context);
    }

    /**
     * Track database transaction errors
     */
    public static function trackDatabaseError($table, $operation, $error, $context = [])
    {
        self::captureMessage(
            "Database Operation Failed: {$operation}",
            'error',
            array_merge([
                'table' => $table,
                'operation' => $operation,
                'error' => $error,
            ], $context)
        );

        self::trackErrorPattern("db:{$table}:{$operation}", $context);
    }

    /**
     * Get error statistics
     */
    public static function getErrorStatistics($timeWindow = 3600)
    {
        return [
            'total_errors' => cache()->get('error_count', 0),
            'error_rate' => self::calculateErrorRate(),
            'most_common_errors' => self::getMostCommonErrors(10),
            'affected_users' => self::getAffectedUsers(),
        ];
    }

    /**
     * Calculate error rate
     */
    private static function calculateErrorRate()
    {
        $errors = cache()->get('error_count', 0);
        $requests = cache()->get('request_count', 0);

        if ($requests === 0) {
            return 0;
        }

        return round(($errors / $requests) * 100, 2);
    }

    /**
     * Get most common errors
     */
    private static function getMostCommonErrors($limit = 10)
    {
        // Would query from Sentry API or local error log
        return [];
    }

    /**
     * Get affected users count
     */
    private static function getAffectedUsers()
    {
        // Would query from Sentry API
        return null;
    }
}
