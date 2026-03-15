<?php

declare(strict_types=1);

namespace App\Modules\PublicSite\Contracts;

interface LandingPageInterface
{
    public function buildState(array $server, array $post = []): array;
}