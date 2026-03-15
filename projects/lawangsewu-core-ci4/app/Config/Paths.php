<?php

declare(strict_types=1);

namespace App\Config;

final class Paths
{
    public string $rootDirectory;
    public string $appDirectory;
    public string $publicDirectory;
    public string $writableDirectory;

    public function __construct()
    {
        $this->rootDirectory = dirname(__DIR__, 2);
        $this->appDirectory = $this->rootDirectory . '/app';
        $this->publicDirectory = $this->rootDirectory . '/public';
        $this->writableDirectory = $this->rootDirectory . '/writable';
    }
}