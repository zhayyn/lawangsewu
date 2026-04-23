<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Comprehensive security configuration for Lawangsewu
    |
    */

    'cors' => [
        'enabled' => env('CORS_ENABLED', true),
        
        // Allowed origins for cross-origin requests
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 
            'http://localhost:3000,http://localhost:5173,https://lawangsewu.app'
        )),
        
        'default_origin' => env('CORS_DEFAULT_ORIGIN', 'https://lawangsewu.app'),
        
        // Allowed HTTP methods
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        
        // Allowed headers
        'allowed_headers' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'X-Trace-ID',
            'Accept',
            'Accept-Language',
        ],
        
        // Expose headers to client
        'exposed_headers' => [
            'Content-Type',
            'X-Trace-ID',
            'X-Response-Time-Ms',
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset',
        ],
        
        // Credentials allowed
        'supports_credentials' => true,
        
        // Max age for preflight cache
        'max_age' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Configuration
    |--------------------------------------------------------------------------
    */

    'encryption' => [
        // Session data encryption
        'session_encryption' => env('SESSION_ENCRYPT', true),
        
        // Encrypt sensitive database fields
        'field_encryption' => [
            'enabled' => env('FIELD_ENCRYPTION_ENABLED', true),
            'fields' => [
                'users.phone',
                'users.email',
                'wa_contacts.phone',
                'wa_messages.message_content',
            ],
        ],
        
        // API request/response encryption (if needed)
        'api_encryption' => [
            'enabled' => env('API_ENCRYPTION_ENABLED', false),
            'algorithm' => 'AES-256-CBC',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication & Authorization
    |--------------------------------------------------------------------------
    */

    'auth' => [
        // Session timeout
        'session_timeout_minutes' => (int) env('SESSION_TIMEOUT_MINUTES', 120),
        
        // Password policy
        'password_policy' => [
            'min_length' => 12,
            'require_uppercase' => true,
            'require_numbers' => true,
            'require_special_chars' => true,
            'expiration_days' => (int) env('PASSWORD_EXPIRATION_DAYS', 90),
        ],

        // OAuth 2.0 Configuration
        'oauth' => [
            'enabled' => env('OAUTH_ENABLED', true),
            'google' => [
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect_uri' => env('GOOGLE_REDIRECT_URI', 'https://lawangsewu.app/auth/google/callback'),
            ],
        ],

        // Two-factor authentication
        'mfa' => [
            'enabled' => env('MFA_ENABLED', true),
            'required_for_roles' => ['admin', 'useradmin'],
            'grace_period_days' => 7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    */

    'api' => [
        // API key validation
        'validate_api_key' => env('VALIDATE_API_KEY', true),
        
        // Request signature verification
        'verify_request_signature' => env('VERIFY_REQUEST_SIGNATURE', false),
        
        // Rate limiting
        'rate_limiting' => [
            'enabled' => env('RATE_LIMIT_ENABLED', true),
            'default_limit' => 100,
            'window_seconds' => 60,
        ],

        // IP whitelisting for sensitive endpoints
        'ip_whitelist' => [
            'enabled' => env('IP_WHITELIST_ENABLED', false),
            'endpoints' => [
                '/api/admin/*',
                '/api/user-management/*',
            ],
            'whitelist' => explode(',', env('IP_WHITELIST', '127.0.0.1')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Validation & Sanitization
    |--------------------------------------------------------------------------
    */

    'input' => [
        // XSS protection
        'xss_protection' => true,
        'allowed_html_tags' => ['b', 'i', 'u', 'strong', 'em', 'p', 'br', 'a'],
        
        // SQL injection protection (via parameterized queries)
        'sql_injection_protection' => true,
        
        // File upload validation
        'file_uploads' => [
            'enabled' => true,
            'max_file_size_mb' => (int) env('MAX_FILE_SIZE_MB', 10),
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'],
            'scan_for_malware' => env('SCAN_UPLOADS_FOR_MALWARE', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit & Compliance
    |--------------------------------------------------------------------------
    */

    'audit' => [
        // Enable audit logging
        'enabled' => true,
        
        // Log these actions
        'tracked_actions' => [
            'user.login',
            'user.logout',
            'user.created',
            'user.deleted',
            'user.role_changed',
            'data.created',
            'data.modified',
            'data.deleted',
            'admin.action',
        ],

        // Data retention policy
        'retention_days' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Headers & Security Policies
    |--------------------------------------------------------------------------
    */

    'headers' => [
        // Strict Transport Security (HTTPS only)
        'strict_transport_security' => [
            'enabled' => env('HTTPS_STRICT', true),
            'max_age' => 31536000, // 1 year
            'include_subdomains' => true,
        ],

        // Content Security Policy
        'content_security_policy' => [
            'enabled' => true,
            'default_src' => ["'self'"],
            'script_src' => ["'self'", "'unsafe-inline'"],
            'style_src' => ["'self'", "'unsafe-inline'"],
            'img_src' => ["'self'", 'data:', 'https:'],
        ],

        // X-Frame-Options (Clickjacking protection)
        'x_frame_options' => 'SAMEORIGIN',
        
        // X-Content-Type-Options
        'x_content_type_options' => 'nosniff',
        
        // Referrer-Policy
        'referrer_policy' => 'strict-origin-when-cross-origin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Security
    |--------------------------------------------------------------------------
    */

    'webhook' => [
        // Token-based auth
        'require_token' => true,
        
        // Signature verification
        'verify_signature' => true,
        'signature_algorithm' => 'sha256',
        
        // IP whitelist
        'verify_ip' => true,
        'trusted_ips' => explode(',', env('WEBHOOK_TRUSTED_IPS', '127.0.0.1')),
        
        // Rate limiting
        'rate_limit' => [
            'per_ip' => (int) env('WEBHOOK_RATE_LIMIT_IP', 500),
            'per_phone' => (int) env('WEBHOOK_RATE_LIMIT_PHONE', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Protection & GDPR
    |--------------------------------------------------------------------------
    */

    'data_protection' => [
        // GDPR compliance
        'gdpr_compliance' => env('GDPR_COMPLIANCE', true),
        
        // Data retention policies
        'retention_policies' => [
            'user_activity_logs' => (int) env('USER_ACTIVITY_LOG_RETENTION_DAYS', 90),
            'api_logs' => (int) env('API_LOG_RETENTION_DAYS', 30),
            'error_logs' => (int) env('ERROR_LOG_RETENTION_DAYS', 90),
            'audit_logs' => (int) env('AUDIT_LOG_RETENTION_DAYS', 365),
        ],
        
        // Personal data fields (for GDPR right to be forgotten)
        'personal_data_fields' => [
            'users.phone',
            'users.email',
            'visitors.phone',
            'wa_contacts.phone',
        ],
        
        // Data export format
        'export_format' => 'json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Penetration Testing & Security Testing
    |--------------------------------------------------------------------------
    */

    'testing' => [
        // Allow security testing endpoints (only in dev/staging)
        'enable_security_endpoints' => env('ENABLE_SECURITY_TESTING', false),
        
        // Allowed test types
        'allowed_tests' => [
            'sql_injection',
            'xss',
            'csrf',
            'command_injection',
        ],

        // Test result logging
        'log_test_results' => true,
        'alert_on_vulnerability' => true,
    ],
];
