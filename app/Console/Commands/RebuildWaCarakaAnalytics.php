<?php

namespace App\Console\Commands;

use App\Services\WaCarakaService;
use App\Models\WaCarakaMessage;
use App\Models\WaCarakaMonthlySnapshot;
use App\Support\WaCarakaDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

class RebuildWaCarakaAnalytics extends Command
{
    protected $signature = 'wacaraka:rebuild-analytics';

    protected $description = 'Bangun ulang metrik harian dan snapshot bulanan WA Caraka dari pesan yang sudah tersimpan.';

    public function handle(WaCarakaService $service): int
    {
        $result = $service->rebuildDailyMetrics();
        $snapshots = 0;

        if (WaCarakaDatabase::hasTable('wa_caraka_monthly_snapshots') && WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            $firstMessage = WaCarakaMessage::query()->oldest('created_at')->first();
            $firstMonth = $firstMessage?->created_at?->copy()->startOfMonth() ?? now()->startOfMonth();
            $currentMonth = now()->startOfMonth();

            while ($firstMonth->lte($currentMonth)) {
                $monthKey = $firstMonth->format('Y-m');
                WaCarakaMonthlySnapshot::query()->updateOrCreate(
                    ['scope_key' => 'global', 'month_key' => $monthKey],
                    [
                        'month_start' => $firstMonth->toDateString(),
                        'month_end' => $firstMonth->copy()->endOfMonth()->toDateString(),
                        'snapshot_taken_at' => now(),
                    ],
                );
                $snapshots++;
                $firstMonth->addMonth();
            }
        }

        $this->table(['metric', 'value'], [
            ['days', (string) ($result['days'] ?? 0)],
            ['messages', (string) ($result['messages'] ?? 0)],
            ['snapshots', (string) $snapshots],
        ]);

        $this->info('Rebuild analytics selesai.');

        return self::SUCCESS;
    }
}
