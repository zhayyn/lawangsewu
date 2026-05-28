<?php

namespace App\Listeners;

use App\Events\WaCarakaMessageReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProcessWaCarakaChatbot implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WaCarakaMessageReceived $event): void
    {
        $message = $event->message;

        // Jangan merespon pesan keluar/outgoing
        if ($message->direction !== 'inbound') {
            return;
        }

        // Jalankan chatbot service
        try {
            $chatbot = app(\App\Services\WaCarakaChatbotService::class);
            $reply = $chatbot->processInbound($message->from, $message->body ?? '');
            
            if ($reply) {
                app(\App\Services\WaCarakaService::class)->sendText($message->from, $reply);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[ProcessWaCarakaChatbot] Error processing message', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
