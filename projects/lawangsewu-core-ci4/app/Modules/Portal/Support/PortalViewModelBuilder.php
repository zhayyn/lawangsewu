<?php

declare(strict_types=1);

namespace App\Modules\Portal\Support;

use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;
use App\Modules\Portal\Contracts\PortalRegistryInterface;
use App\Modules\Portal\Providers\LegacyPortalRegistry;

final class PortalViewModelBuilder
{
    private PortalRegistryInterface $registry;
    private LegacyGatewayAuthBridge $auth;

    public function __construct(?PortalRegistryInterface $registry = null, ?LegacyGatewayAuthBridge $auth = null)
    {
        $this->registry = $registry ?? new LegacyPortalRegistry();
        $this->auth = $auth ?? new LegacyGatewayAuthBridge();
    }

    public function build(): array
    {
        $user = $this->auth->currentUser() ?? [];
        $userName = $this->displayName($user);
        $sections = $this->registry->sections();
        $allItems = $this->registry->allItems();

        return [
            'gatewayUser' => $user,
            'gatewayUserName' => $userName,
            'gatewayUserRole' => strtoupper($this->auth->userRole() !== '' ? $this->auth->userRole() : 'user'),
            'gatewayUserInitials' => $this->initials($userName),
            'sections' => $sections,
            'summary' => $this->registry->summary(),
            'favoriteLeadItems' => array_slice($allItems, 0, 4),
        ];
    }

    public function cardSymbol(array $item): array
    {
        $name = strtolower((string) ($item['name'] ?? ''));
        $tag = strtolower((string) ($item['tag'] ?? ''));

        return match (true) {
            str_contains($name, 'mapping') || str_contains($name, 'gateway') => ['symbol' => '🔒', 'class' => 'symbol-lock'],
            str_contains($name, 'walkthrough') || str_contains($tag, 'dokumen') => ['symbol' => '✦', 'class' => 'symbol-star'],
            str_contains($name, 'swagger') || str_contains($tag, 'api') => ['symbol' => '⬢', 'class' => 'symbol-cube'],
            str_contains($name, 'wa caraka') || str_contains($tag, 'aplikasi') => ['symbol' => '◉', 'class' => 'symbol-orb'],
            str_contains($name, 'mas satset') || str_contains($tag, 'ai') => ['symbol' => '✺', 'class' => 'symbol-flare'],
            str_contains($tag, 'security') => ['symbol' => '🛡', 'class' => 'symbol-shield'],
            str_contains($tag, 'data') || str_contains($tag, 'bridge') => ['symbol' => '✦', 'class' => 'symbol-star'],
            str_contains($tag, 'publik') || str_contains($tag, 'embed') || str_contains($tag, 'sidang') => ['symbol' => '✶', 'class' => 'symbol-sun'],
            default => ['symbol' => '✦', 'class' => 'symbol-star'],
        };
    }

    public function filterGroup(string $sectionTitle, array $item): string
    {
        $section = strtolower($sectionTitle);
        $tag = strtolower((string) ($item['tag'] ?? ''));
        $kind = strtolower((string) ($item['kind'] ?? 'public'));

        return match (true) {
            $kind === 'internal' => 'internal',
            str_contains($section, 'reference') || in_array($tag, ['dokumen', 'security'], true) => 'reference',
            str_contains($section, 'public') => 'public',
            str_contains($section, 'data') || in_array($tag, ['data', 'bridge'], true) => 'data',
            default => 'all',
        };
    }

    public function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= strtoupper(substr($part, 0, 1));
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'PU';
    }

    private function displayName(array $user): string
    {
        $fullName = trim((string) ($user['full_name'] ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        return (string) ($user['username'] ?? 'Pengguna');
    }
}