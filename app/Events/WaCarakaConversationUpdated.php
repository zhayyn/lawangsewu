<?php

namespace App\Events;

use App\Models\WaCarakaConversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaCarakaConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WaCarakaConversation $conversation)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('lawangsewu.wacaraka.inbox')];
    }

    public function broadcastAs(): string
    {
        return 'wa-caraka.conversation.updated';
    }

    public function broadcastWith(): array
    {
        $conversation = $this->conversation->loadMissing('owner:id,name,alias', 'pendingHandover.requestor:id,name,alias');

        return [
            'conversation' => [
                'conversationId' => $conversation->conversation_id,
                'remoteNumber' => $conversation->remote_number,
                'remoteName' => $conversation->remote_name,
                'status' => $conversation->status,
                'unreadCount' => $conversation->unread_count,
                'lastActivityAt' => $conversation->last_activity_at?->diffForHumans(),
                'claimedAt' => $conversation->claimed_at?->diffForHumans(),
                'ownerPresence' => $this->ownerPresenceFor($conversation),
                'justClaimed' => $this->wasJustClaimed($conversation),
                'owner' => $conversation->owner ? [
                    'id' => $conversation->owner->id,
                    'name' => $conversation->owner->name,
                    'alias' => $conversation->owner->alias,
                ] : null,
                'pendingHandover' => $conversation->pendingHandover ? [
                    'id' => $conversation->pendingHandover->id,
                    'requestor' => [
                        'id' => $conversation->pendingHandover->requestor->id,
                        'name' => $conversation->pendingHandover->requestor->name,
                    ],
                ] : null,
            ],
        ];
    }

    protected function ownerPresenceFor(WaCarakaConversation $conversation): ?string
    {
        if (!$conversation->claimed_by || !$conversation->last_activity_at) {
            return null;
        }

        if ($conversation->last_activity_at->gte(now()->subMinutes(2))) {
            return 'active';
        }

        if ($conversation->last_activity_at->gte(now()->subMinutes(10))) {
            return 'standby';
        }

        return 'idle';
    }

    protected function wasJustClaimed(WaCarakaConversation $conversation): bool
    {
        return (bool) $conversation->claimed_at?->gte(now()->subMinutes(3));
    }
}