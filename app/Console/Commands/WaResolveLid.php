<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WaCarakaMessage;
use App\Models\WaCarakaConversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * wa:resolve-lid — Retroactive patch pesan @lid menggunakan mapping dari wa-bridge.
 *
 * Opsi B spare: dijalankan manual setelah mapping dipelajari/diinject ke bridge.
 *
 * Usage:
 *   php artisan wa:resolve-lid            # patch semua @lid yang bisa di-resolve
 *   php artisan wa:resolve-lid --dry-run  # preview saja tanpa update DB
 */
class WaResolveLid extends Command
{
    protected $signature = 'wa:resolve-lid
                            {--dry-run : Preview saja, tidak ada perubahan di DB}';

    protected $description = 'Retroactive patch remote_number @lid di database menggunakan mapping dari wa-bridge.';

    public function handle(): int
    {
        $dryRun    = $this->option('dry-run');
        $bridgeUrl = rtrim(config('services.wa_caraka.base_url', env('LW_WA_V2_BASE', 'http://127.0.0.1:8790')), '/');
        $token     = config('services.wa_caraka.token', env('LW_WA_V2_TOKEN', 'lawangsewu2026'));

        $this->info('[wa:resolve-lid] Mengambil LID mappings dari bridge...');

        // ── 1. Ambil semua mapping dari bridge ────────────────────────────────
        try {
            $response = Http::withHeaders(['X-WA-V2-Token' => $token])
                ->timeout(10)
                ->get("{$bridgeUrl}/lid-mappings");

            if (! $response->successful()) {
                $this->error('Gagal mengambil mapping dari bridge: ' . $response->status());
                return self::FAILURE;
            }

            $pairs = $response->json('pairs', []);
        } catch (\Throwable $e) {
            $this->error('Koneksi ke bridge gagal: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (empty($pairs)) {
            $this->warn('Bridge tidak punya mapping LID sama sekali.');
            $this->line('Gunakan endpoint POST /lid-mappings/inject di bridge untuk isi manual, lalu jalankan lagi.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Ditemukan %d mapping. Memulai analisis DB...', count($pairs)));

        // ── 2. Untuk setiap mapping, patch messages + conversations ───────────
        $totalMessages     = 0;
        $totalConversations = 0;

        foreach ($pairs as $pair) {
            $lidJid = $pair['lid'] ?? null;
            $pnJid  = $pair['pn']  ?? null;

            if (! $lidJid || ! $pnJid) continue;

            // Normalisasi: pastikan format @s.whatsapp.net
            $pnNormalized = str_ends_with($pnJid, '@s.whatsapp.net')
                ? $pnJid
                : preg_replace('/@.*$/', '', $pnJid) . '@s.whatsapp.net';

            $lidMessages = WaCarakaMessage::where('remote_number', $lidJid)->count();
            $lidConvos   = WaCarakaConversation::where('remote_number', $lidJid)->count();

            if ($lidMessages === 0 && $lidConvos === 0) {
                $this->line("  SKIP {$lidJid} — tidak ada data di DB");
                continue;
            }

            $this->line("  PATCH {$lidJid} → {$pnNormalized} ({$lidMessages} pesan, {$lidConvos} konv.)");

            if ($dryRun) continue;

            DB::transaction(function () use ($lidJid, $pnNormalized, &$totalMessages, &$totalConversations) {
                // Hitung conversation_id baru
                $newConvoId = \App\Models\WaCarakaMessage::conversationIdFor($pnNormalized);

                // Update messages
                $patched = WaCarakaMessage::where('remote_number', $lidJid)
                    ->update([
                        'remote_number'   => $pnNormalized,
                        'conversation_id' => $newConvoId,
                    ]);
                $totalMessages += $patched;

                // Update conversations
                $patchedConvos = WaCarakaConversation::where('remote_number', $lidJid)
                    ->update([
                        'remote_number'   => $pnNormalized,
                        'conversation_id' => $newConvoId,
                    ]);
                $totalConversations += $patchedConvos;
            });
        }

        // ── 3. Ringkasan ──────────────────────────────────────────────────────
        $this->newLine();
        if ($dryRun) {
            $this->warn('[DRY-RUN] Tidak ada perubahan. Jalankan tanpa --dry-run untuk apply.');
        } else {
            $this->info("✅ Selesai. {$totalMessages} pesan & {$totalConversations} konversasi di-patch.");
            Log::info('[WaCaraka] wa:resolve-lid selesai', [
                'messages_patched'      => $totalMessages,
                'conversations_patched' => $totalConversations,
            ]);
        }

        return self::SUCCESS;
    }
}
