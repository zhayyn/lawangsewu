<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Support/StagingViewRenderer.php';

require_once dirname(__DIR__) . '/app/Modules/AuthGateway/Contracts/AuthGatewayInterface.php';
require_once dirname(__DIR__) . '/app/Modules/AuthGateway/Services/LegacyGatewayAuthBridge.php';
require_once dirname(__DIR__) . '/app/Modules/AuditAccess/Contracts/AuditLoggerInterface.php';
require_once dirname(__DIR__) . '/app/Modules/AuditAccess/Services/JsonAuditLogger.php';

require_once dirname(__DIR__) . '/app/Modules/AppRegistry/Contracts/AppRegistryInterface.php';
require_once dirname(__DIR__) . '/app/Modules/AppRegistry/Providers/LegacyAppRegistry.php';
require_once dirname(__DIR__) . '/app/Modules/AppRegistry/Controllers/IndexController.php';

require_once dirname(__DIR__) . '/app/Modules/DocsRegistry/Contracts/DocsRegistryInterface.php';
require_once dirname(__DIR__) . '/app/Modules/DocsRegistry/Providers/LegacyDocsRegistry.php';
require_once dirname(__DIR__) . '/app/Modules/DocsRegistry/Controllers/IndexController.php';

require_once dirname(__DIR__) . '/app/Modules/WidgetRegistry/Contracts/WidgetRegistryInterface.php';
require_once dirname(__DIR__) . '/app/Modules/WidgetRegistry/Providers/LegacyWidgetRegistry.php';
require_once dirname(__DIR__) . '/app/Modules/WidgetRegistry/Controllers/DirectoryController.php';

require_once dirname(__DIR__) . '/app/Modules/Portal/Contracts/PortalRegistryInterface.php';
require_once dirname(__DIR__) . '/app/Modules/Portal/Providers/LegacyPortalRegistry.php';
require_once dirname(__DIR__) . '/app/Modules/Portal/Support/PortalViewModelBuilder.php';
require_once dirname(__DIR__) . '/app/Modules/Portal/Controllers/HomeController.php';

require_once dirname(__DIR__) . '/app/Modules/PublicSite/Contracts/LandingPageInterface.php';
require_once dirname(__DIR__) . '/app/Modules/PublicSite/Services/LegacyLandingPageService.php';
require_once dirname(__DIR__) . '/app/Modules/PublicSite/Controllers/LandingController.php';

require_once dirname(__DIR__) . '/app/Controllers/StagingKernel.php';

use App\Controllers\StagingKernel;

$kernel = new StagingKernel();
$response = $kernel->handle(
    (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
    (string) ($_SERVER['REQUEST_URI'] ?? '/'),
    $_SERVER,
    $_POST
);

http_response_code((int) ($response['status'] ?? 200));
foreach ((array) ($response['headers'] ?? []) as $name => $value) {
    header($name . ': ' . $value);
}

echo (string) ($response['body'] ?? '');