<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WA Caraka v2 Runtime Configuration
    |--------------------------------------------------------------------------
    |
    | WA Caraka is the WhatsApp messaging gateway integrated as a first-class
    | module inside Lawangsewu. SSO is handled by the Lawangsewu auth layer.
    | This config controls connection to the Node.js runtime process.
    |
    | Instance 'default' = Nomor kantor (server Proxmox / runtime utama)
    | Instance 'personal' = Nomor pribadi superadmin (runtime di WSL via Tailscale)
    |
    */

    // Base URL of the local WA runtime (Node.js, e.g. Baileys-based server)
    'base_url' => env('LW_WA_V2_BASE', 'http://127.0.0.1:8790'),

    // Fallback base URL if primary runtime cannot serve /internal/media/ (opsional)
    // Contoh: 'http://192.168.88.33:8790' atau URL alternatif
    'fallback_base_url' => env('LW_WA_V2_BASE_FALLBACK', null),

    // Static token for runtime authentication (leave empty if runtime has no auth)
    'token' => env('LW_WA_V2_TOKEN', ''),

    // HTTP timeout in seconds for all runtime calls
    'timeout' => env('LW_WA_V2_TIMEOUT', 20),

    // Maximum recipients for a single broadcast call
    'broadcast_limit' => env('LW_WA_BROADCAST_LIMIT', 50),

    // Whether to store all sent message logs locally in wa_caraka_logs table
    'logging_enabled' => env('LW_WA_LOGGING', true),

    // Number of recent log entries to show on the dashboard
    'log_display_limit' => 50,

    // Maximum outbound media payload accepted by UI/backend/runtime.
    'max_media_bytes' => (int) env('LW_WA_MAX_MEDIA_BYTES', 5 * 1024 * 1024),

    // Laravel queue name for outbound messages (must match queue:work --queue flag)
    'queue' => env('WA_QUEUE', 'default'),

    // Database connection for all WA Caraka tables. Set to "wa_caraka" to isolate
    // inbox/history-sync storage from the main Lawangsewu database.
    'database_connection' => env('WA_CARAKA_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),

    // Recommended runtime history-sync tuning for smoother initial device login.
    'history_sync_max_days' => (int) env('WA_HISTORY_SYNC_MAX_DAYS', 45),
    'history_sync_batch_size' => (int) env('WA_HISTORY_SYNC_BATCH_SIZE', 75),

    /*
    |--------------------------------------------------------------------------
    | Webhook Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for incoming webhooks from WA Runtime
    |
    */

    // Webhook token for request verification
    'webhook_token' => env('WA_WEBHOOK_TOKEN', ''),

    // Webhook secret for HMAC signature validation
    'webhook_secret' => env('WA_WEBHOOK_SECRET', ''),

    // IP addresses allowed to send webhooks (CIDR notation supported)
    'webhook_ip_whitelist' => explode(',', env('WA_WEBHOOK_IP_WHITELIST', '127.0.0.1,::1')),

    // Rate limiting per phone number (requests per minute)
    'webhook_rate_limit_phone' => (int) env('WA_WEBHOOK_RATE_LIMIT_PHONE', 100),

    // Rate limiting per IP (requests per minute)
    'webhook_rate_limit_ip' => (int) env('WA_WEBHOOK_RATE_LIMIT_IP', 500),

    /*
    |--------------------------------------------------------------------------
    | Personal Instance Configuration (Nomor Pribadi Superadmin)
    |--------------------------------------------------------------------------
    |
    | Instance ke-2 untuk nomor WA pribadi superadmin.
    | Runtime berjalan di WSL PC lokal yang terhubung via Tailscale.
    |
    */

    // Apakah instance personal aktif
    'personal_enabled' => (bool) env('WA_CARAKA_PERSONAL_ENABLED', false),

    // Base URL runtime personal (IP Tailscale WSL + port 8791)
    'personal_base_url' => env('LW_WA_V2_BASE_PERSONAL', 'http://127.0.0.1:8791'),

    // Token autentikasi runtime personal
    'personal_token' => env('LW_WA_V2_TOKEN_PERSONAL', ''),

    // HTTP timeout untuk runtime personal (detik) — sedikit lebih toleran karena via Tailscale
    'personal_timeout' => (int) env('LW_WA_V2_TIMEOUT_PERSONAL', 30),

    // Token webhook yang dikirim dari runtime personal ke Laravel
    'personal_webhook_token' => env('WA_WEBHOOK_TOKEN_PERSONAL', ''),

    // Label tampilan di UI
    'personal_label' => env('WA_CARAKA_PERSONAL_LABEL', 'WA Personal'),
];
