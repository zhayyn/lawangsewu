<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$scheduleLockMinutes = 120;

$guardedCommand = static function (string $command, string $name) use ($scheduleLockMinutes): Event {
    return Schedule::command($command)
        ->name($name)
        ->withoutOverlapping($scheduleLockMinutes)
        ->onOneServer()
        ->runInBackground();
};

$guardedCommand('wacaraka:archive --days=365', 'wacaraka-archive')
    ->monthlyOn(1, '01:00');

$guardedCommand('passport:purge', 'passport-purge')
    ->dailyAt('02:00');

$guardedCommand('auth:clear-resets', 'auth-clear-resets')
    ->dailyAt('02:30');

$guardedCommand('cache:prune-stale-tags', 'cache-prune-stale-tags')
    ->hourly();

$guardedCommand('queue:prune-batches', 'queue-prune-batches')
    ->dailyAt('03:00');

$guardedCommand('session:gc', 'session-gc')
    ->dailyAt('03:30');
