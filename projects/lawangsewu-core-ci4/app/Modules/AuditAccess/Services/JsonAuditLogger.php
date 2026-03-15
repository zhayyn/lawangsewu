<?php

declare(strict_types=1);

namespace App\Modules\AuditAccess\Services;

use App\Modules\AuditAccess\Contracts\AuditLoggerInterface;

final class JsonAuditLogger implements AuditLoggerInterface
{
    public function __construct(
        private readonly string $logFile = '',
    ) {
    }

    public function log(array $entry): void
    {
        $target = $this->logFile !== ''
            ? $this->logFile
            : dirname(__DIR__, 4) . '/writable/logs/audit-access.jsonl';

        $directory = dirname($target);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $payload = $entry + [
            'timestamp' => gmdate('c'),
        ];

        file_put_contents($target, json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}