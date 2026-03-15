<?php

declare(strict_types=1);

namespace App\Config;

final class App
{
    public string $basePath;
    public string $baseURL;
    public string $indexPage = '';
    public string $defaultLocale = 'id';
    public string $appName = 'lawangsewu-core-ci4-staging';

    public function __construct()
    {
        $this->basePath = dirname(__DIR__, 2);
        $this->baseURL = '/lawangsewu/projects/lawangsewu-core-ci4/public';
    }
}