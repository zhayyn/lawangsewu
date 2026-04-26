<?php

namespace App\Console\Commands;

use App\Models\WaCarakaConversation;
use App\Models\WaCarakaMessage;
use App\Support\WaCarakaDatabase;
use App\Services\WaCarakaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetWaCarakaState extends Command
{
    protected $signature = 'wacaraka:reset-state
                            {--hard : Hapus semua data WA Caraka lokal (messages, conversations, logs, handovers, marks)}
                            {--pull : Tarik inbox dari WA runtime setelah reset}
                            {--force : Lewati konfirmasi}' ;

    protected $description = 'Reset state WA Caraka agar counter kembali sinkron dengan kondisi operasional saat ini.';

    public function handle(WaCarakaService $service): int
    {
        $hard = (bool) $this->option('hard');
        $pull = (bool) $this->option('pull');
        $force = (bool) $this->option('force');

        $modeText = $hard ? 'HARD RESET' : 'SOFT RESET';
        $this->warn("Mode: {$modeText}");

        if (! $force) {
            $confirmed = $this->confirm(
                $hard
                    ? 'Ini akan menghapus SEMUA data WA Caraka lokal. Lanjutkan?'
                    : 'Ini akan mereset counter unread/unreplied lokal. Lanjutkan?',
                false,
            );

            if (! $confirmed) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        if ($hard) {
            $this->hardReset();
        } else {
            $this->softReset();
        }

        if ($pull) {
            $this->line('Menarik inbox realtime dari runtime...');
            $result = $service->pullInbox();
            $data = $result['data'] ?? [];

            $this->table(
                ['ok', 'supported', 'pulled', 'stored', 'message'],
                [[
                    $result['ok'] ? 'yes' : 'no',
                    ($data['supported'] ?? false) ? 'yes' : 'no',
                    (string) ($data['pulled'] ?? 0),
                    (string) ($data['stored'] ?? 0),
                    (string) ($data['message'] ?? '-'),
                ]],
            );
        }

        $this->showCurrentStats();
        $this->info('Reset WA Caraka selesai.');

        return self::SUCCESS;
    }

    private function softReset(): void
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_messages') || !WaCarakaDatabase::hasTable('wa_caraka_conversations')) {
            $this->warn('Tabel WA Caraka belum lengkap. Soft reset dilewati.');
            return;
        }

        $now = now();

        $updatedInbound = WaCarakaMessage::query()
            ->where('direction', 'inbound')
            ->whereNull('replied_at')
            ->update(['replied_at' => $now]);

        WaCarakaConversation::query()->update([
            'unread_count' => 0,
            'last_activity_at' => DB::raw('COALESCE(last_activity_at, NOW())'),
        ]);

        // After soft reset, all non-closed conversations are marked open.
        WaCarakaConversation::query()
            ->where('status', '!=', 'closed')
            ->update(['status' => 'open']);

        $this->line("Inbound unreplied ditandai replied: {$updatedInbound}");
    }

    private function hardReset(): void
    {
        WaCarakaDatabase::transaction(function () {
            $tables = [
                'wa_caraka_handovers',
                'wa_caraka_conversation_marks',
                'wa_caraka_messages',
                'wa_caraka_conversations',
                'wa_caraka_logs',
            ];

            foreach ($tables as $table) {
                if (WaCarakaDatabase::hasTable($table)) {
                    WaCarakaDatabase::table($table)->delete();
                }
            }
        });

        $this->line('Semua data WA Caraka lokal dibersihkan.');
    }

    private function showCurrentStats(): void
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_messages') || !WaCarakaDatabase::hasTable('wa_caraka_conversations')) {
            return;
        }

        $stats = [
            'messages_total' => WaCarakaMessage::count(),
            'inbound' => WaCarakaMessage::where('direction', 'inbound')->count(),
            'outbound' => WaCarakaMessage::where('direction', 'outbound')->count(),
            'unreplied' => WaCarakaMessage::where('direction', 'inbound')->whereNull('replied_at')->count(),
            'conversations' => WaCarakaConversation::count(),
            'pending' => WaCarakaConversation::where('status', 'pending')->count(),
            'open' => WaCarakaConversation::where('status', 'open')->count(),
        ];

        $rows = [];
        foreach ($stats as $k => $v) {
            $rows[] = [$k, (string) $v];
        }

        $this->table(['metric', 'value'], $rows);
    }
}
