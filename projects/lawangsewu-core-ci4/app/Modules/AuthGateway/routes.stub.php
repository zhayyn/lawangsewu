<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| AuthGateway Route Stub
|--------------------------------------------------------------------------
|
| Ini adalah stub kontrak route untuk modul AuthGateway di CI4 Core.
| Belum dipakai produksi. Dipakai sebagai panduan migrasi helper dan controller.
|
| Target route:
| - /
| - /portal
| - /lawangsewu/gateway/login
| - /lawangsewu/gateway/logout
| - /lawangsewu/gateway/index
|
*/

return [
    'GET /lawangsewu/gateway/login' => 'AuthGateway\\LoginController::index',
    'POST /lawangsewu/gateway/login' => 'AuthGateway\\LoginController::authenticate',
    'GET /lawangsewu/gateway/logout' => 'AuthGateway\\LogoutController::index',
    'GET /lawangsewu/gateway/index' => 'AuthGateway\\GatewayHomeController::index',
];