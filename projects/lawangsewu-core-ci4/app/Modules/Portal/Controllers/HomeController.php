<?php

declare(strict_types=1);

namespace App\Modules\Portal\Controllers;

use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;
use App\Modules\Portal\Contracts\PortalRegistryInterface;
use App\Modules\Portal\Providers\LegacyPortalRegistry;
use App\Modules\Portal\Support\PortalViewModelBuilder;

final class HomeController
{
    private PortalViewModelBuilder $viewModelBuilder;
    private LegacyGatewayAuthBridge $auth;
    private PortalRegistryInterface $registry;

    public function __construct(?PortalViewModelBuilder $viewModelBuilder = null, ?LegacyGatewayAuthBridge $auth = null, ?PortalRegistryInterface $registry = null)
    {
        $this->viewModelBuilder = $viewModelBuilder ?? new PortalViewModelBuilder();
        $this->auth = $auth ?? new LegacyGatewayAuthBridge();
        $this->registry = $registry ?? new LegacyPortalRegistry();
    }

    public function index(string $requestedPath = '/portal'): array
    {
        if (!$this->auth->isLoggedIn()) {
            return [
                'redirect' => $this->auth->loginUrl($requestedPath),
            ];
        }

        return [
            'view' => 'portal/dashboard',
            'data' => $this->viewModelBuilder->build(),
        ];
    }

    public function launch(array $query): array
    {
        if (!$this->auth->isLoggedIn()) {
            return [
                'status' => 302,
                'redirect' => $this->auth->loginUrl('/portal'),
                'audit' => [
                    'event' => 'launch-auth-required',
                    'launchSource' => 'portal',
                    'launchTarget' => (string) ($query['path'] ?? ''),
                ],
            ];
        }

        $path = trim((string) ($query['path'] ?? ''));
        $item = $this->findPortalItemByPath($path);

        if ($item === null) {
            return [
                'status' => 302,
                'redirect' => '/portal',
                'audit' => [
                    'event' => 'launch-denied',
                    'launchSource' => 'portal',
                    'launchTarget' => $path,
                    'launchKind' => 'unknown',
                ],
            ];
        }

        return [
            'status' => 302,
            'redirect' => (string) ($item['path'] ?? '/portal'),
            'audit' => [
                'event' => 'launch-portal-item',
                'launchSource' => 'portal',
                'launchName' => (string) ($item['name'] ?? ''),
                'launchTarget' => (string) ($item['path'] ?? ''),
                'launchKind' => (string) ($item['kind'] ?? ''),
                'launchTag' => (string) ($item['tag'] ?? ''),
            ],
        ];
    }

    private function findPortalItemByPath(string $path): ?array
    {
        if ($path === '' || !$this->isInternalPath($path)) {
            return null;
        }

        foreach ($this->registry->allItems() as $item) {
            if ((string) ($item['path'] ?? '') === $path) {
                return $item;
            }
        }

        return null;
    }

    private function isInternalPath(string $path): bool
    {
        return str_starts_with($path, '/') && !str_starts_with($path, '//');
    }
}