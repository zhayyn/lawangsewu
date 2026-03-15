<?php

declare(strict_types=1);

namespace App\Modules\AuthGateway\Services;

use App\Modules\AuthGateway\Contracts\AuthGatewayInterface;
use LogicException;

final class LegacyGatewayAuthBridge implements AuthGatewayInterface
{
    private bool $legacyBooted = false;

    public function currentUser(): ?array
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_auth_user')) {
            $user = gateway_auth_user();
            return is_array($user) ? $user : null;
        }

        $user = $_SESSION['gateway_user'] ?? null;
        return is_array($user) ? $user : null;
    }

    public function isLoggedIn(): bool
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_is_logged_in')) {
            return gateway_is_logged_in();
        }

        return $this->currentUser() !== null;
    }

    public function userRole(): string
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_user_role')) {
            return (string) gateway_user_role();
        }

        $user = $this->currentUser();
        return is_array($user) ? strtolower(trim((string) ($user['role'] ?? ''))) : '';
    }

    public function normalizeReturnPath(?string $path, string $default = ''): string
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_normalize_return_path')) {
            return gateway_normalize_return_path($path, $default);
        }

        $value = trim((string) $path);
        if ($value === '') {
            return $default;
        }

        if (!str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return $default;
        }

        return $value;
    }

    public function loginUrl(string $returnPath = ''): string
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_login_url_with_return')) {
            return gateway_login_url_with_return($returnPath);
        }

        $returnPath = $this->normalizeReturnPath($returnPath);
        if ($returnPath === '') {
            return '/lawangsewu/gateway/login';
        }

        return '/lawangsewu/gateway/login?return=' . rawurlencode($returnPath);
    }

    public function logoutUrl(): string
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_logout_url')) {
            return gateway_logout_url();
        }

        return '/lawangsewu/gateway/logout';
    }

    public function flashSet(string $key, string $message): void
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_flash_set')) {
            gateway_flash_set($key, $message);
            return;
        }

        $_SESSION['gateway_flash'][$key] = $message;
    }

    public function flashGet(string $key): ?string
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_flash_get')) {
            return gateway_flash_get($key);
        }

        $value = $_SESSION['gateway_flash'][$key] ?? null;
        unset($_SESSION['gateway_flash'][$key]);
        return is_string($value) ? $value : null;
    }

    public function attemptLogin(string $username, string $password): array
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_attempt_login')) {
            return gateway_attempt_login($username, $password);
        }

        throw new LogicException('Legacy gateway login function is not available.');
    }

    public function logout(): void
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_logout')) {
            gateway_logout();
            return;
        }

        unset($_SESSION['gateway_user'], $_SESSION['gateway_flash']);
    }

    public function appLauncherUrl(string $appKey, string $target = ''): string
    {
        $this->bootLegacyGateway();

        return match ($appKey) {
            'portal' => '/portal',
            'gateway' => function_exists('gateway_ui_url') ? gateway_ui_url('index') : '/lawangsewu/gateway/index',
            'mas-satset-ai' => function_exists('gateway_mas_satset_url') ? gateway_mas_satset_url() : '/lawangsewu/gateway/mas-satset-ai',
            'dubes-prakom' => function_exists('gateway_dubes_prakom_url') ? gateway_dubes_prakom_url() : '/lawangsewu/gateway/dubes-prakom',
            'wa-caraka-admin' => $this->legacyWaCarakaLauncher($target),
            default => '',
        };
    }

    public function ssoServiceMap(): array
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_sso_service_map')) {
            $map = gateway_sso_service_map();
            return is_array($map) ? $map : [];
        }

        return [];
    }

    public function ssoStatusLabel(): string
    {
        $this->bootLegacyGateway();

        if (function_exists('gateway_sso_status_label')) {
            return (string) gateway_sso_status_label();
        }

        return $this->isLoggedIn() ? 'Aktif dan siap dipakai' : 'Menunggu login portal';
    }

    private function bootLegacyGateway(): void
    {
        if ($this->legacyBooted) {
            return;
        }

        $legacyBootstrap = dirname(__DIR__, 6) . '/gateway/bootstrap.php';
        if (is_file($legacyBootstrap)) {
            require_once $legacyBootstrap;
        }

        $this->legacyBooted = true;
    }

    private function legacyWaCarakaLauncher(string $target): string
    {
        if (function_exists('gateway_wa_admin_sso_url')) {
            return gateway_wa_admin_sso_url($target !== '' ? $target : 'dashboard');
        }

        return '/wa-caraka-admin/index.php/sso-login' . ($target !== '' ? '?target=' . rawurlencode($target) : '');
    }
}