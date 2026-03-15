<?php

declare(strict_types=1);

require_once __DIR__ . '/Contracts/WidgetRegistryInterface.php';
require_once __DIR__ . '/Providers/LegacyWidgetRegistry.php';
require_once __DIR__ . '/Controllers/DirectoryController.php';

use App\Modules\WidgetRegistry\Controllers\DirectoryController;
use App\Modules\WidgetRegistry\Providers\LegacyWidgetRegistry;

$registry = new LegacyWidgetRegistry();
$summary = $registry->summary();
$allItems = $registry->allItems();
$controller = new DirectoryController($registry);
$state = $controller->index();

echo 'categories=' . $summary['categoryCount'] . PHP_EOL;
echo 'total=' . $summary['total'] . PHP_EOL;
echo 'embed=' . $summary['embedCount'] . PHP_EOL;
echo 'monitoring=' . $summary['monitoringCount'] . PHP_EOL;
echo 'first=' . (($allItems[0]['name'] ?? '')) . PHP_EOL;
echo 'featured=' . count($state['data']['featuredItems'] ?? []) . PHP_EOL;
echo 'view=' . ($state['view'] ?? '') . PHP_EOL;