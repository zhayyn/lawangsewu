<?php

namespace App\Events;

use App\Models\WaCarakaMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaCarakaMessageSynced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WaCarakaMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('lawangsewu.wacaraka.inbox')];
    }

    public function broadcastAs(): string
    {
        return 'wa-caraka.message.synced';
    }

    public function broadcastWith(): array
    {
        $message = $this->message->loadMissing('user:id,name,alias');

        return [
            'message' => [
                'id' => $message->id,
                'direction' => $message->direction,
                'remoteNumber' => $message->remote_number,
                'localNumber' => $message->local_number,
                'text' => $message->message_text,
                'type' => $message->message_type,
                'status' => $message->status,
                'waMessageId' => $message->wa_message_id,
                'conversationId' => $message->conversation_id,
                'operator' => $message->user ? ($message->user->alias ?: $message->user->name) : null,
                'repliedAt' => $message->replied_at
                    ? $message->replied_at->setTimezone('Asia/Jakarta')->format('d M H:i') . ' WIB'
                    : null,
                'sentAt' => $message->created_at
                    ? $message->created_at->setTimezone('Asia/Jakarta')->format('d M H:i') . ' WIB'
                    : null,
                'createdAtIso' => $message->created_at?->toISOString(),
                'metadata' => $message->metadata ?? [],
            ],
        ];
    }
}