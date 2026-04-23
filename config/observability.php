<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Observability Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for monitoring, logging, tracing, and metrics
    |
    */

    'enabled' => env('OBSERVABILITY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Distributed Tracing
    |--------------------------------------------------------------------------
    */

    'tracing' => [
        'enabled' => env('TRACING_ENABLED', true),
        'service_name' => env('TRACING_SERVICE_NAME', 'lawangsewu'),
        'environment' => env('TRACING_ENVIRONMENT', config('app.env')),
        
        // Jaeger configuration
        'jaeger' => [
            'host' => env('JAEGER_AGENT_HOST', 'localhost'),
            'port' => env('JAEGER_AGENT_PORT', 6831),
            'sampler' => [
                'type' => env('JAEGER_SAMPLER_TYPE', 'const'),
                'param' => (float) env('JAEGER_SAMPLER_PARAM', 1.0), // 1.0 = trace all
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prometheus Metrics
    |--------------------------------------------------------------------------
    */

    'prometheus' => [
        'enabled' => env('PROMETHEUS_ENABLED', true),
        'push_gateway_url' => env('PROMETHEUS_PUSH_GATEWAY_URL'),
        'scrape_interval_seconds' => (int) env('PROMETHEUS_SCRAPE_INTERVAL', 60),
        'export_path' => storage_path('prometheus'),
        
        // Metrics to collect
        'metrics' => [
            'http_requests' => true,
            'database_queries' => true,
            'queue_jobs' => true,
            'cache_operations' => true,
            'business_metrics' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Tracking (Sentry)
    |--------------------------------------------------------------------------
    */

    'error_tracking' => [
        'enabled' => env('SENTRY_ENABLED', false),
        'dsn' => env('SENTRY_LARAVEL_DSN'),
        'environment' => env('SENTRY_ENVIRONMENT', config('app.env')),
        'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
        'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Logging
    |--------------------------------------------------------------------------
    */

    'request_logging' => [
        'enabled' => env('REQUEST_LOGGING_ENABLED', true),
        'log_request_headers' => env('REQUEST_LOGGING_HEADERS', true),
        'log_request_body' => env('REQUEST_LOGGING_BODY', true),
        'log_response_body' => env('REQUEST_LOGGING_RESPONSE', false),
        'skip_paths' => [
            'health',
            'metrics',
            'ready',
            'live',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'slow_request_threshold_ms' => (int) env('SLOW_REQUEST_THRESHOLD_MS', 500),
        'slow_query_threshold_ms' => (int) env('SLOW_QUERY_THRESHOLD_MS', 1000),
        'slow_sipp_query_threshold_ms' => (int) env('SLOW_SIPP_QUERY_THRESHOLD_MS', 500),
        'alert_on_slow_requests' => env('ALERT_ON_SLOW_REQUESTS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check Configuration
    |--------------------------------------------------------------------------
    */

    'health_check' => [
        'enabled' => env('HEALTH_CHECK_ENABLED', true),
        'check_database' => env('HEALTH_CHECK_DATABASE', true),
        'check_cache' => env('HEALTH_CHECK_CACHE', true),
        'check_queue' => env('HEALTH_CHECK_QUEUE', true),
        'check_external_services' => env('HEALTH_CHECK_EXTERNAL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerting
    |--------------------------------------------------------------------------
    */

    'alerting' => [
        'enabled' => env('ALERTS_ENABLED', false),
        
        'channels' => [
            'slack' => [
                'enabled' => env('SLACK_ALERTS_ENABLED', false),
                'webhook_url' => env('SLACK_ALERT_WEBHOOK_URL'),
            ],
            'email' => [
                'enabled' => env('EMAIL_ALERTS_ENABLED', false),
                'recipients' => explode(',', env('ALERT_EMAIL_RECIPIENTS', 'admin@example.com')),
            ],
            'pagerduty' => [
                'enabled' => env('PAGERDUTY_ALERTS_ENABLED', false),
                'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
            ],
        ],

        'alert_conditions' => [
            'high_error_rate' => (float) env('ALERT_ERROR_RATE_THRESHOLD', 5.0), // 5%
            'queue_depth_threshold' => (int) env('ALERT_QUEUE_DEPTH', 1000),
            'database_connection_pool_threshold' => (float) env('ALERT_DB_POOL_THRESHOLD', 0.8), // 80%
            'response_time_threshold_ms' => (int) env('ALERT_RESPONSE_TIME_MS', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    */

    'retention' => [
        'metrics_retention_days' => (int) env('METRICS_RETENTION_DAYS', 30),
        'logs_retention_days' => (int) env('LOGS_RETENTION_DAYS', 30),
        'traces_retention_days' => (int) env('TRACES_RETENTION_DAYS', 7),
        'errors_retention_days' => (int) env('ERRORS_RETENTION_DAYS', 90),
    ],
];
