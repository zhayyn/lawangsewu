<?php

declare(strict_types=1);

require_once __DIR__ . '/app/Support/StagingViewRenderer.php';

require_once __DIR__ . '/app/Modules/AuthGateway/Contracts/AuthGatewayInterface.php';
require_once __DIR__ . '/app/Modules/AuthGateway/Services/LegacyGatewayAuthBridge.php';
require_once __DIR__ . '/app/Modules/AuditAccess/Contracts/AuditLoggerInterface.php';
require_once __DIR__ . '/app/Modules/AuditAccess/Services/JsonAuditLogger.php';

require_once __DIR__ . '/app/Modules/AppRegistry/Contracts/AppRegistryInterface.php';
require_once __DIR__ . '/app/Modules/AppRegistry/Providers/LegacyAppRegistry.php';
require_once __DIR__ . '/app/Modules/AppRegistry/Controllers/IndexController.php';

require_once __DIR__ . '/app/Modules/DocsRegistry/Contracts/DocsRegistryInterface.php';
require_once __DIR__ . '/app/Modules/DocsRegistry/Providers/LegacyDocsRegistry.php';
require_once __DIR__ . '/app/Modules/DocsRegistry/Controllers/IndexController.php';

require_once __DIR__ . '/app/Modules/WidgetRegistry/Contracts/WidgetRegistryInterface.php';
require_once __DIR__ . '/app/Modules/WidgetRegistry/Providers/LegacyWidgetRegistry.php';
require_once __DIR__ . '/app/Modules/WidgetRegistry/Controllers/DirectoryController.php';

require_once __DIR__ . '/app/Modules/Portal/Contracts/PortalRegistryInterface.php';
require_once __DIR__ . '/app/Modules/Portal/Providers/LegacyPortalRegistry.php';
require_once __DIR__ . '/app/Modules/Portal/Support/PortalViewModelBuilder.php';
require_once __DIR__ . '/app/Modules/Portal/Controllers/HomeController.php';

require_once __DIR__ . '/app/Modules/PublicSite/Contracts/LandingPageInterface.php';
require_once __DIR__ . '/app/Modules/PublicSite/Services/LegacyLandingPageService.php';
require_once __DIR__ . '/app/Modules/PublicSite/Controllers/LandingController.php';

require_once __DIR__ . '/app/Controllers/StagingKernel.php';

use App\Controllers\StagingKernel;

$logFile = __DIR__ . '/writable/logs/audit-access.jsonl';
@unlink($logFile);

$kernel = new StagingKernel();
$contracts = json_decode((string) file_get_contents(__DIR__ . '/config/route-contracts.json'), true);

if (!is_array($contracts)) {
    throw new RuntimeException('Invalid route-contracts.json');
}

$tests = [];

$appendTest = static function (array &$tests, string $method, string $path): void {
    $key = $method . ' ' . $path;
    if (isset($tests[$key])) {
        return;
    }

    $tests[$key] = [$method, $path, ['REQUEST_URI' => $path, 'REQUEST_METHOD' => $method], []];
};

foreach ((array) ($contracts['coreRoutes'] ?? []) as $path) {
    $appendTest($tests, 'GET', (string) $path);
}

foreach ((array) ($contracts['publicWidgetRoutes'] ?? []) as $path) {
    $appendTest($tests, 'GET', (string) $path);
}

foreach ((array) ($contracts['legacyAliases'] ?? []) as $alias) {
    $appendTest($tests, 'GET', (string) ($alias['from'] ?? ''));
}

$appendTest($tests, 'GET', '/lawangsewu/');
$appendTest($tests, 'GET', '/lawangsewu/walkthrough');
$appendTest($tests, 'GET', '/lawangsewu/portal');
$appendTest($tests, 'GET', '/widget-links');
$appendTest($tests, 'GET', '/app-registry');
$appendTest($tests, 'GET', '/app-registry/launch?app=gateway');
$appendTest($tests, 'GET', '/portal/launch?path=%2Fdashboard-perkara');

foreach ($tests as [$method, $path, $server, $post]) {
    $response = $kernel->handle($method, $path, $server, $post);
    echo $method . ' ' . $path . ' status=' . $response['status'] . ' location=' . (($response['headers']['Location'] ?? '')) . ' body=' . strlen((string) ($response['body'] ?? '')) . PHP_EOL;
}

$lines = is_file($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
echo 'audit_lines=' . count($lines) . PHP_EOL;