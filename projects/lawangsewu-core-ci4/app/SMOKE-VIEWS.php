<?php

declare(strict_types=1);

require_once __DIR__ . '/Support/StagingViewRenderer.php';

require_once __DIR__ . '/Modules/AuthGateway/Contracts/AuthGatewayInterface.php';
require_once __DIR__ . '/Modules/AuthGateway/Services/LegacyGatewayAuthBridge.php';

require_once __DIR__ . '/Modules/AppRegistry/Contracts/AppRegistryInterface.php';
require_once __DIR__ . '/Modules/AppRegistry/Providers/LegacyAppRegistry.php';

require_once __DIR__ . '/Modules/DocsRegistry/Contracts/DocsRegistryInterface.php';
require_once __DIR__ . '/Modules/DocsRegistry/Providers/LegacyDocsRegistry.php';
require_once __DIR__ . '/Modules/DocsRegistry/Controllers/IndexController.php';

require_once __DIR__ . '/Modules/WidgetRegistry/Contracts/WidgetRegistryInterface.php';
require_once __DIR__ . '/Modules/WidgetRegistry/Providers/LegacyWidgetRegistry.php';
require_once __DIR__ . '/Modules/WidgetRegistry/Controllers/DirectoryController.php';

require_once __DIR__ . '/Modules/Portal/Contracts/PortalRegistryInterface.php';
require_once __DIR__ . '/Modules/Portal/Providers/LegacyPortalRegistry.php';
require_once __DIR__ . '/Modules/Portal/Support/PortalViewModelBuilder.php';
require_once __DIR__ . '/Modules/Portal/Controllers/HomeController.php';

require_once __DIR__ . '/Modules/PublicSite/Contracts/LandingPageInterface.php';
require_once __DIR__ . '/Modules/PublicSite/Services/LegacyLandingPageService.php';
require_once __DIR__ . '/Modules/PublicSite/Controllers/LandingController.php';

use App\Modules\DocsRegistry\Controllers\IndexController as DocsIndexController;
use App\Modules\Portal\Support\PortalViewModelBuilder;
use App\Modules\Portal\Controllers\HomeController as PortalHomeController;
use App\Modules\PublicSite\Controllers\LandingController as PublicLandingController;
use App\Modules\WidgetRegistry\Controllers\DirectoryController as WidgetDirectoryController;
use App\Support\StagingViewRenderer;

$renderer = new StagingViewRenderer();

$docsState = (new DocsIndexController())->index();
$docsHtml = $renderer->render((string) $docsState['view'], (array) $docsState['data']);

$widgetState = (new WidgetDirectoryController())->index();
$widgetHtml = $renderer->render((string) $widgetState['view'], (array) $widgetState['data']);

$landingState = (new PublicLandingController())->index([
    'REQUEST_URI' => '/lawangsewu/',
    'REQUEST_METHOD' => 'GET',
]);
$landingHtml = $renderer->render((string) $landingState['view'], (array) $landingState['data']);

$portalController = new PortalHomeController();
$portalState = $portalController->index('/portal');
$portalViewModel = (new PortalViewModelBuilder())->build();
$portalHtml = $renderer->render('portal/dashboard', $portalViewModel);

echo 'docs_html=' . strlen($docsHtml) . PHP_EOL;
echo 'widget_html=' . strlen($widgetHtml) . PHP_EOL;
echo 'landing_html=' . strlen($landingHtml) . PHP_EOL;
echo 'portal_mode=' . (isset($portalState['redirect']) ? 'redirect' : 'view') . PHP_EOL;
echo 'portal_html=' . strlen($portalHtml) . PHP_EOL;