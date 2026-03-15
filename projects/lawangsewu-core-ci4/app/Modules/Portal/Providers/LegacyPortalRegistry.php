<?php

declare(strict_types=1);

namespace App\Modules\Portal\Providers;

use App\Modules\AppRegistry\Providers\LegacyAppRegistry;
use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;
use App\Modules\DocsRegistry\Providers\LegacyDocsRegistry;
use App\Modules\Portal\Contracts\PortalRegistryInterface;
use App\Modules\WidgetRegistry\Providers\LegacyWidgetRegistry;

final class LegacyPortalRegistry implements PortalRegistryInterface
{
    public function __construct(
        private readonly LegacyGatewayAuthBridge $auth = new LegacyGatewayAuthBridge(),
        private readonly LegacyAppRegistry $apps = new LegacyAppRegistry(),
        private readonly LegacyDocsRegistry $docs = new LegacyDocsRegistry(),
        private readonly LegacyWidgetRegistry $widgets = new LegacyWidgetRegistry(),
    ) {
    }

    public function sections(): array
    {
        $documents = $this->docs->allDocuments();
        $widgetItems = $this->widgets->allItems();
        $isAdminRole = in_array($this->auth->userRole(), ['superadmin', 'admin'], true);

        return [
            [
                'title' => 'Internal Tools',
                'caption' => 'Akses inti untuk operasional harian dan pengelolaan sistem yang membutuhkan sesi login aktif.',
                'items' => array_values(array_map(
                    fn (array $app): array => $this->portalAppItem($app),
                    $this->apps->visibleApplications()
                )),
            ],
            [
                'title' => 'Reference',
                'caption' => 'Dokumentasi, widget directory, dan referensi operasional yang tetap sering dibuka dari satu dashboard.',
                'items' => array_values(array_filter([
                    [
                        'name' => 'Walkthrough Lawangsewu',
                        'path' => '/walkthrough',
                        'desc' => 'Bundel dokumentasi implementasi, migrasi, keamanan, dan operasional.',
                        'tag' => 'Dokumen',
                        'kind' => 'public',
                    ],
                    [
                        'name' => 'Daftar Link Widget',
                        'path' => '/daftar-widget',
                        'desc' => 'Direktori URL final widget publik beserta jalur embed cepat.',
                        'tag' => 'Publik',
                        'kind' => 'public',
                    ],
                    $this->portalDocumentItem($this->findDocument($documents, 'REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE') ?? $this->findDocument($documents, 'REMOTE-ACCESS-SECURITY-SERVER9')),
                    $this->portalDocumentItem($this->findDocument($documents, 'SECURITY-HARDENING')),
                    $isAdminRole ? [
                        'name' => 'Mapping SSO Layanan',
                        'path' => '/lawangsewu/gateway/sso-mapping',
                        'desc' => 'Peta login terpadu untuk layanan yang terhubung dengan gateway.',
                        'tag' => 'Admin',
                        'kind' => 'internal',
                    ] : null,
                ])),
            ],
            [
                'title' => 'Public Views',
                'caption' => 'Halaman publik yang bisa dibuka cepat tanpa harus berpindah ke menu lain.',
                'items' => array_values(array_map(
                    fn (array $item): array => $this->portalWidgetItem($item),
                    array_values(array_filter($widgetItems, static fn (array $item): bool => in_array((string) ($item['path'] ?? ''), [
                        '/berita-pengadilan',
                        '/pengumuman-peradilan',
                        '/widget-pengumuman',
                        '/monitor-persidangan',
                    ], true)))
                )),
            ],
            [
                'title' => 'Data & Monitoring',
                'caption' => 'Kumpulan dashboard dan bridge data yang membantu pembacaan cepat kondisi sistem.',
                'items' => array_values(array_map(
                    fn (array $item): array => $this->portalWidgetItem($item),
                    array_values(array_filter($widgetItems, static fn (array $item): bool => in_array((string) ($item['path'] ?? ''), [
                        '/dashboard-perkara',
                        '/dashboard-ecourt',
                        '/dashboard-hakim',
                        '/bridge-server10',
                        '/monitor-wa',
                    ], true)))
                )),
            ],
        ];
    }

    public function allItems(): array
    {
        $items = [];
        foreach ($this->sections() as $section) {
            foreach ($section['items'] as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function summary(): array
    {
        $allItems = $this->allItems();
        $internalCount = count(array_filter($allItems, static fn ($item) => (string) ($item['kind'] ?? '') === 'internal'));
        $publicCount = count($allItems) - $internalCount;
        $docCount = count(array_filter($allItems, static fn ($item) => in_array((string) ($item['tag'] ?? ''), ['Dokumen', 'Security'], true)));

        return [
            'total' => count($allItems),
            'internalCount' => $internalCount,
            'publicCount' => $publicCount,
            'docCount' => $docCount,
        ];
    }

    private function portalAppItem(array $app): array
    {
        return [
            'name' => (string) ($app['name'] ?? ''),
            'path' => (string) ($app['path'] ?? ''),
            'desc' => (string) ($app['description'] ?? ''),
            'tag' => $this->appTag((string) ($app['kind'] ?? '')),
            'kind' => 'internal',
        ];
    }

    private function portalDocumentItem(?array $document): ?array
    {
        if ($document === null) {
            return null;
        }

        return [
            'name' => $this->documentTitle((string) ($document['title'] ?? 'Dokumen')),
            'path' => (string) ($document['pdfPath'] ?? $document['mdPath'] ?? '/walkthrough'),
            'desc' => (string) ($document['summary'] ?? ''),
            'tag' => $this->documentTag((string) ($document['kind'] ?? 'overview')),
            'kind' => 'public',
        ];
    }

    private function portalWidgetItem(array $item): array
    {
        return [
            'name' => (string) ($item['name'] ?? ''),
            'path' => (string) ($item['path'] ?? ''),
            'desc' => (string) ($item['desc'] ?? ''),
            'tag' => (string) ($item['tag'] ?? 'Publik'),
            'kind' => 'public',
        ];
    }

    private function findDocument(array $documents, string $title): ?array
    {
        foreach ($documents as $document) {
            if ((string) ($document['title'] ?? '') === $title) {
                return $document;
            }
        }

        return null;
    }

    private function appTag(string $kind): string
    {
        return match ($kind) {
            'core' => 'SSO',
            'sibling' => 'Aplikasi',
            'ops' => 'Operasional',
            'ai' => 'AI',
            'admin' => 'Admin',
            'reference' => 'API',
            default => 'Aplikasi',
        };
    }

    private function documentTitle(string $title): string
    {
        return match ($title) {
            'REMOTE-ACCESS-SECURITY-SERVER9-ONEPAGE', 'REMOTE-ACCESS-SECURITY-SERVER9' => 'Remote Access Security',
            'SECURITY-HARDENING' => 'Security Hardening',
            default => str_replace('-', ' ', $title),
        };
    }

    private function documentTag(string $kind): string
    {
        return match ($kind) {
            'security' => 'Security',
            'migration', 'audit' => 'Dokumen',
            default => 'Dokumen',
        };
    }
}