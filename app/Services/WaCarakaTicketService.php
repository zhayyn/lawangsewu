<?php

namespace App\Services;

use App\Models\WaCarakaTicket;
use App\Support\WaCarakaDatabase;
use Illuminate\Support\Facades\Log;

/**
 * WaCarakaTicketService
 *
 * Manages pengaduan/konsultasi/umum tickets from the chatbot.
 * Replaces wamehehe's Pengaduan.php, Konsultasi.php, and Umum.php controllers.
 */
class WaCarakaTicketService
{
    public function __construct(protected WaCarakaService $wa) {}

    /**
     * List tickets with optional filters.
     */
    public function listTickets(?string $type = null, ?string $status = null, int $limit = 20): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_tickets')) {
            return [];
        }

        $query = WaCarakaTicket::query()
            ->with('assignee:id,name,alias')
            ->orderByDesc('created_at');

        if ($type) {
            $query->ofType($type);
        }
        if ($status) {
            $query->ofStatus($status);
        }

        return $query->limit($limit)
            ->get()
            ->map(fn (WaCarakaTicket $t) => $this->formatTicket($t))
            ->all();
    }

    /**
     * Reply to a ticket (save reply text, optionally with attachment).
     */
    public function replyTicket(int $ticketId, string $reply, ?string $attachmentPath, int $userId): array
    {
        $ticket = WaCarakaTicket::findOrFail($ticketId);

        $ticket->update([
            'reply'           => $reply,
            'status'          => 'replied',
            'assigned_to'     => $userId,
            'replied_at'      => now(),
            'attachment_path' => $attachmentPath,
        ]);

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => $this->formatTicket($ticket->fresh('assignee')),
        ];
    }

    /**
     * Send the reply to the user via WA.
     */
    public function sendTicketReply(int $ticketId, int $userId): array
    {
        $ticket = WaCarakaTicket::findOrFail($ticketId);

        if (empty($ticket->reply)) {
            return [
                'ok'     => false,
                'status' => 422,
                'error'  => 'Tiket belum memiliki balasan.',
            ];
        }

        $fullMessage = $ticket->replyPrefix() . ' ' . $ticket->reply;

        // Append konsultasi footer if applicable
        if ($ticket->type === 'konsultasi') {
            $fullMessage .= '. Terimakasih sudah melakukan konsultasi menggunakan Layanan SiNofita.';
        }

        $result = $this->wa->sendText(
            $ticket->remote_number,
            $fullMessage,
            'TIKET-REPLY',
            $userId,
        );

        if ($result['ok']) {
            $ticket->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        }

        return [
            'ok'     => $result['ok'],
            'status' => $result['ok'] ? 200 : 502,
            'data'   => $this->formatTicket($ticket->fresh('assignee')),
            'error'  => $result['error'] ?? null,
        ];
    }

    /**
     * Transfer ticket to a different type (e.g. pengaduan → konsultasi).
     */
    public function transferTicket(int $ticketId, string $newType): array
    {
        $validTypes = ['pengaduan', 'konsultasi', 'umum'];
        if (!in_array($newType, $validTypes)) {
            return [
                'ok'     => false,
                'status' => 422,
                'error'  => 'Tipe tiket tidak valid.',
            ];
        }

        $ticket = WaCarakaTicket::findOrFail($ticketId);
        $oldType = $ticket->type;

        $ticket->update(['type' => $newType]);

        Log::info('[WaCaraka:Ticket] Transferred', [
            'ticket_id' => $ticketId,
            'from_type' => $oldType,
            'to_type'   => $newType,
        ]);

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => $this->formatTicket($ticket->fresh('assignee')),
        ];
    }

    /**
     * Close a ticket.
     */
    public function closeTicket(int $ticketId): array
    {
        $ticket = WaCarakaTicket::findOrFail($ticketId);
        $ticket->update(['status' => 'closed']);

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => $this->formatTicket($ticket->fresh('assignee')),
        ];
    }

    /**
     * Schedule a live konsultasi call.
     */
    public function scheduleCall(int $ticketId, string $datetime, int $userId): array
    {
        $ticket = WaCarakaTicket::findOrFail($ticketId);

        if ($ticket->type !== 'live_konsul') {
            return [
                'ok'     => false,
                'status' => 422,
                'error'  => 'Hanya tiket live konsultasi yang bisa dijadwalkan.',
            ];
        }

        $ticket->update([
            'scheduled_call_at' => $datetime,
            'status'            => 'replied',
            'assigned_to'       => $userId,
        ]);

        // Send notification to user
        $prefix = $ticket->replyPrefix();
        $msg = "{$prefix} Kami akan menghubungi Anda di nomor Anda pada: {$datetime}. Harap ditunggu. Terimakasih.";

        $result = $this->wa->sendText($ticket->remote_number, $msg, 'JADWAL-KONSUL', $userId);

        if ($result['ok']) {
            $ticket->update(['sent_at' => now(), 'status' => 'sent']);
        }

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => $this->formatTicket($ticket->fresh('assignee')),
        ];
    }

    /**
     * Reject a live konsultasi request.
     */
    public function rejectCall(int $ticketId, string $reason, int $userId): array
    {
        $ticket = WaCarakaTicket::findOrFail($ticketId);

        $ticket->update([
            'reject_reason' => $reason,
            'status'        => 'closed',
            'assigned_to'   => $userId,
        ]);

        // Notify user
        $msg = "*WA Notifikasi PA Semarang* _Assalamualaikum wr. wb._ Berdasarkan *Permohonan Konsultasi Langsung* Anda sebelumnya dengan ini kami sampaikan permintaan maaf karena kami tidak dapat memenuhi permintaan tersebut dengan alasan {$reason}. Terimakasih.";

        $this->wa->sendText($ticket->remote_number, $msg, 'TOLAK-KONSUL', $userId);

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => $this->formatTicket($ticket->fresh('assignee')),
        ];
    }

    /**
     * Format a ticket for API response.
     */
    protected function formatTicket(WaCarakaTicket $ticket): array
    {
        return [
            'id'              => $ticket->id,
            'type'            => $ticket->type,
            'remoteNumber'    => $ticket->remote_number,
            'message'         => $ticket->message,
            'reply'           => $ticket->reply,
            'status'          => $ticket->status,
            'attachmentPath'  => $ticket->attachment_path,
            'assignee'        => $ticket->assignee
                ? ($ticket->assignee->alias ?: $ticket->assignee->name)
                : null,
            'rejectReason'    => $ticket->reject_reason,
            'scheduledCallAt' => $ticket->scheduled_call_at
                ? $ticket->scheduled_call_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                : null,
            'createdAt'       => $ticket->created_at
                ? $ticket->created_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                : null,
            'repliedAt'       => $ticket->replied_at
                ? $ticket->replied_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                : null,
            'sentAt'          => $ticket->sent_at
                ? $ticket->sent_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                : null,
        ];
    }
}
