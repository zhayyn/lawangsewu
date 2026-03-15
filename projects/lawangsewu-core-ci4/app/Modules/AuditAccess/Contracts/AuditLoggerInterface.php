<?php

declare(strict_types=1);

namespace App\Modules\AuditAccess\Contracts;

interface AuditLoggerInterface
{
    public function log(array $entry): void;
}