<?php

declare(strict_types=1);

namespace App\Modules\Portal\Contracts;

interface PortalRegistryInterface
{
    public function sections(): array;

    public function allItems(): array;

    public function summary(): array;
}