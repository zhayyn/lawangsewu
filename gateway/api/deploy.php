<?php
require dirname(__DIR__) . '/bootstrap.php';
gateway_require_token();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    gateway_json(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$cfg = gateway_config();
$payload = gateway_request_json();
$project = (string)($payload['project'] ?? '');
$target = (string)($payload['target'] ?? $project);

if (!gateway_safe_project_name($project) || !gateway_safe_project_name($target)) {
    gateway_json(['ok' => false, 'error' => 'Nama project/target tidak valid'], 400);
}

$sourceDir = rtrim($cfg['project_root'], '/') . '/' . $project;
$targetDir = rtrim($cfg['deploy_root'], '/') . '/' . $target;

if (!is_dir($sourceDir)) {
    gateway_json(['ok' => false, 'error' => 'Project tidak ditemukan: ' . $sourceDir], 404);
}

if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    gateway_json(['ok' => false, 'error' => 'Gagal membuat target dir: ' . $targetDir], 500);
}

$cmd = sprintf(
    'rsync -av --delete %s/ %s/',
    escapeshellarg($sourceDir),
    escapeshellarg($targetDir)
);

if (!$cfg['allow_commands']) {
    gateway_json([
        'ok' => true,
        'dry_run' => true,
        'message' => 'Command execution nonaktif. Set GATEWAY_ALLOW_COMMANDS=true di gateway/.env untuk aktifkan deploy.',
        'command' => $cmd,
    ]);
}

$output = [];
$code = 0;
exec($cmd . ' 2>&1', $output, $code);

if ($code !== 0) {
    gateway_json([
        'ok' => false,
        'error' => 'Deploy gagal',
        'command' => $cmd,
        'output' => $output,
    ], 500);
}

gateway_json([
    'ok' => true,
    'message' => 'Deploy berhasil',
    'project' => $project,
    'target' => $target,
    'target_dir' => $targetDir,
    'output' => $output,
]);
