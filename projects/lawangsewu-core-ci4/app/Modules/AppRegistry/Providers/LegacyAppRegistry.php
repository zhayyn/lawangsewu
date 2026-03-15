<?php

declare(strict_types=1);

namespace App\Modules\AppRegistry\Providers;

use App\Modules\AppRegistry\Contracts\AppRegistryInterface;
use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;

final class LegacyAppRegistry implements AppRegistryInterface
{
    public function __construct(
        private readonly LegacyGatewayAuthBridge $auth = new LegacyGatewayAuthBridge(),
    ) {
    }

    public function applications(): array
    {
        $isAdmin = in_array($this->auth->userRole(), ['superadmin', 'admin'], true);

        return array_values(array_filter([
            $this->application(
                'Portal Gateway',
                'gateway',
                $this->auth->appLauncherUrl('gateway'),
                'Pusat SSO, integrasi layanan, dan utilitas gateway Lawangsewu.',
                'core',
                ['user', 'admin', 'superadmin']
            ),
            $this->application(
                'WA Caraka Admin',
                'wa-caraka-admin',
                $this->auth->appLauncherUrl('wa-caraka-admin', 'dashboard'),
                'Launcher SSO ke dashboard admin WA Caraka.',
                'sibling',
                ['admin', 'superadmin']
            ),
            $this->application(
                'Dubes Prakom Ops',
                'dubes-prakom',
                $this->auth->appLauncherUrl('dubes-prakom'),
                'Pantauan runtime dan operasional Dubes Prakom.',
                'ops',
                ['admin', 'superadmin']
            ),
            $this->application(
                'Mas Satset AI',
                'mas-satset-ai',
                $this->auth->appLauncherUrl('mas-satset-ai'),
                'Laboratorium knowledge dan uji jawaban AI.',
                'ai',
                ['admin', 'superadmin']
            ),
            $isAdmin ? $this->application(
                'SSO Mapping',
                'sso-mapping',
                '/lawangsewu/gateway/sso-mapping',
                'Peta layanan SSO yang aktif di gateway.',
                'admin',
                ['admin', 'superadmin']
            ) : null,
            $this->application(
                'Swagger UI Internal',
                'swagger-ui',
                '/wa-caraka-admin/wa/docs/swagger',
                'Dokumentasi endpoint admin internal.',
                'reference',
                ['admin', 'superadmin']
            ),
        ]));
    }

    public function visibleApplications(): array
    {
        $role = $this->auth->userRole();
        if ($role === '') {
            $role = 'guest';
        }

        return array_values(array_filter(
            $this->applications(),
            static fn (array $app): bool => in_array($role, $app['roles'], true) || in_array('user', $app['roles'], true)
        ));
    }

    public function summary(): array
    {
        $apps = $this->applications();
        $visible = $this->visibleApplications();
        $siblingCount = count(array_filter($apps, static fn ($app) => (string) ($app['kind'] ?? '') === 'sibling'));

        return [
            'total' => count($apps),
            'visible' => count($visible),
            'siblingCount' => $siblingCount,
            'statusLabel' => $this->auth->ssoStatusLabel(),
        ];
    }

    private function application(string $name, string $key, string $path, string $description, string $kind, array $roles): array
    {
        return [
            'name' => $name,
            'key' => $key,
            'path' => $path,
            'description' => $description,
            'kind' => $kind,
            'roles' => $roles,
        ];
    }
}