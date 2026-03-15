<?php

declare(strict_types=1);

namespace App\Modules\DocsRegistry\Controllers;

use App\Modules\DocsRegistry\Contracts\DocsRegistryInterface;
use App\Modules\DocsRegistry\Providers\LegacyDocsRegistry;

final class IndexController
{
    private DocsRegistryInterface $registry;

    public function __construct(?DocsRegistryInterface $registry = null)
    {
        $this->registry = $registry ?? new LegacyDocsRegistry();
    }

    public function index(): array
    {
        return [
            'view' => 'docsregistry/index',
            'data' => [
                'sections' => $this->registry->sections(),
                'summary' => $this->registry->summary(),
                'featuredDocuments' => array_slice($this->registry->allDocuments(), 0, 6),
                'widgetDirectoryPath' => '/daftar-widget',
                'masterPdfPath' => '/walkthrough/master-pdf',
            ],
        ];
    }
}