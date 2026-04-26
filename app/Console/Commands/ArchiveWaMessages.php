<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WaCarakaConversation;
use App\Models\WaCarakaMessage;
use App\Models\WaCarakaLog;
use App\Support\WaCarakaDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ArchiveWaMessages extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'wacaraka:archive
                            {--days=90 : Jumlah hari sebelum data dianggap usang}
                            {--dry-run : Tampilkan statistik tanpa melakukan penghapusan}';

    /**
     * The console command description.
     */
    protected $description = 'Arsipkan pesan, percakapan, dan log WA Caraka yang sudah melewati batas usia ke file JSON, lalu bersihkan database.';

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $dryRun    = (bool) $this->option('dry-run');
        $threshold = Carbon::now()->subDays($days);
        $label     = $threshold->format('Y_m_d_His');

        $this->info("=== WA Caraka Archive ===");
        $this->info("Threshold : {$threshold->toDateTimeString()} (data lebih lama dari {$days} hari)");
        $dryRun && $this->warn('[DRY-RUN] Tidak ada data yang dihapus.');
        $this->newLine();

        $stats = [
            'archived_at'         => now('Asia/Jakarta')->toDateTimeString(),
            'threshold_days'      => $days,
            'messages_archived'   => 0,
            'logs_archived'       => 0,
            'conversations_pruned'=> 0,
        ];

        // Pastikan folder ada
        if (!Storage::disk('local')->exists('archives')) {
            Storage::disk('local')->makeDirectory('archives');
        }

        // 1. wa_caraka_messages
        if (WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            $query = WaCarakaMessage::where('created_at', '<', $threshold);
            $count = $query->count();

            $this->line("📨 wa_caraka_messages : {$count} baris lama ditemukan");

            if ($count > 0 && !$dryRun) {
                $data = [];
                $query->chunk(500, function ($rows) use (&$data) {
                    foreach ($rows as $row) $data[] = $row->toArray();
                });
                Storage::disk('local')->put(
                    "archives/wa_messages_before_{$label}.json",
                    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
                $deleted = WaCarakaMessage::where('created_at', '<', $threshold)->delete();
                $this->info("   ✅ {$deleted} baris dihapus & diarsipkan.");
                $stats['messages_archived'] = $deleted;
            } elseif ($count === 0) {
                $this->info("   ✅ Tidak ada yang perlu diarsipkan.");
            }
        }

        $this->newLine();

        // 2. wa_caraka_logs
        if (WaCarakaDatabase::hasTable('wa_caraka_logs')) {
            $query = WaCarakaLog::where('created_at', '<', $threshold);
            $count = $query->count();

            $this->line("📋 wa_caraka_logs : {$count} baris lama ditemukan");

            if ($count > 0 && !$dryRun) {
                $data = [];
                $query->chunk(500, function ($rows) use (&$data) {
                    foreach ($rows as $row) $data[] = $row->toArray();
                });
                Storage::disk('local')->put(
                    "archives/wa_logs_before_{$label}.json",
                    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                );
                $deleted = WaCarakaLog::where('created_at', '<', $threshold)->delete();
                $this->info("   ✅ {$deleted} baris dihapus & diarsipkan.");
                $stats['logs_archived'] = $deleted;
            } elseif ($count === 0) {
                $this->info("   ✅ Tidak ada yang perlu diarsipkan.");
            }
        }

        $this->newLine();

        // 3. wa_caraka_conversations — hapus percakapan yang tidak punya pesan aktif
        if (WaCarakaDatabase::hasTable('wa_caraka_conversations') && WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            $staleConvos = WaCarakaConversation::where('last_activity_at', '<', $threshold)->count();
            $this->line("💬 wa_caraka_conversations stale : {$staleConvos} baris");

            if ($staleConvos > 0 && !$dryRun) {
                $deleted = WaCarakaConversation::where('last_activity_at', '<', $threshold)->delete();
                $this->info("   ✅ {$deleted} percakapan usang dihapus.");
                $stats['conversations_pruned'] = $deleted;
            } elseif ($staleConvos === 0) {
                $this->info("   ✅ Semua percakapan masih aktif.");
            }
        }

        $this->newLine();

        // Simpan statistik ke cache agar bisa ditampilkan di System Monitor
        if (!$dryRun) {
            Cache::put('wacaraka:last_archive', $stats, now()->addDays(40));
        }

        $this->info("🎉 Proses selesai." . ($dryRun ? ' [DRY-RUN, tidak ada perubahan]' : ''));
        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Pesan diarsipkan',       $stats['messages_archived']],
                ['Log diarsipkan',         $stats['logs_archived']],
                ['Percakapan dihapus',     $stats['conversations_pruned']],
                ['Threshold',              "{$days} hari"],
                ['Waktu proses',           $stats['archived_at']],
            ]
        );

        return self::SUCCESS;
    }
}
