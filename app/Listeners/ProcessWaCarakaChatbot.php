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

        // [TESTING MODE] Hanya merespons nomor 081317361689
        if (!str_contains((string)$message->remote_number, '81317361689')) {
            return;
        }

        // Jalankan chatbot service
        try {
            $chatbot = app(\App\Services\WaCarakaChatbotService::class);
            $reply = $chatbot->processInbound((string)$message->remote_number, $message->message_text ?? '');
            
            if ($reply) {
                app(\App\Services\WaCarakaService::class)->sendText((string)$message->remote_number, $reply);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('[ProcessWaCarakaChatbot] Error processing message', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
