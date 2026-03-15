<?php

declare(strict_types=1);

require_once __DIR__ . '/Contracts/AuditLoggerInterface.php';
require_once __DIR__ . '/Services/JsonAuditLogger.php';

use App\Modules\AuditAccess\Services\JsonAuditLogger;

$logFile = dirname(__DIR__, 3) . '/writable/logs/audit-access-smoke.jsonl';
@unlink($logFile);

$logger = new JsonAuditLogger($logFile);
$logger->log([
    'event' => 'request',
    'method' => 'GET',
    'path' => '/walkthrough',
    'status' => 200,
]);

$lines = is_file($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
echo 'lines=' . count($lines) . PHP_EOL;
echo 'path=' . $logFile . PHP_EOL;