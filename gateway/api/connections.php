<?php
require dirname(__DIR__) . '/bootstrap.php';
gateway_require_token();

$cfg = gateway_config();
$path = $cfg['connections_file'];
$connections = [];

if (is_readable($path)) {
    $raw = file_get_contents($path);
    $decoded = json_decode((string)$raw, true);
    if (is_array($decoded)) {
        $connections = $decoded;
    }
}

gateway_json([
    'ok' => true,
    'connections' => $connections,
]);
