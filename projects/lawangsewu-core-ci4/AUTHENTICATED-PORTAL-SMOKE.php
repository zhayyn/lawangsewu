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
use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;

$logFile = __DIR__ . '/writable/logs/audit-access.authenticated.jsonl';
@unlink($logFile);

$auth = new LegacyGatewayAuthBridge();
$auth->isLoggedIn();

$_SESSION['gateway_user'] = [
    'id' => 9001,
    'username' => 'staging.superadmin',
    'full_name' => 'Staging Superadmin',
    'role' => 'superadmin',
    'login_at' => gmdate('c'),
];

$kernel = new StagingKernel(
    auditLogger: new App\Modules\AuditAccess\Services\JsonAuditLogger($logFile),
    auth: $auth,
);

$tests = [
    '/portal',
    '/portal/launch?path=%2Fdashboard-perkara',
    '/portal/launch?path=%2Flawangsewu%2Fgateway%2Fsso-mapping',
];

foreach ($tests as $path) {
    $response = $kernel->handle('GET', $path, [
        'REQUEST_URI' => $path,
        'REQUEST_METHOD' => 'GET',
    ], []);

    echo 'GET ' . $path . ' status=' . $response['status'] . ' location=' . (($response['headers']['Location'] ?? '')) . ' body=' . strlen((string) ($response['body'] ?? '')) . PHP_EOL;
}

$events = [];
foreach (file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
    $entry = json_decode($line, true);
    if (!is_array($entry)) {
        continue;
    }

    $event = (string) ($entry['event'] ?? 'unknown');
    $events[$event] = (int) ($events[$event] ?? 0) + 1;
}

ksort($events);

echo 'auth_user=' . ($_SESSION['gateway_user']['username'] ?? '') . PHP_EOL;
foreach ($events as $event => $count) {
    echo $event . '=' . $count . PHP_EOL;
}

unset($_SESSION['gateway_user']);