<?php

declare(strict_types=1);

require_once __DIR__ . '/Contracts/AuthGatewayInterface.php';
require_once __DIR__ . '/Services/LegacyGatewayAuthBridge.php';

use App\Modules\AuthGateway\Services\LegacyGatewayAuthBridge;

$bridge = new LegacyGatewayAuthBridge();

$results = [
    'normalize_empty' => $bridge->normalizeReturnPath('', '/portal'),
    'normalize_valid' => $bridge->normalizeReturnPath('/lawangsewu/portal?tab=favorites', '/portal'),
    'normalize_invalid_host' => $bridge->normalizeReturnPath('https://evil.example/path', '/portal'),
    'login_url' => $bridge->loginUrl('/lawangsewu/portal'),
    'logout_url' => $bridge->logoutUrl(),
    'user_role' => $bridge->userRole(),
    'sso_status' => $bridge->ssoStatusLabel(),
    'launcher_gateway' => $bridge->appLauncherUrl('gateway'),
    'launcher_mas_satset' => $bridge->appLauncherUrl('mas-satset-ai'),
    'sso_map_count' => (string) count($bridge->ssoServiceMap()),
];

foreach ($results as $key => $value) {
    echo $key . '=' . $value . PHP_EOL;
}