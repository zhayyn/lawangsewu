<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/AuthGateway/Contracts/AuthGatewayInterface.php';
require_once dirname(__DIR__) . '/AuthGateway/Services/LegacyGatewayAuthBridge.php';
require_once __DIR__ . '/Contracts/AppRegistryInterface.php';
require_once __DIR__ . '/Providers/LegacyAppRegistry.php';
require_once __DIR__ . '/Controllers/IndexController.php';

use App\Modules\AppRegistry\Controllers\IndexController;
use App\Modules\AppRegistry\Providers\LegacyAppRegistry;

$registry = new LegacyAppRegistry();
$summary = $registry->summary();
$apps = $registry->applications();
$controller = new IndexController($registry);
$state = $controller->index();

echo 'total=' . $summary['total'] . PHP_EOL;
echo 'visible=' . $summary['visible'] . PHP_EOL;
echo 'sibling=' . $summary['siblingCount'] . PHP_EOL;
echo 'status=' . $summary['statusLabel'] . PHP_EOL;
echo 'first=' . (($apps[0]['name'] ?? '')) . PHP_EOL;
echo 'controller_apps=' . count($state['data']['applications'] ?? []) . PHP_EOL;
echo 'view=' . ($state['view'] ?? '') . PHP_EOL;