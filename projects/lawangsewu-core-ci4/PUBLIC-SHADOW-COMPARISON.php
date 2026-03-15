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
$baseUrl = 'http://127.0.0.1/lawangsewu';

$routes = ['/','/walkthrough','/daftar-widget'];
foreach ((array) ($contracts['publicWidgetRoutes'] ?? []) as $path) {
    $routes[] = (string) $path;
}

$routes = array_values(array_unique($routes));

$report = [];
$report[] = 'Lawangsewu Public Shadow Comparison';
$report[] = '==================================';

$summary = [
    'safe' => 0,
    'review' => 0,
    'hold' => 0,
];

foreach ($routes as $path) {
    $live = fetchLive($baseUrl, $path);
    $staging = $kernel->handle('GET', $path, [
        'REQUEST_URI' => $path,
        'REQUEST_METHOD' => 'GET',
    ], []);

    $liveStatus = (int) ($live['status'] ?? 0);
    $stagingStatus = (int) ($staging['status'] ?? 0);
    $liveLength = (int) ($live['bodyLength'] ?? 0);
    $stagingLength = strlen((string) ($staging['body'] ?? ''));
    $statusMatch = $liveStatus === $stagingStatus ? 'match' : 'diff';
    $lengthDelta = abs($liveLength - $stagingLength);
    $deltaRatio = $liveLength > 0 ? round(($lengthDelta / $liveLength) * 100, 2) : 0.0;
    $verdict = verdict($liveStatus, $stagingStatus, $deltaRatio);
    $summary[$verdict]++;

    $report[] = sprintf(
        '%s live=%d/%d staging=%d/%d status=%s delta=%d ratio=%.2f%% verdict=%s' . PHP_EOL,
        $path,
        $liveStatus,
        $liveLength,
        $stagingStatus,
        $stagingLength,
        $statusMatch,
        $lengthDelta,
        $deltaRatio,
        $verdict,
    );
}

$report[] = '';
$report[] = 'Summary';
$report[] = 'safe=' . $summary['safe'];
$report[] = 'review=' . $summary['review'];
$report[] = 'hold=' . $summary['hold'];

echo implode(PHP_EOL, $report) . PHP_EOL;

function verdict(int $liveStatus, int $stagingStatus, float $deltaRatio): string
{
    if ($liveStatus !== $stagingStatus) {
        return 'hold';
    }

    if ($liveStatus !== 200) {
        return 'review';
    }

    if ($deltaRatio <= 15.0) {
        return 'safe';
    }

    if ($deltaRatio <= 60.0) {
        return 'review';
    }

    return 'hold';
}

function fetchLive(string $baseUrl, string $path): array
{
    $url = rtrim($baseUrl, '/') . ($path === '/' ? '/' : $path);
    $headers = [];
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'timeout' => 15,
            'header' => "User-Agent: Lawangsewu-Staging-Shadow-Compare\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if (isset($http_response_header) && is_array($http_response_header)) {
        $headers = $http_response_header;
    }

    $status = 0;
    foreach ($headers as $headerLine) {
        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $headerLine, $matches) === 1) {
            $status = (int) $matches[1];
            break;
        }
    }

    return [
        'status' => $status,
        'bodyLength' => is_string($body) ? strlen($body) : 0,
    ];
}