<?php

namespace App\Console\Commands;

use App\Services\LegacyPendopoSyncService;
use Illuminate\Console\Command;

class SyncLegacyPendopo extends Command
{
    protected $signature = 'pendopo:sync-legacy {--archive : Archive legacy folder after sync}';

    protected $description = 'Sync legacy Pendopo data, settings, and photos into Lawangsewu.';

    public function handle(LegacyPendopoSyncService $service): int
    {
        $result = $service->syncAll();

        $this->info('Sinkronisasi legacy Pendopo selesai.');
        $this->line('Data tamu tersinkron: ' . $result['entries']);
        $this->line('Foto baru tersalin: ' . $result['photos']);
        $this->line('Total data legacy: ' . $result['legacy_total']);

        if ($this->option('archive')) {
            $archivedPath = $service->archiveLegacy();
            $this->info('Legacy Pendopo diarsipkan ke: ' . $archivedPath);
        }

        return self::SUCCESS;
    }
}
