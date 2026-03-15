<?php

declare(strict_types=1);

namespace App\Modules\AppRegistry\Controllers;

use App\Modules\AppRegistry\Contracts\AppRegistryInterface;
use App\Modules\AppRegistry\Providers\LegacyAppRegistry;

final class IndexController
{
    private AppRegistryInterface $registry;

    public function __construct(?AppRegistryInterface $registry = null)
    {
        $this->registry = $registry ?? new LegacyAppRegistry();
    }

    public function index(): array
    {
        return [
            'view' => 'appregistry/index',
            'data' => [
                'applications' => $this->registry->applications(),
                'visibleApplications' => $this->registry->visibleApplications(),
                'summary' => $this->registry->summary(),
            ],
        ];
    }

    public function launch(array $query): array
    {
        $key = trim((string) ($query['app'] ?? ''));
        $app = $this->findVisibleApplication($key);

        if ($app === null) {
            return [
                'status' => 302,
                'redirect' => '/app-registry',
                'audit' => [
                    'event' => 'launch-denied',
                    'launchSource' => 'app-registry',
                    'launchKey' => $key,
                    'launchTarget' => '',
                    'launchKind' => 'unknown',
                ],
            ];
        }

        return [
            'status' => 302,
            'redirect' => (string) ($app['path'] ?? '/app-registry'),
            'audit' => [
                'event' => 'launch-app',
                'launchSource' => 'app-registry',
                'launchKey' => (string) ($app['key'] ?? ''),
                'launchName' => (string) ($app['name'] ?? ''),
                'launchTarget' => (string) ($app['path'] ?? ''),
                'launchKind' => (string) ($app['kind'] ?? ''),
            ],
        ];
    }

    private function findVisibleApplication(string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        foreach ($this->registry->visibleApplications() as $app) {
            if ((string) ($app['key'] ?? '') === $key) {
                return $app;
            }
        }

        return null;
    }
}