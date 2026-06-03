<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIKEP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for syncing employee data from SIKEP.
    |
    */

    'base_url' => env('SIKEP_BASE_URL', 'https://sikep.mahkamahagung.go.id'),
    'employee_endpoint' => env('SIKEP_EMPLOYEE_ENDPOINT', '/pegawai'),
    'token' => env('SIKEP_API_TOKEN', ''),
    
    // Portal scraper configuration
    'portal_login_url' => env('SIKEP_PORTAL_LOGIN_URL', 'https://sikep.mahkamahagung.go.id/site/login'),
    'portal_employee_export_url' => env('SIKEP_PORTAL_EMPLOYEE_EXPORT_URL', 'https://sikep.mahkamahagung.go.id/laporan/bezetting/print'),
    'portal_username_field' => env('SIKEP_PORTAL_USERNAME_FIELD', 'username'),
    'portal_password_field' => env('SIKEP_PORTAL_PASSWORD_FIELD', 'password'),
    'portal_csrf_field' => env('SIKEP_PORTAL_CSRF_FIELD', '_csrf'),

    'verify_tls' => env('SIKEP_VERIFY_TLS', true),
    'timeout_seconds' => env('SIKEP_TIMEOUT_SECONDS', 15),
];
