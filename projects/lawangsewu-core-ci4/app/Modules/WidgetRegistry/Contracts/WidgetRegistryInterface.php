<?php

declare(strict_types=1);

namespace App\Modules\WidgetRegistry\Contracts;

interface WidgetRegistryInterface
{
    public function categories(): array;

    public function allItems(): array;

    public function findByPath(string $path): ?array;

    public function summary(): array;
}