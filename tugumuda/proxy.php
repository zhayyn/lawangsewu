<?php

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$path = trim((string)($_GET['path'] ?? '/lumpiapasar/api/monitor'));
if ($path === '' || $path[0] !== '/') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Path harus diawali /']);
    exit;
}

if (!in_array($path, $config['allowed_paths'], true)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Path tidak diizinkan. Tambahkan ke allowed_paths di config.php',
    ]);
    exit;
}

$baseUrl = rtrim((string)$config['base_url'], '/');
$url = $baseUrl . $path;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => (int)$config['timeout'],
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => array_filter([
        'Accept: application/json',
        !empty($config['token']) ? 'Authorization: Bearer ' . $config['token'] : null,
    ]),
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'Gagal menghubungi server 10',
        'detail' => $curlError,
    ]);
    exit;
}

$decoded = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo json_encode([
        'ok' => $statusCode >= 200 && $statusCode < 300,
        'status' => $statusCode,
        'source' => $url,
        'data' => $decoded,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => $statusCode >= 200 && $statusCode < 300,
    'status' => $statusCode,
    'source' => $url,
    'raw' => $response,
], JSON_UNESCAPED_UNICODE);
