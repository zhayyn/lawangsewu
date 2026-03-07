<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/portable_config.php';

function s10_param(string $key, $default = null)
{
    if (array_key_exists($key, $_POST)) {
        return $_POST[$key];
    }
    if (array_key_exists($key, $_GET)) {
        return $_GET[$key];
    }
    return $default;
}

function s10_log(array $config, array $payload): void
{
    $logFile = (string)($config['server10_log_file'] ?? '');
    if ($logFile === '') {
        return;
    }

    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $payload['ts'] = date('c');
    @file_put_contents($logFile, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function s10_http(array $config, string $url, string $method, array $forwardPost, int $timeout): array
{
    $body = false;
    $curlError = '';
    $statusCode = 502;

    if (function_exists('curl_init')) {
        $headers = ['Accept: application/json'];
        $token = trim((string)($config['server10_token'] ?? ''));
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($forwardPost);
        }

        curl_setopt_array($ch, $curlOptions);
        $body = curl_exec($ch);
        $curlError = (string)curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $contextHeaders = "Accept: application/json\r\n";
        $contextBody = null;
        if ($method === 'POST') {
            $contextHeaders .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $contextBody = http_build_query($forwardPost);
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $contextHeaders,
                'content' => $contextBody,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if (is_array($http_response_header ?? null)) {
            foreach ($http_response_header as $line) {
                if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $line, $match)) {
                    $statusCode = (int)$match[1];
                    break;
                }
            }
        }
    }

    return [
        'body' => $body,
        'status' => $statusCode,
        'error' => $curlError,
    ];
}

if (!in_array(($_SERVER['REQUEST_METHOD'] ?? 'GET'), ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$config = lw_config();
$allowedPaths = $config['server10_allowed_paths'] ?? ['/api/data'];
$presets = [
    'panjar_wilayah' => [
        'path' => '/lumpiapasar/panjar/_panjar_data_wilayah.php',
        'method' => 'POST',
        'post' => [
            'jenis' => 'kota',
            'id_provinces' => 'JAWA TENGAH',
        ],
    ],
    'modul_panjar_perkara_ghaib' => [
        'path' => '/lumpiapasar/panjar/_cerai_proses.php',
        'method' => 'POST',
        'post' => [
            'nama_p' => 'PENGGUGAT',
            'nama_t' => 'TERGUGAT',
            'satker_code' => '3322',
            'nilai' => '0',
            'alamat' => 'SEMARAMG',
            'ghoib' => '1',
        ],
    ],
    'panjar_hitung' => [
        'path' => '/lumpiapasar/panjar/hitung_panjar.php',
        'method' => 'GET',
    ],
];

$mode = strtolower(trim((string)s10_param('mode', '')));
if ($mode === 'health') {
    $baseUrl = rtrim((string)($config['server10_base_url'] ?? 'http://192.168.88.10'), '/');
    $check = s10_http($config, $baseUrl . '/', 'GET', [], 6);
    $ok = $check['body'] !== false && $check['status'] > 0 && $check['status'] < 500;
    http_response_code($ok ? 200 : 502);
    echo json_encode([
        'ok' => $ok,
        'status' => $check['status'],
        'base' => $baseUrl,
        'bridge' => 'lawangsewu-server10',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($mode === 'capabilities') {
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'bridge' => 'lawangsewu-server10',
        'base' => rtrim((string)($config['server10_base_url'] ?? 'http://192.168.88.10'), '/'),
        'allowed' => $allowedPaths,
        'presets' => array_keys($presets),
        'supports' => ['GET', 'POST'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$method = strtoupper(trim((string)s10_param('method', $requestMethod)));
if (!in_array($method, ['GET', 'POST'], true)) {
    $method = $requestMethod;
}

$source = strtolower(trim((string)s10_param('source', '')));
$path = trim((string)s10_param('path', $allowedPaths[0]));

if ($source !== '' && isset($presets[$source])) {
    $path = $presets[$source]['path'];
    $method = strtoupper((string)$presets[$source]['method']);
}

$allowedPathsEffective = $allowedPaths;
if ($source !== '' && isset($presets[$source])) {
    $presetPath = (string)$presets[$source]['path'];
    if ($presetPath !== '' && !in_array($presetPath, $allowedPathsEffective, true)) {
        $allowedPathsEffective[] = $presetPath;
    }
}

if ($path === '' || !str_starts_with($path, '/')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Path harus diawali /'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($path, $allowedPathsEffective, true)) {
    http_response_code(403);
    echo json_encode([
        'ok' => false,
        'error' => 'Path tidak diizinkan',
        'allowed' => $allowedPathsEffective,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


$forwardGet = $_GET;
unset($forwardGet['path'], $forwardGet['method'], $forwardGet['source'], $forwardGet['mode']);

$forwardPost = $_POST;
unset($forwardPost['path'], $forwardPost['method'], $forwardPost['source'], $forwardPost['mode']);

if ($source !== '' && isset($presets[$source]['post']) && $method === 'POST') {
    $forwardPost = array_merge($presets[$source]['post'], $forwardPost);
}

$query = http_build_query($forwardGet);
$baseUrl = rtrim((string)($config['server10_base_url'] ?? 'http://192.168.88.10'), '/');
$url = $baseUrl . $path . ($query !== '' ? ('?' . $query) : '');

$timeout = (int)($config['server10_timeout'] ?? 15);
if ($timeout < 3) {
    $timeout = 3;
}
if ($timeout > 60) {
    $timeout = 60;
}

$body = false;
$curlError = '';
$statusCode = 502;

$attempts = max(1, min(4, (int)($config['server10_retries'] ?? 2)));
$response = ['body' => false, 'status' => 502, 'error' => ''];
for ($i = 1; $i <= $attempts; $i++) {
    $response = s10_http($config, $url, $method, $forwardPost, $timeout);
    if ($response['body'] !== false && (int)$response['status'] > 0 && (int)$response['status'] < 500) {
        break;
    }
    usleep(120000);
}

$body = $response['body'];
$statusCode = (int)$response['status'];
$curlError = (string)$response['error'];

if ($body === false) {
    s10_log($config, [
        'event' => 'request_failed',
        'url' => $url,
        'method' => $method,
        'path' => $path,
        'status' => $statusCode,
        'detail' => $curlError,
    ]);

    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'Gagal menghubungi server 10',
        'detail' => $curlError,
        'source' => $url,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$decoded = json_decode((string)$body, true);
$isJson = json_last_error() === JSON_ERROR_NONE;

s10_log($config, [
    'event' => 'request_done',
    'url' => $url,
    'method' => $method,
    'path' => $path,
    'status' => $statusCode,
    'source' => $source,
]);

http_response_code($statusCode > 0 ? $statusCode : 200);
echo json_encode([
    'ok' => $statusCode >= 200 && $statusCode < 300,
    'status' => $statusCode,
    'source' => $url,
    'bridge' => 'lawangsewu-server10',
    'sourcePreset' => $source !== '' ? $source : null,
    'method' => $method,
    'path' => $path,
    'allowed' => $allowedPathsEffective,
    'data' => $isJson ? $decoded : null,
    'raw' => $isJson ? null : (string)$body,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
