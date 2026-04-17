<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected string $isolatedStoragePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->isolatedStoragePath = base_path('storage/framework/testing-sandbox/' . str_replace('\\', '-', static::class) . '-' . bin2hex(random_bytes(4)));

        File::ensureDirectoryExists($this->isolatedStoragePath, 0755, true);
        $this->app->useStoragePath($this->isolatedStoragePath);
    }

    protected function tearDown(): void
    {
        if (isset($this->isolatedStoragePath) && File::isDirectory($this->isolatedStoragePath)) {
            File::deleteDirectory($this->isolatedStoragePath);
        }

        parent::tearDown();
    }
}
