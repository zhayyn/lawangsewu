<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Support\ChatAttachment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('lawangsewu.chat.global')];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        $message = $this->message->loadMissing('user');

        return [
            'message' => [
                'id' => $message->id,
                'user_id' => $message->user_id,
                'type' => $message->type,
                'content' => $message->content,
                'metadata' => [
                    ...($message->metadata ?? []),
                    'attachment' => ChatAttachment::present($message->metadata['attachment'] ?? null, $message),
                ],
                'created_at' => optional($message->created_at)->toISOString(),
                'user' => [
                    'id' => $message->user?->id,
                    'name' => $message->user?->name,
                    'alias' => $message->user?->alias,
                    'avatar' => $message->user?->avatar,
                ],
            ],
        ];
    }
}
