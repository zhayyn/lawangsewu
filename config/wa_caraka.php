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
    */

    // Base URL of the local WA runtime (Node.js, e.g. Baileys-based server)
    'base_url' => env('LW_WA_V2_BASE', 'http://127.0.0.1:8790'),

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
];
