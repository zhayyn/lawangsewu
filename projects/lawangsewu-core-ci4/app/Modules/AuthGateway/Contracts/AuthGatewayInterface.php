<?php

declare(strict_types=1);

namespace App\Modules\AuthGateway\Contracts;

interface AuthGatewayInterface
{
    public function currentUser(): ?array;

    public function isLoggedIn(): bool;

    public function userRole(): string;

    public function normalizeReturnPath(?string $path, string $default = ''): string;

    public function loginUrl(string $returnPath = ''): string;

    public function logoutUrl(): string;

    public function flashSet(string $key, string $message): void;

    public function flashGet(string $key): ?string;

    public function attemptLogin(string $username, string $password): array;

    public function logout(): void;

    public function appLauncherUrl(string $appKey, string $target = ''): string;

    public function ssoServiceMap(): array;

    public function ssoStatusLabel(): string;
}