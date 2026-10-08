<?php

namespace App\Console\Commands;

use App\Models\WaCarakaConversation;
use App\Models\WaCarakaMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WaRepairConversationKeys extends Command
{
    protected $signature = 'wacaraka:repair-conversation-keys
                            {--dry-run : Preview tanpa menyimpan perubahan}';

    protected $description = 'Perbaiki conversation_id agar grup/personal tidak tergabung, lalu sinkronkan data inbox.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('[wacaraka:repair-conversation-keys] Memulai normalisasi conversation_id...');

        $messagePatched = 0;
        $conversationsCreated = 0;
        $conversationsUpdated = 0;

        DB::transaction(function () use ($dryRun, &$messagePatched, &$conversationsCreated, &$conversationsUpdated) {
            WaCarakaMessage::query()
                ->select(['id', 'remote_number', 'conversation_id', 'source'])
                ->orderBy('id')
                ->chunk(500, function ($rows) use ($dryRun, &$messagePatched) {
                    foreach ($rows as $row) {
                        $targetConversationId = WaCarakaMessage::conversationIdFor((string) $row->remote_number, $row->source);

                        if ((string) $row->conversation_id === $targetConversationId) {
                            continue;
                        }

                        $messagePatched++;

                        if ($dryRun) {
                            continue;
                        }

                        WaCarakaMessage::query()
                            ->where('id', $row->id)
                            ->update(['conversation_id' => $targetConversationId]);
                    }
                });

            $grouped = WaCarakaMessage::query()
                ->select('conversation_id', 'remote_number', 'source')
                ->selectRaw('MAX(created_at) as last_activity_at')
                ->selectRaw("SUM(CASE WHEN direction = 'inbound' AND replied_at IS NULL THEN 1 ELSE 0 END) as unread_count")
                ->whereNotNull('conversation_id')
                ->groupBy('conversation_id', 'remote_number', 'source')
                ->get();

            foreach ($grouped as $row) {
                $payload = [
                    'remote_number' => (string) $row->remote_number,
                    'last_activity_at' => $row->last_activity_at,
                    'unread_count' => (int) ($row->unread_count ?? 0),
                    'source' => $row->source,
                ];

                $existing = WaCarakaConversation::query()
                    ->where('conversation_id', $row->conversation_id)
                    ->where('source', $row->source)
                    ->first();

                if (!$existing) {
                    $conversationsCreated++;

                    if (!$dryRun) {
                        WaCarakaConversation::query()->create(array_merge($payload, [
                            'conversation_id' => (string) $row->conversation_id,
                            'status' => 'pending',
                        ]));
                    }

                    continue;
                }

                $conversationsUpdated++;

                if (!$dryRun) {
                    $existing->fill($payload)->save();
                }
            }

            // Hapus percakapan lama yang tidak memiliki pesan terkait lagi
            if (!$dryRun) {
                WaCarakaConversation::query()->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('wa_caraka_messages')
                        ->whereColumn('wa_caraka_messages.conversation_id', 'wa_caraka_conversations.conversation_id');
                })->delete();
            }
        });

        $this->newLine();
        if ($dryRun) {
            $this->warn('[DRY-RUN] Tidak ada perubahan yang disimpan.');
        }

        $this->info(sprintf(
            'Selesai. messages patched: %d, conversations created: %d, conversations updated: %d',
            $messagePatched,
            $conversationsCreated,
            $conversationsUpdated,
        ));

        return self::SUCCESS;
    }
}
