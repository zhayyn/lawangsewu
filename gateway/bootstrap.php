<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('lawangsewu_gateway_session');
    session_start();
}

function gateway_load_env(string $filePath): array
{
    $values = [];
    if (!is_readable($filePath)) {
        return $values;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");
        $values[$key] = $value;
    }

    return $values;
}

function gateway_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $baseDir = dirname(__DIR__);
    $env = gateway_load_env(__DIR__ . '/.env');

    $config = [
        'app_name' => $env['GATEWAY_APP_NAME'] ?? 'Lawangsewu Gateway',
        'base_path' => rtrim($env['GATEWAY_BASE_PATH'] ?? '/lawangsewu/gateway', '/'),
        'api_token' => $env['GATEWAY_API_TOKEN'] ?? '',
        'allow_commands' => (($env['GATEWAY_ALLOW_COMMANDS'] ?? 'false') === 'true'),
        'project_root' => $env['GATEWAY_PROJECT_ROOT'] ?? ($baseDir . '/projects'),
        'deploy_root' => $env['GATEWAY_DEPLOY_ROOT'] ?? '/var/www/html',
        'backup_root' => $env['GATEWAY_BACKUP_ROOT'] ?? '/var/backups/lawangsewu',
        'backup_file_prefix' => $env['GATEWAY_BACKUP_FILE_PREFIX'] ?? 'lawangsewu_',
        'backup_cron_tag' => $env['GATEWAY_BACKUP_CRON_TAG'] ?? 'lawangsewu',
        'connections_file' => __DIR__ . '/data/connections.json',
        'wa_env_file' => $baseDir . '/wa-caraka/.env',
    ];

    return $config;
}

function gateway_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function gateway_request_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function gateway_get_header_token(): string
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (str_starts_with($auth, 'Bearer ')) {
        return trim(substr($auth, 7));
    }
    return (string)($_GET['token'] ?? '');
}

function gateway_require_token(): void
{
    $cfg = gateway_config();
    if ($cfg['api_token'] === '') {
        gateway_json([
            'ok' => false,
            'error' => 'Token API belum diset. Isi gateway/.env pada GATEWAY_API_TOKEN',
        ], 500);
    }

    $token = gateway_get_header_token();
    if (!hash_equals($cfg['api_token'], $token)) {
        gateway_json(['ok' => false, 'error' => 'Unauthorized'], 401);
    }
}

function gateway_safe_project_name(string $name): bool
{
    return preg_match('/^[a-zA-Z0-9_-]+$/', $name) === 1;
}

function gateway_ui_url(string $path = ''): string
{
    $basePath = gateway_config()['base_path'];
    $trimmed = ltrim($path, '/');
    return $trimmed === '' ? $basePath . '/index' : $basePath . '/' . $trimmed;
}

function gateway_login_url(): string
{
    return gateway_ui_url('login');
}

function gateway_logout_url(): string
{
    return gateway_ui_url('logout');
}

function gateway_flash_set(string $key, string $message): void
{
    $_SESSION['gateway_flash'][$key] = $message;
}

function gateway_flash_get(string $key): ?string
{
    $value = $_SESSION['gateway_flash'][$key] ?? null;
    unset($_SESSION['gateway_flash'][$key]);
    return is_string($value) ? $value : null;
}

function gateway_auth_user(): ?array
{
    $user = $_SESSION['gateway_user'] ?? null;
    return is_array($user) ? $user : null;
}

function gateway_is_logged_in(): bool
{
    return gateway_auth_user() !== null;
}

function gateway_require_login(): void
{
    if (gateway_is_logged_in()) {
        return;
    }

    header('Location: ' . gateway_login_url());
    exit;
}

function gateway_logout(): void
{
    unset($_SESSION['gateway_user']);
}

function gateway_wa_env(): array
{
    static $values = null;
    if ($values !== null) {
        return $values;
    }

    $values = gateway_load_env(gateway_config()['wa_env_file']);
    return $values;
}

function gateway_admin_pdo(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $env = gateway_wa_env();
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = (int) ($env['DB_PORT'] ?? 3306);
    $dbName = $env['DB_NAME'] ?? '';
    $user = $env['DB_USER'] ?? '';
    $password = $env['DB_PASSWORD'] ?? '';

    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbName),
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $pdo;
}

function gateway_attempt_login(string $username, string $password): array
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return ['ok' => false, 'message' => 'Username dan password wajib diisi.'];
    }

    try {
        $stmt = gateway_admin_pdo()->prepare('SELECT id, username, full_name, role, is_active, password_hash FROM admin_users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Login gateway belum dapat memeriksa database admin.'];
    }

    if (!is_array($user) || (int) ($user['is_active'] ?? 0) !== 1) {
        return ['ok' => false, 'message' => 'Username atau password salah.'];
    }

    if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
        return ['ok' => false, 'message' => 'Username atau password salah.'];
    }

    $_SESSION['gateway_user'] = [
        'id' => (int) $user['id'],
        'username' => (string) $user['username'],
        'full_name' => (string) $user['full_name'],
        'role' => (string) $user['role'],
        'login_at' => date('c'),
    ];

    return ['ok' => true, 'user' => $_SESSION['gateway_user']];
}
