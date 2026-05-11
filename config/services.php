<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'analytics_id' => env('GOOGLE_ANALYTICS_ID'),
    ],

    // ── PAK PP — Vertex AI (Gemini 2.5 Flash) ──────────────────────
    'vertex_ai' => [
        'project_id'                  => env('VERTEX_AI_PROJECT_ID'),
        'location'                    => env('VERTEX_AI_LOCATION', 'us-central1'),
        'model'                       => env('VERTEX_AI_MODEL', 'gemini-2.5-flash-preview-05-20'),
        'service_account_json_base64' => env('VERTEX_AI_SERVICE_ACCOUNT_JSON_BASE64'),
    ],

];
