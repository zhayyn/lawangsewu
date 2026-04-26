<?php

use App\Http\Controllers\Api\PortalApiController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\WaCarakaWebhookController;
use Illuminate\Support\Facades\Route;

// Health Check Endpoints — Public, no auth required
Route::get('/health', [HealthCheckController::class, 'health']);
Route::get('/ready', [HealthCheckController::class, 'ready']);
Route::get('/live', [HealthCheckController::class, 'live']);

Route::middleware(['auth', 'verified', 'active', 'role:viewer,operator,useradmin,admin', 'throttle:60,1'])->prefix('lawangsewu')->group(function () {
    Route::get('/dashboard', [PortalApiController::class, 'dashboard']);
    Route::get('/cameras', [PortalApiController::class, 'cameras']);
    Route::get('/chat', [PortalApiController::class, 'chat']);
});

Route::middleware(['auth', 'verified', 'active', 'role:operator,admin', 'throttle:30,1'])->prefix('lawangsewu')->group(function () {
    Route::post('/chat/messages', [PortalApiController::class, 'storeMessage']);
});

// WA Caraka Webhook — Secured by token, IP whitelist, and rate limiting
Route::middleware([\App\Http\Middleware\SecureWaWebhook::class, 'throttle:500,1'])->group(function () {
    Route::post('/wa-caraka/webhook/inbound', [WaCarakaWebhookController::class, 'inbound'])
        ->name('wacaraka.webhook.inbound');
    Route::post('/wa-caraka/webhook/history-sync', [WaCarakaWebhookController::class, 'historySync'])
        ->name('wacaraka.webhook.history-sync');
});
