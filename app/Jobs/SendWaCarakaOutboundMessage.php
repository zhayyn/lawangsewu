<?php

namespace App\Jobs;

use App\Services\WaCarakaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWaCarakaOutboundMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $messageId,
        public ?string $sender = null,
    ) {
    }

    public function handle(WaCarakaService $waCarakaService): void
    {
        $waCarakaService->deliverQueuedMessage($this->messageId, $this->sender);
    }
}