<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('employees:sync --source=sikep-portal')
            ->dailyAt('05:10')
            ->when(fn (): bool => (bool) config('lawangsewu.sikep.portal_username') && (bool) config('lawangsewu.sikep.portal_password'))
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/jatidiri-scheduler.log'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'portal.sso' => \App\Http\Middleware\EnsurePortalSsoSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
