<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Modules\AuditAccess\Services\JsonAuditLogger;
use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;
use App\Modules\AppRegistry\Controllers\IndexController as AppRegistryController;
use App\Modules\DocsRegistry\Controllers\IndexController as DocsRegistryController;
use App\Modules\Portal\Controllers\HomeController as PortalController;
use App\Modules\PublicSite\Controllers\LandingController as PublicSiteController;
use App\Modules\WidgetRegistry\Controllers\DirectoryController as WidgetRegistryController;
use App\Support\StagingViewRenderer;
use RuntimeException;

final class StagingKernel
{
    private array $routes;
    private array $compatibility;
    private array $contractAliases;

    public function __construct(
        private readonly StagingViewRenderer $renderer = new StagingViewRenderer(),
        private readonly JsonAuditLogger $auditLogger = new JsonAuditLogger(),
        private readonly LegacyGatewayAuthBridge $auth = new LegacyGatewayAuthBridge(),
    ) {
        $this->routes = require dirname(__DIR__) . '/Config/Routes.php';
        $this->compatibility = require dirname(__DIR__) . '/Config/Compatibility.php';
        $routeContracts = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/config/route-contracts.json'), true);
        $this->contractAliases = is_array($routeContracts['legacyAliases'] ?? null) ? $routeContracts['legacyAliases'] : [];
    }

    public function handle(string $method, string $path, array $server = [], array $post = []): array
    {
        $query = $this->queryParams($path);
        $normalizedPath = $this->normalizePath($path);
        $resolvedPath = $this->resolvePath($normalizedPath, strtoupper($method));
        $routeKey = strtoupper($method) . ' ' . $resolvedPath;
        $definition = $this->routes[$routeKey] ?? null;
        if (!is_array($definition)) {
            $widgetRouteState = $this->resolveWidgetRoute(strtoupper($method), $resolvedPath);
            if (is_array($widgetRouteState)) {
                $response = [
                    'status' => 200,
                    'headers' => ['Content-Type' => 'text/html; charset=UTF-8'],
                    'body' => isset($widgetRouteState['rawHtml'])
                        ? (string) $widgetRouteState['rawHtml']
                        : $this->renderer->render((string) ($widgetRouteState['view'] ?? ''), (array) ($widgetRouteState['data'] ?? [])),
                ];
                $this->audit('widget-route', strtoupper($method), $normalizedPath, $resolvedPath, $response['status']);
                return $response;
            }

            $aliasTarget = $this->contractAliasTarget($normalizedPath);
            if (strtoupper($method) === 'GET' && is_string($aliasTarget) && $aliasTarget !== '') {
                $response = [
                    'status' => 302,
                    'headers' => ['Location' => $aliasTarget],
                    'body' => '',
                ];
                $this->audit('alias-redirect', strtoupper($method), $normalizedPath, $aliasTarget, $response['status'], $aliasTarget);
                return $response;
            }

            $response = [
                'status' => 404,
                'headers' => ['Content-Type' => 'text/plain; charset=UTF-8'],
                'body' => 'Route not found',
            ];
            $this->audit('not-found', strtoupper($method), $normalizedPath, $resolvedPath, $response['status']);
            return $response;
        }

        $controller = $this->makeController((string) ($definition['controller'] ?? ''));
        $action = (string) ($definition['method'] ?? 'index');

        if (!method_exists($controller, $action)) {
            throw new RuntimeException('Controller action not found: ' . $routeKey);
        }

        $state = match ((string) ($definition['controller'] ?? '') . '::' . $action) {
            'PublicSite::index', 'PublicSite::authenticateInline' => $controller->{$action}($server, $post),
            'Portal::index' => $controller->{$action}($resolvedPath),
            'Portal::launch', 'AppRegistry::launch' => $controller->{$action}($query),
            default => $controller->{$action}(),
        };

        if (isset($state['redirect'])) {
            $response = [
                'status' => (int) ($state['status'] ?? 302),
                'headers' => ['Location' => (string) $state['redirect']],
                'body' => '',
            ];
            $this->audit(
                (string) (($state['audit']['event'] ?? 'redirect')),
                strtoupper($method),
                $normalizedPath,
                $resolvedPath,
                $response['status'],
                (string) $state['redirect'],
                (array) ($state['audit'] ?? [])
            );
            return $response;
        }

        if (isset($state['rawHtml'])) {
            $response = [
                'status' => (int) ($state['status'] ?? 200),
                'headers' => ['Content-Type' => 'text/html; charset=UTF-8'],
                'body' => (string) $state['rawHtml'],
            ];
            $this->audit(
                (string) (($state['audit']['event'] ?? 'request')),
                strtoupper($method),
                $normalizedPath,
                $resolvedPath,
                $response['status'],
                '',
                (array) ($state['audit'] ?? [])
            );
            return $response;
        }

        $response = [
            'status' => (int) ($state['status'] ?? 200),
            'headers' => ['Content-Type' => 'text/html; charset=UTF-8'],
            'body' => $this->renderer->render((string) ($state['view'] ?? ''), (array) ($state['data'] ?? [])),
        ];
        $this->audit(
            (string) (($state['audit']['event'] ?? 'request')),
            strtoupper($method),
            $normalizedPath,
            $resolvedPath,
            $response['status'],
            '',
            (array) ($state['audit'] ?? [])
        );
        return $response;
    }

    private function makeController(string $name): object
    {
        return match ($name) {
            'PublicSite' => new PublicSiteController(),
            'Portal' => new PortalController(),
            'DocsRegistry' => new DocsRegistryController(),
            'WidgetRegistry' => new WidgetRegistryController(),
            'AppRegistry' => new AppRegistryController(),
            default => throw new RuntimeException('Unknown controller: ' . $name),
        };
    }

    private function resolveWidgetRoute(string $method, string $path): ?array
    {
        if ($method !== 'GET') {
            return null;
        }

        $controller = new WidgetRegistryController();
        $state = $controller->show($path);
        if ((string) ($state['view'] ?? '') === 'widgetregistry/not-found') {
            return null;
        }

        return $state;
    }

    private function normalizePath(string $path): string
    {
        $normalized = (string) parse_url($path, PHP_URL_PATH);
        if ($normalized === '') {
            return '/';
        }

        foreach ((array) ($this->compatibility['prefixes'] ?? []) as $prefix) {
            $prefix = rtrim((string) $prefix, '/');
            if ($prefix !== '' && ($normalized === $prefix || str_starts_with($normalized, $prefix . '/'))) {
                $normalized = substr($normalized, strlen($prefix));
                $normalized = $normalized === '' ? '/' : $normalized;
                break;
            }
        }

        return rtrim($normalized, '/') !== '' ? rtrim($normalized, '/') : '/';
    }

    private function resolvePath(string $normalizedPath, string $method): string
    {
        $localAliases = (array) ($this->compatibility['localAliases'] ?? []);
        $candidate = $localAliases[$normalizedPath] ?? $this->contractAliasTarget($normalizedPath);
        if (!is_string($candidate) || $candidate === '') {
            return $normalizedPath;
        }

        $routeKey = $method . ' ' . $candidate;
        return array_key_exists($routeKey, $this->routes) ? $candidate : $normalizedPath;
    }

    private function contractAliasTarget(string $path): ?string
    {
        foreach ($this->contractAliases as $alias) {
            if ((string) ($alias['from'] ?? '') === $path) {
                return (string) ($alias['to'] ?? '');
            }
        }

        return null;
    }

    private function audit(string $event, string $method, string $path, string $resolvedPath, int $status, string $redirectTo = '', array $context = []): void
    {
        $user = $this->auth->currentUser();
        $aliasTarget = $this->contractAliasTarget($path);
        $bodyBytes = 0;
        if ($event === 'request' || $event === 'widget-route') {
            $bodyBytes = 1;
        }
        $this->auditLogger->log([
            'event' => $event,
            'method' => $method,
            'path' => $path,
            'resolvedPath' => $resolvedPath,
            'status' => $status,
            'redirectTo' => $redirectTo,
            'aliasTarget' => $aliasTarget,
            'routeFamily' => $this->routeFamily($resolvedPath),
            'bodyState' => $bodyBytes > 0 ? 'rendered' : 'empty',
            'isLoggedIn' => $this->auth->isLoggedIn(),
            'user' => is_array($user) ? [
                'username' => (string) ($user['username'] ?? ''),
                'role' => (string) ($user['role'] ?? ''),
            ] : null,
        ] + $context);
    }

    private function routeFamily(string $path): string
    {
        return match (true) {
            $path === '/' => 'landing',
            $path === '/portal' => 'portal',
            $path === '/portal/launch' => 'portal-launch',
            $path === '/walkthrough' || str_starts_with($path, '/walkthrough/') => 'docs',
            $path === '/daftar-widget' || $path === '/widget-links' => 'widget-directory',
            $path === '/app-registry' => 'app-registry',
            $path === '/app-registry/launch' => 'app-launch',
            str_starts_with($path, '/lawangsewu/gateway/') => 'gateway',
            default => 'public-widget',
        };
    }

    private function queryParams(string $path): array
    {
        $query = (string) parse_url($path, PHP_URL_QUERY);
        if ($query === '') {
            return [];
        }

        parse_str($query, $params);
        return is_array($params) ? $params : [];
    }
}