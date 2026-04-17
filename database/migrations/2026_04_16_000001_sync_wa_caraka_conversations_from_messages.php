<?php

use App\Models\WaCarakaConversation;
use App\Models\WaCarakaMessage;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Re-sync wa_caraka_conversations from existing wa_caraka_messages.
     * Fixes the case where messages existed but conversations were not created.
     */
    public function up(): void
    {
        $grouped = WaCarakaMessage::query()
            ->select('conversation_id', 'remote_number')
            ->groupBy('conversation_id', 'remote_number')
            ->get();

        foreach ($grouped as $row) {
            $lastMsg = WaCarakaMessage::where('conversation_id', $row->conversation_id)
                ->latest()
                ->first();

            $unread = WaCarakaMessage::where('conversation_id', $row->conversation_id)
                ->where('direction', 'inbound')
                ->whereNull('replied_at')
                ->count();

            WaCarakaConversation::firstOrCreate(
                ['conversation_id' => $row->conversation_id],
                [
                    'remote_number'    => $row->remote_number,
                    'status'           => 'pending',
                    'unread_count'     => $unread,
                    'last_activity_at' => $lastMsg?->created_at ?? now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Rollback: remove conversations that were created by this migration
        // (those with no claimed_by owner are safe to remove if needed)
    }
};
