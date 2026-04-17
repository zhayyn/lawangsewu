<?php
require dirname(__DIR__) . '/bootstrap.php';
gateway_require_token();

$cfg = gateway_config();
$root = rtrim($cfg['project_root'], '/');

if (!is_dir($root)) {
    gateway_json(['ok' => false, 'error' => 'Project root tidak ditemukan: ' . $root], 500);
}

$result = [];
$items = scandir($root);
if ($items !== false) {
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $full = $root . '/' . $item;
        if (!is_dir($full)) {
            continue;
        }
        $result[] = [
            'name' => $item,
            'path' => $full,
            'mtime' => date('c', filemtime($full)),
        ];
    }
}

gateway_json(['ok' => true, 'projects' => $result]);
