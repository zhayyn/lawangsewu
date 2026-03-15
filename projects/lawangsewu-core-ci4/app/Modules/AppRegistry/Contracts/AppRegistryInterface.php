<?php

declare(strict_types=1);

namespace App\Modules\AppRegistry\Contracts;

interface AppRegistryInterface
{
    public function applications(): array;

    public function visibleApplications(): array;

    public function summary(): array;
}