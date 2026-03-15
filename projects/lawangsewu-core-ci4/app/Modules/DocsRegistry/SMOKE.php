<?php

declare(strict_types=1);

require_once __DIR__ . '/Contracts/DocsRegistryInterface.php';
require_once __DIR__ . '/Providers/LegacyDocsRegistry.php';
require_once __DIR__ . '/Controllers/IndexController.php';

use App\Modules\DocsRegistry\Controllers\IndexController;
use App\Modules\DocsRegistry\Providers\LegacyDocsRegistry;

$registry = new LegacyDocsRegistry();
$summary = $registry->summary();
$documents = $registry->allDocuments();
$controller = new IndexController($registry);
$state = $controller->index();

echo 'sections=' . $summary['sectionCount'] . PHP_EOL;
echo 'total=' . $summary['total'] . PHP_EOL;
echo 'security=' . $summary['securityCount'] . PHP_EOL;
echo 'migration=' . $summary['migrationCount'] . PHP_EOL;
echo 'first=' . (($documents[0]['title'] ?? '')) . PHP_EOL;
echo 'featured=' . count($state['data']['featuredDocuments'] ?? []) . PHP_EOL;
echo 'widget_dir=' . ($state['data']['widgetDirectoryPath'] ?? '') . PHP_EOL;
echo 'view=' . ($state['view'] ?? '') . PHP_EOL;