<?php

/**
 * SIPP Configuration
 * 
 * Configuration for SIPP (Sistem Informasi Pertanggungjawaban Perkara)
 * database connection and data synchronization.
 */

return [

    /**
     * Enabled
     * 
     * Whether SIPP integration is enabled.
     * When disabled, SIPP Hub and related features are unavailable.
     */
    'enabled' => env('SIPP_ENABLED', true),

    /**
     * Database Connection
     * 
     * SIPP database connection parameters.
     * Typically a separate MySQL database from the main application.
     */
    'database' => [
        'driver' => 'mysql',
        'host' => env('SIPP_DB_HOST', '192.168.88.10'),
        'port' => env('SIPP_DB_PORT', 3306),
        'database' => env('SIPP_DB_DATABASE', 'sipp'),
        'username' => env('SIPP_DB_USERNAME', 'admin'),
        'password' => env('SIPP_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
        
        // Connection timeout for SIPP server
        'options' => [
            \PDO::ATTR_TIMEOUT => env('SIPP_DB_TIMEOUT', 5),
        ],
    ],

    /**
     * Cache Configuration
     * 
     * How long to cache SIPP data before re-fetching.
     * Balances freshness with database load.
     */
    'cache' => [
        'enabled' => true,
        'ttl_minutes' => (int) env('SIPP_CACHE_TTL', 15),
        'driver' => env('CACHE_DRIVER', 'redis'),
    ],

    /**
     * Sync Configuration
     * 
     * Automatic synchronization of SIPP data.
     */
    'sync' => [
        'enabled' => true,
        'interval_minutes' => 15,
        'batch_size' => 100,
    ],

    /**
     * Data Tables
     * 
     * Main tables in SIPP database to sync.
     */
    'tables' => [
        'perkara' => 'perkara',
        'ecourt' => 'ecourt_entry',
        'hakim' => 'hakim',
    ],

    /**
     * Feature Flags
     * 
     * Enable/disable specific SIPP features.
     */
    'features' => [
        'hub_dashboard' => true,
        'cache_metrics' => true,
        'live_stats' => true,
        'historical_data' => true,
    ],

    /**
     * Logging
     * 
     * Logging for SIPP operations.
     */
    'logging' => [
        'enabled' => true,
        'channel' => 'single',
        'level' => env('LOG_LEVEL', 'info'),
        'log_slow_queries' => true,
        'slow_query_threshold_ms' => 1000,
    ],

    /**
     * Fallback Behavior
     * 
     * How to behave when SIPP is unavailable.
     */
    'fallback' => [
        'use_cache' => true,
        'cache_max_age_hours' => 24,
        'return_empty_on_error' => false,
        'log_failures' => true,
    ],

];
