<?php

declare(strict_types=1);

namespace App\Modules\WidgetRegistry\Controllers;

require_once dirname(__DIR__) . '/Support/LegacyWidgetSourceAdapter.php';

use App\Modules\WidgetRegistry\Contracts\WidgetRegistryInterface;
use App\Modules\WidgetRegistry\Providers\LegacyWidgetRegistry;
use App\Modules\WidgetRegistry\Support\LegacyWidgetSourceAdapter;

final class DirectoryController
{
    private WidgetRegistryInterface $registry;
    private LegacyWidgetSourceAdapter $sourceAdapter;

    public function __construct(?WidgetRegistryInterface $registry = null, ?LegacyWidgetSourceAdapter $sourceAdapter = null)
    {
        $this->registry = $registry ?? new LegacyWidgetRegistry();
        $this->sourceAdapter = $sourceAdapter ?? new LegacyWidgetSourceAdapter();
    }

    public function index(): array
    {
        $legacyHtml = $this->sourceAdapter->renderSourcePath('Walkthrough-DBPrakom/widget-links.html', '/daftar-widget');
        if (is_string($legacyHtml) && $legacyHtml !== '') {
            return [
                'status' => 200,
                'rawHtml' => $legacyHtml,
            ];
        }

        return [
            'view' => 'widgetregistry/directory',
            'data' => [
                'categories' => $this->registry->categories(),
                'summary' => $this->registry->summary(),
                'featuredItems' => array_slice($this->registry->allItems(), 0, 6),
            ],
        ];
    }

    public function show(string $path): array
    {
        $item = $this->registry->findByPath($path);
        if ($item === null) {
            return [
                'view' => 'widgetregistry/not-found',
                'data' => [
                    'path' => $path,
                ],
            ];
        }

        $legacyHtml = $this->sourceAdapter->render($item);
        if (is_string($legacyHtml) && $legacyHtml !== '') {
            return [
                'status' => 200,
                'rawHtml' => $legacyHtml,
            ];
        }

        $relatedItems = array_values(array_filter(
            $this->registry->allItems(),
            static fn (array $candidate): bool => (string) ($candidate['path'] ?? '') !== $path
                && (string) ($candidate['category'] ?? '') === (string) ($item['category'] ?? '')
        ));

        return [
            'view' => 'widgetregistry/page',
            'data' => [
                'item' => $item,
                'relatedItems' => array_slice($relatedItems, 0, 4),
                'summary' => $this->registry->summary(),
            ],
        ];
    }
}