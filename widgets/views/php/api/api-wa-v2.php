<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../system/portable_config.php';

$allowedPaths = ['/health', '/qr', '/send-text', '/restart', '/reconnect', '/disconnect', '/history', '/history/clear'];
$path = '/' . ltrim((string)($_GET['path'] ?? 'health'), '/');
if (!in_array($path, $allowedPaths, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Path tidak valid']);
    exit;
}

$cfg = lw_config();
$envToken = trim((string)($cfg['wa_v2_token'] ?? ''));
if ($envToken === '') {
    $envToken = trim((string)(getenv('LW_WA_V2_TOKEN') ?: ''));
}

if ($envToken === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'LW_WA_V2_TOKEN wajib di-set untuk akses WA v2']);
    exit;
}

$providedToken = trim((string)($_SERVER['HTTP_X_WA_V2_TOKEN'] ?? ''));
if ($providedToken === '') {
    $providedToken = trim((string)($_GET['token'] ?? ''));
}

if ($providedToken === '' || !hash_equals($envToken, $providedToken)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$targetBase = trim((string)($cfg['wa_v2_base'] ?? ''));
if ($targetBase === '') {
    $targetBase = trim((string)(getenv('LW_WA_V2_BASE') ?: 'http://127.0.0.1:8790'));
}
$targetBase = rtrim($targetBase, '/');
$url = $targetBase . $path;
$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if (!in_array($method, ['GET', 'POST'], true)) {
    $method = 'GET';
}

$postOnlyPaths = ['/send-text', '/restart', '/reconnect', '/disconnect', '/history/clear'];
if (in_array($path, $postOnlyPaths, true) && $method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method tidak diizinkan untuk path ini']);
    exit;
}

$body = null;
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $body = $raw !== false ? $raw : '';
}

$status = 502;
$response = false;
$error = '';

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($method === 'POST') {
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$body);
    }

    $response = curl_exec($ch);
    $error = (string)curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if (is_int($statusCode) && $statusCode > 0) {
        $status = $statusCode;
    }
    curl_close($ch);
}

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'Gagal terhubung ke WA v2 service',
        'detail' => $error,
    ]);
    exit;
}

http_response_code($status);
echo $response;
