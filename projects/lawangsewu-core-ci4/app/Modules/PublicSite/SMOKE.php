<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/AuthGateway/Contracts/AuthGatewayInterface.php';
require_once dirname(__DIR__) . '/AuthGateway/Services/LegacyGatewayAuthBridge.php';
require_once __DIR__ . '/Contracts/LandingPageInterface.php';
require_once __DIR__ . '/Controllers/LandingController.php';
require_once __DIR__ . '/Services/LegacyLandingPageService.php';

use App\Modules\PublicSite\Controllers\LandingController;
use App\Modules\PublicSite\Services\LegacyLandingPageService;

$service = new LegacyLandingPageService();
$controller = new LandingController($service);
$getState = $service->buildState([
    'REQUEST_URI' => '/lawangsewu/',
    'REQUEST_METHOD' => 'GET',
]);
$postState = $service->buildState([
    'REQUEST_URI' => '/lawangsewu/',
    'REQUEST_METHOD' => 'POST',
], [
    'landing_login' => '1',
    'username' => 'superadmin',
    'password' => 'definitely-not-the-live-password',
]);

echo 'get_portal=' . $getState['portalUrl'] . PHP_EOL;
echo 'get_landing=' . $getState['landingUrl'] . PHP_EOL;
echo 'get_logged_in=' . ($getState['isLoggedIn'] ? '1' : '0') . PHP_EOL;
echo 'post_error=' . $postState['loginError'] . PHP_EOL;
echo 'post_show_panel=' . ($postState['showLoginPanel'] ? '1' : '0') . PHP_EOL;
echo 'post_redirect=' . ($postState['redirectTo'] ?? '') . PHP_EOL;
echo 'controller_get_view=' . (($controller->index(['REQUEST_URI' => '/lawangsewu/', 'REQUEST_METHOD' => 'GET'])['view'] ?? '')) . PHP_EOL;
echo 'controller_post_mode=' . (isset($controller->authenticateInline([
    'REQUEST_URI' => '/lawangsewu/',
    'REQUEST_METHOD' => 'POST',
], [
    'landing_login' => '1',
    'username' => 'superadmin',
    'password' => 'definitely-not-the-live-password',
])['redirect']) ? 'redirect' : 'view') . PHP_EOL;