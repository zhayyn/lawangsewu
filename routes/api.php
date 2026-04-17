<?php

use App\Http\Controllers\Api\PortalApiController;
use App\Http\Controllers\WaCarakaWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'active', 'role:viewer,operator,useradmin,admin', 'throttle:60,1'])->prefix('lawangsewu')->group(function () {
    Route::get('/dashboard', [PortalApiController::class, 'dashboard']);
    Route::get('/cameras', [PortalApiController::class, 'cameras']);
    Route::get('/chat', [PortalApiController::class, 'chat']);
});

Route::middleware(['auth', 'verified', 'active', 'role:operator,admin', 'throttle:30,1'])->prefix('lawangsewu')->group(function () {
    Route::post('/chat/messages', [PortalApiController::class, 'storeMessage']);
});

// WA Caraka Webhook — NOT behind SSO, verified by shared token
Route::post('/wa-caraka/webhook/inbound', [WaCarakaWebhookController::class, 'inbound'])
    ->name('wacaraka.webhook.inbound');
