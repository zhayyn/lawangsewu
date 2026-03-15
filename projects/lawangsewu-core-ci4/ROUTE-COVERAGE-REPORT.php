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

$contracts = json_decode((string) file_get_contents(__DIR__ . '/config/route-contracts.json'), true);
if (!is_array($contracts)) {
    throw new RuntimeException('Invalid route-contracts.json');
}

$kernel = new StagingKernel();
$summary = [
    'ok' => 0,
    'redirect' => 0,
    'missing' => 0,
];

$tests = [];

foreach ((array) ($contracts['coreRoutes'] ?? []) as $path) {
    $tests[] = ['family' => 'core-route', 'path' => (string) $path, 'expectedRedirect' => ''];
}

foreach ((array) ($contracts['publicWidgetRoutes'] ?? []) as $path) {
    $tests[] = ['family' => 'public-widget', 'path' => (string) $path, 'expectedRedirect' => ''];
}

foreach ((array) ($contracts['legacyAliases'] ?? []) as $alias) {
    $tests[] = [
        'family' => 'legacy-alias',
        'path' => (string) ($alias['from'] ?? ''),
        'expectedRedirect' => (string) ($alias['to'] ?? ''),
    ];
}

$report = [];
$report[] = 'Lawangsewu Core CI4 Route Coverage Report';
$report[] = '======================================';
$report[] = 'in_scope_routes=' . count($tests);

foreach ($tests as $test) {
    $response = $kernel->handle('GET', $test['path'], [
        'REQUEST_URI' => $test['path'],
        'REQUEST_METHOD' => 'GET',
    ], []);

    $status = (int) ($response['status'] ?? 0);
    $location = (string) ($response['headers']['Location'] ?? '');
    $bodyLength = strlen((string) ($response['body'] ?? ''));
    $outcome = 'missing';

    if ($status === 200) {
        $outcome = 'ok';
    } elseif ($status >= 300 && $status < 400) {
        $outcome = 'redirect';
    }

    $summary[$outcome]++;

    $report[] = sprintf(
        "[%s] %s status=%d outcome=%s location=%s body=%d\n",
        $test['family'],
        $test['path'],
        $status,
        $outcome,
        $location !== '' ? $location : '-',
        $bodyLength,
    );

    if ($test['family'] === 'legacy-alias' && $test['expectedRedirect'] !== '' && $location !== $test['expectedRedirect']) {
        $report[] = '  expected_redirect=' . $test['expectedRedirect'];
    }
}

$report[] = '';
$report[] = 'Summary';
$report[] = 'ok=' . $summary['ok'];
$report[] = 'redirect=' . $summary['redirect'];
$report[] = 'missing=' . $summary['missing'];

$report[] = '';
$report[] = 'Out of Scope Contracts';
$report[] = 'auth_routes_retained=' . count((array) ($contracts['authRoutes'] ?? []));
$report[] = 'public_api_routes_retained=' . count((array) ($contracts['publicApiRoutes'] ?? []));
$report[] = 'integration_contracts_retained=' . count((array) ($contracts['integrationContracts'] ?? []));

echo implode(PHP_EOL, $report) . PHP_EOL;