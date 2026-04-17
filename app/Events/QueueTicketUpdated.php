<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * QueueTicketUpdated
 *
 * Broadcast saat tiket PTSP atau Sidang mengalami perubahan status.
 * Channel: private-lawangsewu.queue.ptsp / private-lawangsewu.queue.sidang
 *
 * Pendengar utama:
 * - PtspQueue.vue   — update daftar tiket real-time tanpa reload halaman
 * - SidangQueue.vue — update daftar tiket real-time tanpa reload halaman
 */
class QueueTicketUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $type,    // 'ptsp' | 'sidang'
        public readonly string $action,  // 'created' | 'called' | 'served' | 'skipped' | 'completed' | 'postponed'
        public readonly array  $ticket,
        public readonly array  $summary,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('lawangsewu.queue.' . $this->type),
        ];
    }

    public function broadcastAs(): string
    {
        return 'queue.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type'    => $this->type,
            'action'  => $this->action,
            'ticket'  => $this->ticket,
            'summary' => $this->summary,
        ];
    }
}
