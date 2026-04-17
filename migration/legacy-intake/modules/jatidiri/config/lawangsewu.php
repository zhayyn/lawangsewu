<?php

return [
    'portal_url' => env('LAWANGSEWU_PORTAL_URL', 'http://127.0.0.1/lawangsewu/gateway/index'),
    'portal_login_url' => env('LAWANGSEWU_PORTAL_LOGIN_URL', 'http://127.0.0.1/lawangsewu/gateway/login'),
    'sso_mode' => env('LAWANGSEWU_SSO_MODE', 'planned'),
    'sso_shared_secret' => env('LAWANGSEWU_SSO_SHARED_SECRET', ''),
    'sso_ttl_seconds' => (int) env('LAWANGSEWU_SSO_TTL_SECONDS', 300),
    'default_user_role' => env('LAWANGSEWU_DEFAULT_USER_ROLE', 'viewer'),
    'sikep' => [
        'base_url' => env('SIKEP_BASE_URL', ''),
        'employee_endpoint' => env('SIKEP_EMPLOYEE_ENDPOINT', '/pegawai'),
        'token' => env('SIKEP_API_TOKEN', ''),
        'timeout_seconds' => (int) env('SIKEP_TIMEOUT_SECONDS', 15),
        'verify_tls' => env('SIKEP_VERIFY_TLS', true),
        'portal_login_url' => env('SIKEP_PORTAL_LOGIN_URL', ''),
        'portal_employee_export_url' => env('SIKEP_PORTAL_EMPLOYEE_EXPORT_URL', ''),
        'portal_username' => env('SIKEP_PORTAL_USERNAME', ''),
        'portal_password' => env('SIKEP_PORTAL_PASSWORD', ''),
        'portal_username_field' => env('SIKEP_PORTAL_USERNAME_FIELD', 'username'),
        'portal_password_field' => env('SIKEP_PORTAL_PASSWORD_FIELD', 'password'),
        'portal_csrf_field' => env('SIKEP_PORTAL_CSRF_FIELD', '_csrf'),
    ],
];
