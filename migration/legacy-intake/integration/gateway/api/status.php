<?php
require dirname(__DIR__) . '/bootstrap.php';
gateway_require_token();

$cfg = gateway_config();
$latest = null;
$files = glob(rtrim($cfg['backup_root'], '/') . '/' . $cfg['backup_file_prefix'] . '*.tar.enc') ?: [];
if (!empty($files)) {
    rsort($files);
    $latestPath = $files[0];
    $latest = [
        'file' => basename($latestPath),
        'size' => filesize($latestPath),
        'mtime' => date('c', filemtime($latestPath)),
    ];
}

$cronTag = preg_quote($cfg['backup_cron_tag'], '/');
$cron = shell_exec("crontab -l 2>/dev/null | grep -E '" . $cronTag . "-(daily|offsite)-backup' || true");

gateway_json([
    'ok' => true,
    'app' => $cfg['app_name'],
    'time' => date('c'),
    'allow_commands' => $cfg['allow_commands'],
    'backup_latest' => $latest,
    'cron' => trim((string)$cron),
]);
