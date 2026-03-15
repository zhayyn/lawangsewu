<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/AuthGateway/Contracts/AuthGatewayInterface.php';
require_once dirname(__DIR__) . '/AuthGateway/Services/LegacyGatewayAuthBridge.php';
require_once dirname(__DIR__) . '/AppRegistry/Contracts/AppRegistryInterface.php';
require_once dirname(__DIR__) . '/AppRegistry/Providers/LegacyAppRegistry.php';
require_once dirname(__DIR__) . '/DocsRegistry/Contracts/DocsRegistryInterface.php';
require_once dirname(__DIR__) . '/DocsRegistry/Providers/LegacyDocsRegistry.php';
require_once dirname(__DIR__) . '/WidgetRegistry/Contracts/WidgetRegistryInterface.php';
require_once dirname(__DIR__) . '/WidgetRegistry/Providers/LegacyWidgetRegistry.php';
require_once __DIR__ . '/Contracts/PortalRegistryInterface.php';
require_once __DIR__ . '/Controllers/HomeController.php';
require_once __DIR__ . '/Providers/LegacyPortalRegistry.php';
require_once __DIR__ . '/Support/PortalViewModelBuilder.php';

use App\Modules\Portal\Controllers\HomeController;
use App\Modules\Portal\Providers\LegacyPortalRegistry;
use App\Modules\Portal\Support\PortalViewModelBuilder;

$registry = new LegacyPortalRegistry();
$sections = $registry->sections();
$summary = $registry->summary();
$builder = new PortalViewModelBuilder($registry);
$viewModel = $builder->build();
$firstItem = $viewModel['favoriteLeadItems'][0] ?? [];
$firstSymbol = $builder->cardSymbol($firstItem);
$controller = new HomeController($builder);
$controllerState = $controller->index('/portal');

echo 'sections=' . count($sections) . PHP_EOL;
echo 'total=' . $summary['total'] . PHP_EOL;
echo 'internal=' . $summary['internalCount'] . PHP_EOL;
echo 'public=' . $summary['publicCount'] . PHP_EOL;
echo 'docs=' . $summary['docCount'] . PHP_EOL;
echo 'initials=' . $viewModel['gatewayUserInitials'] . PHP_EOL;
echo 'favorite_leads=' . count($viewModel['favoriteLeadItems']) . PHP_EOL;
echo 'first_symbol=' . ($firstSymbol['symbol'] ?? '') . PHP_EOL;
echo 'controller_mode=' . (isset($controllerState['redirect']) ? 'redirect' : 'view') . PHP_EOL;