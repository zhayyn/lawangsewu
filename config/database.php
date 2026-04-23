<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for database operations. This is
    | the connection which will be utilized unless another connection
    | is explicitly specified when you execute a query / statement.
    |
    */

    'default' => env('DB_CONNECTION', 'sqlite'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Below are all of the database connections defined for your application.
    | An example configuration is provided for each database system which
    | is supported by Laravel. You're free to add / remove connections.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'mariadb' => [
            'driver' => 'mariadb',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'wa_caraka' => [
            'driver' => env('WA_CARAKA_DB_DRIVER', env('DB_CONNECTION', 'mysql')),
            'url' => env('WA_CARAKA_DB_URL', env('DB_URL')),
            'host' => env('WA_CARAKA_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('WA_CARAKA_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('WA_CARAKA_DB_DATABASE', env('DB_DATABASE', 'laravel')),
            'username' => env('WA_CARAKA_DB_USERNAME', env('DB_USERNAME', 'root')),
            'password' => env('WA_CARAKA_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('WA_CARAKA_DB_SOCKET', env('DB_SOCKET', '')),
            'charset' => env('WA_CARAKA_DB_CHARSET', env('DB_CHARSET', 'utf8mb4')),
            'collation' => env('WA_CARAKA_DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci')),
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => filter_var(env('WA_CARAKA_DB_STRICT', env('DB_STRICT', true)), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('WA_CARAKA_DB_SSL_CA', env('MYSQL_ATTR_SSL_CA')),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => env('DB_CHARSET', 'utf8'),
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],


        // ─── SIPP (Sistem Informasi Penelusuran Perkara) ─────────────────────
        // Koneksi READ-ONLY independen ke server SIPP (192.168.88.10).
        // Digunakan oleh SippService untuk chatbot menu 7-11.
        //
        // Security hardening:
        //  - PDO::ATTR_EMULATE_PREPARES = false  → true parameterized queries
        //  - PDO::ATTR_TIMEOUT           = 3     → fail-fast on unreachable host
        //  - PDO::MYSQL_ATTR_FOUND_ROWS  = true  → correct row counts
        //  - No default fallback value for credentials (fail if env missing)
        'sipp' => [
            'driver'    => 'mysql',
            'host'      => env('SIPP_DB_HOST'),           // no default — must be set in .env
            'port'      => env('SIPP_DB_PORT', '3306'),
            'database'  => env('SIPP_DB_DATABASE', 'sipp'),
            'username'  => env('SIPP_DB_USERNAME'),       // no default — must be set in .env
            'password'  => env('SIPP_DB_PASSWORD'),       // no default — must be set in .env
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => false,  // SIPP schema may have non-strict SQL; don't break on it
            'options'   => extension_loaded('pdo_mysql') ? [
                // True parameterized queries — prevents any second-order SQL injection
                \PDO::ATTR_EMULATE_PREPARES          => false,
                // Fail fast: 3-second connection timeout (prevents blocking workers)
                \PDO::ATTR_TIMEOUT                   => 3,
                // Return correct affected/found row counts
                \PDO::MYSQL_ATTR_FOUND_ROWS           => true,
                // Disable multi-statement execution — prevents stacked query attacks
                \PDO::MYSQL_ATTR_MULTI_STATEMENTS     => false,
            ] : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run on the database.
    |
    */

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as Memcached. You may define your connection settings here.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
