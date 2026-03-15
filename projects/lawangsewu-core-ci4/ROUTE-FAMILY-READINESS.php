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
use App\Modules\AuditAccess\Services\JsonAuditLogger;
use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;

$contracts = json_decode((string) file_get_contents(__DIR__ . '/config/route-contracts.json'), true);
if (!is_array($contracts)) {
    throw new RuntimeException('Invalid route-contracts.json');
}

$guestAuditFile = __DIR__ . '/writable/logs/audit-access.readiness.guest.jsonl';
$authAuditFile = __DIR__ . '/writable/logs/audit-access.readiness.authenticated.jsonl';

@unlink($guestAuditFile);
@unlink($authAuditFile);

$guestAuth = new LegacyGatewayAuthBridge();
$guestKernel = new StagingKernel(
    auditLogger: new JsonAuditLogger($guestAuditFile),
    auth: $guestAuth,
);

$authenticatedAuditStats = syntheticPortalAuditStats($authAuditFile);

$families = [
    [
        'name' => 'landing',
        'tests' => [
            ['path' => '/', 'expectedStatus' => 200, 'expectedLocation' => ''],
        ],
        'auditRouteFamilies' => ['landing'],
        'requiredEvents' => ['request'],
    ],
    [
        'name' => 'docs',
        'tests' => [
            ['path' => '/walkthrough', 'expectedStatus' => 200, 'expectedLocation' => ''],
        ],
        'auditRouteFamilies' => ['docs'],
        'requiredEvents' => ['request'],
    ],
    [
        'name' => 'widget-directory',
        'tests' => [
            ['path' => '/daftar-widget', 'expectedStatus' => 200, 'expectedLocation' => ''],
            ['path' => '/widget-links', 'expectedStatus' => 200, 'expectedLocation' => ''],
        ],
        'auditRouteFamilies' => ['widget-directory'],
        'requiredEvents' => ['request'],
    ],
    [
        'name' => 'public-widget',
        'tests' => array_map(
            static fn (string $path): array => ['path' => $path, 'expectedStatus' => 200, 'expectedLocation' => ''],
            array_values((array) ($contracts['publicWidgetRoutes'] ?? []))
        ),
        'auditRouteFamilies' => ['public-widget'],
        'requiredEvents' => ['widget-route'],
    ],
    [
        'name' => 'compat-alias',
        'tests' => array_map(
            static fn (array $alias): array => [
                'path' => (string) ($alias['from'] ?? ''),
                'expectedStatus' => 302,
                'expectedLocation' => (string) ($alias['to'] ?? ''),
            ],
            array_values((array) ($contracts['legacyAliases'] ?? []))
        ),
        'auditRouteFamilies' => ['public-widget'],
        'requiredEvents' => ['alias-redirect'],
    ],
    [
        'name' => 'app-registry',
        'tests' => [
            ['path' => '/app-registry', 'expectedStatus' => 200, 'expectedLocation' => ''],
            ['path' => '/app-registry/launch?app=gateway', 'expectedStatus' => 302, 'expectedLocation' => '/lawangsewu/gateway/index'],
        ],
        'auditRouteFamilies' => ['app-registry', 'app-launch'],
        'requiredEvents' => ['request', 'launch-app'],
    ],
    [
        'name' => 'portal',
        'tests' => [
            ['path' => '/portal', 'expectedStatus' => 302, 'expectedLocation' => '/lawangsewu/gateway/login?return=%2Fportal'],
            ['path' => '/portal', 'expectedStatus' => 200, 'expectedLocation' => '', 'auth' => 'synthetic'],
        ],
        'auditRouteFamilies' => ['portal'],
        'requiredEvents' => ['redirect', 'request'],
    ],
    [
        'name' => 'portal-launch',
        'tests' => [
            ['path' => '/portal/launch?path=%2Fdashboard-perkara', 'expectedStatus' => 302, 'expectedLocation' => '/lawangsewu/gateway/login?return=%2Fportal'],
            ['path' => '/portal/launch?path=%2Fdashboard-perkara', 'expectedStatus' => 302, 'expectedLocation' => '/dashboard-perkara', 'auth' => 'synthetic'],
        ],
        'auditRouteFamilies' => ['portal-launch'],
        'requiredEvents' => ['launch-auth-required', 'launch-portal-item'],
    ],
];

$measuredResults = [];
foreach ($families as $family) {
    $measuredResults[(string) $family['name']] = evaluateFamily($guestKernel, $family);
}

$guestAuditStats = auditStats(loadAuditEntries($guestAuditFile));
$finalResults = [];
foreach ($families as $family) {
    $finalResults[(string) $family['name']] = finalizeFamilyResult(
        $family,
        $measuredResults[(string) $family['name']] ?? ['tests' => 0, 'ok' => 0, 'redirect' => 0, 'missing' => 0],
        $guestAuditStats,
        $authenticatedAuditStats,
    );
}

$report = [
    'Lawangsewu Core CI4 Route Family Readiness',
    '=========================================',
];

foreach ($families as $family) {
    $result = $finalResults[(string) $family['name']];
    $report[] = sprintf(
        '%s readiness=%s tests=%d ok=%d redirect=%d missing=%d audit=%s note=%s',
        $family['name'],
        $result['readiness'],
        $result['tests'],
        $result['ok'],
        $result['redirect'],
        $result['missing'],
        $result['auditObserved'] ? 'observed' : 'missing',
        $result['note'],
    );
}

$report[] = '';
$report[] = 'Authenticated Gap';
$report[] = 'portal_logged_in_launch_observed=' . (($guestAuditStats['events']['launch-portal-item'] ?? 0) + ($authenticatedAuditStats['events']['launch-portal-item'] ?? 0));
$report[] = 'portal_auth_required_observed=' . ($guestAuditStats['events']['launch-auth-required'] ?? 0);
$report[] = 'portal_synthetic_auth_observed=' . ($authenticatedAuditStats['events']['launch-portal-item'] ?? 0);
$report[] = 'note=Portal launcher sekarang tervalidasi pada kondisi anon dan sesi sintetis staging. Kredensial live gateway tetap belum dibuktikan di report ini.';

$report[] = '';
$report[] = 'Audit Event Counts';
foreach ($guestAuditStats['events'] as $event => $count) {
    $report[] = $event . '=' . $count;
}

if ($authenticatedAuditStats['events'] !== []) {
    $report[] = '';
    $report[] = 'Synthetic Auth Audit Event Counts';
    foreach ($authenticatedAuditStats['events'] as $event => $count) {
        $report[] = $event . '=' . $count;
    }
}

echo implode(PHP_EOL, $report) . PHP_EOL;

function evaluateFamily(StagingKernel $kernel, array $family): array
{
    $ok = 0;
    $redirect = 0;
    $missing = 0;

    foreach ((array) ($family['tests'] ?? []) as $test) {
        $path = (string) ($test['path'] ?? '/');
        $response = runFamilyTest($kernel, $path, (string) ($test['auth'] ?? 'guest'));

        $status = (int) ($response['status'] ?? 0);
        $location = (string) ($response['headers']['Location'] ?? '');
        $expectedStatus = (int) ($test['expectedStatus'] ?? 200);
        $expectedLocation = (string) ($test['expectedLocation'] ?? '');

        $matches = $status === $expectedStatus;
        if ($expectedLocation !== '') {
            $matches = $matches && $location === $expectedLocation;
        }

        if (!$matches) {
            $missing++;
            continue;
        }

        if ($status === 200) {
            $ok++;
            continue;
        }

        if ($status >= 300 && $status < 400) {
            $redirect++;
            continue;
        }

        $missing++;
    }

    return [
        'tests' => count((array) ($family['tests'] ?? [])),
        'ok' => $ok,
        'redirect' => $redirect,
        'missing' => $missing,
    ];
}

function finalizeFamilyResult(array $family, array $measuredResult, array $guestAuditStats, array $authenticatedAuditStats): array
{
    $tests = (int) ($measuredResult['tests'] ?? 0);
    $ok = (int) ($measuredResult['ok'] ?? 0);
    $redirect = (int) ($measuredResult['redirect'] ?? 0);
    $missing = (int) ($measuredResult['missing'] ?? 0);

    $requiredEvents = array_values((array) ($family['requiredEvents'] ?? []));
    $auditObserved = true;
    foreach ($requiredEvents as $event) {
        $observedCount = (int) ($guestAuditStats['events'][$event] ?? 0) + (int) ($authenticatedAuditStats['events'][$event] ?? 0);
        if ($observedCount < 1) {
            $auditObserved = false;
            break;
        }
    }

    return [
        'tests' => $tests,
        'ok' => $ok,
        'redirect' => $redirect,
        'missing' => $missing,
        'auditObserved' => $auditObserved,
        'readiness' => readinessLabel((string) ($family['name'] ?? ''), $missing, $auditObserved),
        'note' => readinessNote((string) ($family['name'] ?? ''), $missing, $auditObserved),
    ];
}

function readinessLabel(string $family, int $missing, bool $auditObserved): string
{
    if ($missing > 0) {
        return 'not-ready';
    }

    if (in_array($family, ['portal', 'portal-launch'], true)) {
        return $auditObserved ? 'ready-for-shadow-auth' : 'needs-auth-smoke';
    }

    if ($family === 'compat-alias') {
        return $auditObserved ? 'ready-for-compat' : 'observe';
    }

    return $auditObserved ? 'ready-for-shadow' : 'observe';
}

function readinessNote(string $family, int $missing, bool $auditObserved): string
{
    if ($missing > 0) {
        return 'ada route yang belum memenuhi kontrak';
    }

    if (in_array($family, ['portal', 'portal-launch'], true)) {
        return 'jalur anon dan sesi sintetis staging sudah tervalidasi';
    }

    if (!$auditObserved) {
        return 'route lolos, tetapi event audit belum muncul';
    }

    return 'kontrak dan audit dasar sudah tervalidasi';
}

function runFamilyTest(StagingKernel $kernel, string $path, string $authMode): array
{
    $existingUser = $_SESSION['gateway_user'] ?? null;

    if ($authMode === 'synthetic') {
        syntheticLogin();
    } else {
        unset($_SESSION['gateway_user']);
    }

    $response = $kernel->handle('GET', $path, [
        'REQUEST_URI' => $path,
        'REQUEST_METHOD' => 'GET',
    ], []);

    if (is_array($existingUser)) {
        $_SESSION['gateway_user'] = $existingUser;
    } else {
        unset($_SESSION['gateway_user']);
    }

    return $response;
}

function loadAuditEntries(string $file): array
{
    if (!is_file($file)) {
        return [];
    }

    $entries = [];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $entries[] = $decoded;
        }
    }

    return $entries;
}

function auditStats(array $entries): array
{
    $stats = [
        'events' => [],
    ];

    foreach ($entries as $entry) {
        $event = (string) ($entry['event'] ?? 'unknown');
        $stats['events'][$event] = (int) ($stats['events'][$event] ?? 0) + 1;
    }

    ksort($stats['events']);

    return $stats;
}

function syntheticPortalAuditStats(string $logFile): array
{
    @unlink($logFile);

    $auth = new App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge();
    $auth->isLoggedIn();
    syntheticLogin();

    $kernel = new StagingKernel(
        auditLogger: new App\Modules\AuditAccess\Services\JsonAuditLogger($logFile),
        auth: $auth,
    );

    $paths = [
        '/portal',
        '/portal/launch?path=%2Fdashboard-perkara',
    ];

    foreach ($paths as $path) {
        $kernel->handle('GET', $path, [
            'REQUEST_URI' => $path,
            'REQUEST_METHOD' => 'GET',
        ], []);
    }

    unset($_SESSION['gateway_user']);

    return auditStats(loadAuditEntries($logFile));
}

function syntheticLogin(): void
{
    $_SESSION['gateway_user'] = [
        'id' => 9001,
        'username' => 'staging.superadmin',
        'full_name' => 'Staging Superadmin',
        'role' => 'superadmin',
        'login_at' => gmdate('c'),
    ];
}