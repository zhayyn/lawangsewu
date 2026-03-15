<?php

declare(strict_types=1);

namespace App\Modules\DocsRegistry\Contracts;

interface DocsRegistryInterface
{
    public function sections(): array;

    public function allDocuments(): array;

    public function summary(): array;
}