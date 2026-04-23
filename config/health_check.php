<?php

/**
 * Health Check Configuration
 * 
 * Configuration for application health monitoring and status endpoints.
 * Used to track system health and external service availability.
 */

return [

    /**
     * Enabled
     * 
     * Whether health check endpoints are enabled.
     */
    'enabled' => env('HEALTH_CHECK_ENABLED', true),

    /**
     * Endpoint Configuration
     */
    'endpoints' => [
        'health' => '/health',
        'ready' => '/ready',
        'live' => '/live',
    ],

    /**
     * Checks to Perform
     * 
     * Which system components to check.
     */
    'checks' => [
        'application' => [
            'enabled' => true,
            'timeout_ms' => 1000,
        ],
        'database' => [
            'enabled' => true,
            'timeout_ms' => 5000,
            'check_replicas' => false,
        ],
        'cache' => [
            'enabled' => true,
            'timeout_ms' => 3000,
        ],
        'queue' => [
            'enabled' => true,
            'timeout_ms' => 2000,
        ],
        'disk_space' => [
            'enabled' => true,
            'min_free_percent' => 5,
        ],
        'wa_runtime' => [
            'enabled' => true,
            'timeout_ms' => 5000,
            'optional' => true, // Non-critical for main operation
        ],
        'sipp_database' => [
            'enabled' => true,
            'timeout_ms' => 5000,
            'optional' => true, // Non-critical for main operation
        ],
    ],

    /**
     * Response Configuration
     * 
     * How to format health check responses.
     */
    'response' => [
        'format' => 'json', // 'json' or 'text'
        'include_details' => env('APP_DEBUG', false),
        'include_timestamp' => true,
    ],

    /**
     * Cache Configuration
     * 
     * How long to cache health check results.
     * Reduces load by not checking every request.
     */
    'cache' => [
        'enabled' => true,
        'ttl_seconds' => 30,
    ],

    /**
     * Status Codes
     * 
     * HTTP status codes for different health states.
     */
    'status_codes' => [
        'healthy' => 200,
        'degraded' => 206, // Partial content
        'unhealthy' => 503,
    ],

    /**
     * Logging
     */
    'logging' => [
        'enabled' => true,
        'channel' => 'single',
        'log_unhealthy' => true,
        'log_degraded' => false,
    ],

    /**
     * Alerting
     * 
     * Alert configuration for health failures.
     */
    'alerting' => [
        'enabled' => false,
        'channels' => ['mail', 'slack'],
        'notification_threshold' => 3, // Alert after 3 consecutive failures
    ],

];
