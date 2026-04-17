<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Jadwalkan pembersihan (arsip) pesan WA setiap awal bulan jam 01:00 AM (365 hari ke belakang / 1 tahun)
Schedule::command('wacaraka:archive --days=365')->monthlyOn(1, '01:00');

// Maintenance Database Routine untuk mencegah database melambat
Schedule::command('passport:purge')->dailyAt('02:00'); // Bersihkan token OAuth kedaluwarsa
Schedule::command('auth:clear-resets')->dailyAt('02:30'); // Bersihkan token reset password
Schedule::command('cache:prune-stale-tags')->hourly(); // Bersihkan cache yang tidak terpakai
Schedule::command('queue:prune-batches')->daily(); // Bersihkan batch queue (jika menggunakan database queue batch)
Schedule::command('session:gc')->daily(); // Hapus expired sessions
