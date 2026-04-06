<?php

use App\Services\EmployeeDirectorySyncService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('employees:sync {--source=sikep-portal}', function (EmployeeDirectorySyncService $service): int {
    $source = (string) $this->option('source');
    $result = $service->sync($source);

    if (!$result['ok']) {
        $this->error((string) $result['message']);
        return 1;
    }

    $this->info((string) $result['message']);
    $this->line('inserted=' . (int) $result['inserted'] . ', updated=' . (int) $result['updated']);
    return 0;
})->purpose('Sinkronisasi direktori pegawai dari SIKEP API atau login admin SIKEP.');
