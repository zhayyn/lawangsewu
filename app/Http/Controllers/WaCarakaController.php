<?php

namespace App\Http\Controllers;

use App\Models\WaCarakaConversation;
use App\Models\WaCarakaConversationMark;
use App\Models\WaCarakaDailyMetric;
use App\Models\WaCarakaHandover;
use App\Models\WaCarakaMessage;
use App\Models\WaCarakaMonthlySnapshot;
use App\Models\WaCarakaSyncRun;
use App\Models\WaCarakaTicket;
use App\Services\WaCarakaConversationService;
use App\Services\WaCarakaService;
use App\Services\WaCarakaTicketService;
use App\Support\LawangsewuPortal;
use App\Support\WaCarakaDatabase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;

class WaCarakaController extends Controller
{
    public function __construct(
        protected WaCarakaService $wa,
        protected WaCarakaConversationService $conversations,
        protected WaCarakaTicketService $tickets,
    ) {}

    public function index()
    {
        return Inertia::render('Lawangsewu/WaCaraka/Index', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'config' => [
                'baseUrl' => $this->wa->baseUrl(),
                'background' => Cache::get('wacaraka_background'),
                'maxMediaBytes' => (int) config('wa_caraka.max_media_bytes', 15 * 1024 * 1024),
            ],
            'messageStats' => $this->wa->messageStats(),
            'convoStats' => $this->conversations->stats(),
            'ticketStats' => WaCarakaTicket::ticketStats(),
            'recentTickets' => WaCarakaTicket::recent(12),
        ]);
    }

    public function proxy(Request $request, string $action)
    {
        $user = $request->user();

        if (in_array($action, ['restart', 'disconnect', 'reconnect', 'broadcast'], true) && !$this->isAdmin($user)) {
            return response()->json([
                'ok' => false,
                'error' => 'Aksi ini hanya dapat dilakukan oleh admin.',
            ], 403);
        }

        if ($action === 'clear-all-conversations' && !$user->isSuperAdmin()) {
            return response()->json([
                'ok' => false,
                'error' => 'Hapus semua inbox hanya dapat dilakukan oleh superadmin.',
            ], 403);
        }

        $result = match ($action) {
            'health' => $this->wa->health(),
            'qr' => $this->wa->qr(),
            'refresh-qr' => $this->wa->refreshQr(),
            'restart' => $this->wa->restart(),
            'disconnect' => $this->wa->disconnect(),
            'reconnect' => $this->wa->reconnect(),
            'history' => $this->wa->history(),
            'lid-mappings' => $this->wa->getLidMappings(),
            'sync-contacts' => $this->wa->syncContacts(),
            'resolve-contacts' => $this->wa->resolveContactsMeta($request->input('jids', [])),
            'stats' => ['ok' => true, 'status' => 200, 'data' => $this->wa->stats()],
            'message-stats' => ['ok' => true, 'status' => 200, 'data' => $this->wa->messageStats()],
            'convo-stats' => ['ok' => true, 'status' => 200, 'data' => $this->conversations->stats()],
            'operator-stats' => ['ok' => true, 'status' => 200, 'data' => [
                'generatedAt' => now()->toISOString(),
                'operatorStats' => $this->buildReportStats()['operatorStats'] ?? [],
            ]],
            'logs' => ['ok' => true, 'status' => 200, 'data' => $this->wa->recentLogs(50)],
            'inbox' => ['ok' => true, 'status' => 200, 'data' => [
                'conversations' => $this->inboxData($user),
            ]],
            'conversation' => $this->conversationData($request),
            'pull-inbox' => $this->wa->pullInbox($request->query('since')),
            'send-text' => $this->sendText($request),
            'reply' => $this->reply($request),
            'send-media' => $this->sendMedia($request),
            'broadcast' => $this->broadcast($request),
            'tickets' => ['ok' => true, 'status' => 200, 'data' => $this->tickets->listTickets(
                $request->query('type'),
                $request->query('status'),
                min(50, max(1, (int) $request->query('limit', 30))),
            )],
            'ticket-stats' => ['ok' => true, 'status' => 200, 'data' => WaCarakaTicket::ticketStats()],
            'ticket-reply' => $this->ticketReply($request),
            'ticket-send' => $this->ticketSend($request),
            'ticket-transfer' => $this->ticketTransfer($request),
            'ticket-close' => $this->ticketClose($request),
            'request-handover' => $this->requestHandover($request),
            'approve-handover' => $this->approveHandover($request),
            'reject-handover' => $this->rejectHandover($request),
            'force-handover' => $this->forceHandover($request),
            'close' => $this->closeConversation($request),
            'reopen' => $this->reopenConversation($request),
            'mark-read' => $this->markRead($request),
            'mark-customer' => $this->markCustomer($request),
            'unmark-customer' => $this->unmarkCustomer($request),
            'delete-message' => $this->deleteMessage($request),
            'clear-conversation' => $this->clearConversation($request),
            'clear-all-conversations' => $this->clearAllConversations(),
            'reset-state' => $this->resetState(),
            'get-handover-enabled' => ['ok' => true, 'status' => 200, 'data' => [
                'handoverEnabled' => Cache::get('wacaraka_handover_enabled', true),
            ]],
            'toggle-handover-enabled' => $this->toggleHandoverEnabled($request),
            'toggle-pin' => $this->togglePin($request),
            default => ['ok' => false, 'status' => 404, 'error' => 'Aksi tidak valid.'],
        };

        if (!($result['ok'] ?? false)) {
            return response()->json([
                'ok' => false,
                'error' => $result['error'] ?? 'Terjadi kesalahan.',
                'detail' => $result['detail'] ?? null,
            ], $result['status'] ?? 502);
        }

        return response()->json($result['data'] ?? ['ok' => true], $result['status'] ?? 200);
    }

    public function downloadMedia(Request $request, string $path)
    {
        if (!preg_match('/^([a-f0-9]{32,})/i', $path, $matches)) {
            abort(404);
        }

        $token = strtolower($matches[1]);
        $runtimePath = '/internal/media/' . $path;

        /** @var HttpResponse $response */
        $response = Http::timeout((int) config('wa_caraka.timeout', 20))
            ->withHeaders($this->runtimeHeaders())
            ->get(rtrim($this->wa->baseUrl(), '/') . $runtimePath);

        if ($response->failed()) {
            abort($response->status() === 404 ? 404 : 502);
        }

        // Ambil nama file dari query parameter 'fn', lalu path, lalu token
        $fileName = $request->query('fn');
        if (!$fileName) {
            $fileName = basename($path);
            if (!$fileName || $fileName === $token) {
                $fileName = $token;
            }
        }

        // Tentukan MIME type: prioritaskan dari runtime, lalu inferensi dari ekstensi
        $contentType = $response->header('Content-Type', '');
        if (!$contentType || $contentType === 'application/octet-stream') {
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $contentType = match ($ext) {
                'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'doc'   => 'application/msword',
                'xls'   => 'application/vnd.ms-excel',
                'ppt'   => 'application/vnd.ms-powerpoint',
                'pdf'   => 'application/pdf',
                'txt'   => 'text/plain',
                'csv'   => 'text/csv',
                'mp3'   => 'audio/mpeg',
                'ogg', 'oga' => 'audio/ogg',
                'm4a'   => 'audio/mp4',
                'mp4'   => 'video/mp4',
                'jpg', 'jpeg' => 'image/jpeg',
                'png'   => 'image/png',
                'gif'   => 'image/gif',
                'webp'  => 'image/webp',
                default => 'application/octet-stream',
            };
        }

        return response($response->body(), 200, [
            'Content-Type'        => $contentType,
            'Content-Length'      => $response->header('Content-Length'),
            'Content-Disposition' => 'attachment; filename="' . addslashes($fileName) . '"',
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }


    public function reports()
    {
        return Inertia::render('Lawangsewu/WaCaraka/Reports', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'authUser' => request()->user(),
            'reportStats' => $this->buildReportStats(),
        ]);
    }

    public function reportsData()
    {
        return response()->json($this->buildReportStats());
    }

    public function reportsPdf()
    {
        $report = $this->buildReportStats();
        $authUser = request()->user();
        $generatedAtLabel = now()->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') . ' WIB';

        $pdf = Pdf::loadView('pdf.wa-caraka-report', compact('report', 'authUser', 'generatedAtLabel'));

        return $pdf->download('wa-caraka-report-' . now()->format('Ymd-His') . '.pdf');
    }

    private function sendText(Request $request): array
    {
        $validated = $request->validate([
            'to' => 'required_without:conversation_id|string|min:8|max:32',
            'conversation_id' => 'nullable|string|max:255',
            'text' => 'required|string|max:4096',
        ]);

        $to = $validated['to'] ?? $this->remoteNumberFromConversationId((string) ($validated['conversation_id'] ?? ''));

        if (!$to) {
            return ['ok' => false, 'status' => 422, 'error' => 'Nomor tujuan tidak ditemukan.'];
        }

        return $this->wa->sendText(
            $to,
            $validated['text'],
            $this->senderLabel($request->user()),
            $request->user()->id,
        );
    }

    private function reply(Request $request): array
    {
        $validated = $request->validate([
            'conversation_id' => 'required|string|max:255',
            'text'            => 'required|string|max:4096',
        ]);

        // Cari conversation berdasarkan conversation_id
        $conversation = WaCarakaConversation::query()
            ->where('conversation_id', $validated['conversation_id'])
            ->first();

        if (!$conversation) {
            return ['ok' => false, 'status' => 404, 'error' => 'Percakapan tidak ditemukan.'];
        }

        $remoteNumber = $conversation->remote_number;

        if (!$remoteNumber) {
            return ['ok' => false, 'status' => 422, 'error' => 'Nomor tujuan tidak ditemukan di percakapan.'];
        }

        // Kirim pesan via service (langsung pakai remote_number, tidak perlu inboundMessage)
        $result = $this->wa->sendText(
            $remoteNumber,
            $validated['text'],
            $this->senderLabel($request->user()),
            $request->user()->id,
        );

        if (!($result['ok'] ?? false)) {
            return $result;
        }

        // Tandai SEMUA pesan inbound yang belum dibalas di percakapan ini sebagai replied
        WaCarakaMessage::query()
            ->where('conversation_id', $validated['conversation_id'])
            ->where('direction', 'inbound')
            ->whereNull('replied_at')
            ->update(['replied_at' => now()]);

        $conversation->refresh();

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => [
                'ok'           => true,
                'conversation' => $this->formatConversation(
                    $conversation->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']),
                    $request->user()
                ),
            ],
        ];
    }

    private function sendMedia(Request $request): array
    {
        $maxBytes = (int) config('wa_caraka.max_media_bytes', 15 * 1024 * 1024);

        $validated = $request->validate([
            'to' => 'required_without:conversation_id|string|min:8|max:32',
            'conversation_id' => 'nullable|string|max:255',
            'media_kind' => 'required|string|in:image,sticker,video,audio,document',
            'media_url' => 'required_without:media_file|nullable|string',
            'media_file' => 'required_without:media_url|nullable|file|max:' . max(1, (int) ceil($maxBytes / 1024)),
            'mime_type' => 'nullable|string|max:255',
            'file_name' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:4096',
            'ptt' => 'nullable|boolean',
        ]);

        if ($request->hasFile('media_file')) {
            $uploaded = $request->file('media_file');
            $fileBytes = (int) $uploaded->getSize();

            if ($fileBytes > $maxBytes) {
                return [
                    'ok' => false,
                    'status' => 422,
                    'error' => 'Ukuran file melebihi batas maksimum ' . max(1, (int) round($maxBytes / (1024 * 1024))) . ' MB.',
                ];
            }

            $mimeType = $validated['mime_type'] ?? $uploaded->getMimeType() ?? 'application/octet-stream';
            $fileName = $validated['file_name'] ?? $uploaded->getClientOriginalName();

            $validated['media_url'] = sprintf(
                'data:%s;base64,%s',
                $mimeType,
                base64_encode((string) file_get_contents($uploaded->getRealPath())),
            );
            $validated['mime_type'] = $mimeType;
            $validated['file_name'] = $fileName;
        }

        $payloadBytes = $this->mediaPayloadBytes((string) $validated['media_url']);

        if ($payloadBytes > $maxBytes) {
            return [
                'ok' => false,
                'status' => 422,
                'error' => 'Ukuran file melebihi batas maksimum ' . max(1, (int) round($maxBytes / (1024 * 1024))) . ' MB.',
            ];
        }

        $to = $validated['to'] ?? $this->remoteNumberFromConversationId((string) ($validated['conversation_id'] ?? ''));

        if (!$to) {
            return ['ok' => false, 'status' => 422, 'error' => 'Nomor tujuan tidak ditemukan.'];
        }

        return $this->wa->sendMedia(
            $to,
            $validated,
            $this->senderLabel($request->user()),
            $request->user()->id,
        );
    }

    private function broadcast(Request $request): array
    {
        $validated = $request->validate([
            'recipients' => 'required|array|min:1|max:' . $this->wa->broadcastLimit(),
            'recipients.*' => 'required|string|min:8|max:32',
            'text' => 'required|string|max:4096',
        ]);

        return $this->wa->broadcastText(
            $validated['recipients'],
            $validated['text'],
            $this->senderLabel($request->user()),
            $request->user()->id,
        );
    }

    private function conversationData(Request $request): array
    {
        $validated = $request->validate([
            'conversation_id' => 'required|string|max:255',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'messages' => $this->wa->conversationMessages(
                    $validated['conversation_id'],
                    (int) ($validated['limit'] ?? 50),
                    true,
                ),
            ],
        ];
    }

    private function ticketReply(Request $request): array
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer',
            'reply' => 'required|string|max:4096',
        ]);

        return $this->tickets->replyTicket($validated['ticket_id'], $validated['reply'], null, $request->user()->id);
    }

    private function ticketSend(Request $request): array
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer',
        ]);

        return $this->tickets->sendTicketReply($validated['ticket_id'], $request->user()->id);
    }

    private function ticketTransfer(Request $request): array
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer',
            'new_type' => 'required|string',
        ]);

        return $this->tickets->transferTicket($validated['ticket_id'], $validated['new_type']);
    }

    private function ticketClose(Request $request): array
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer',
        ]);

        return $this->tickets->closeTicket($validated['ticket_id']);
    }

    private function requestHandover(Request $request): array
    {
        $validated = $request->validate([
            'conversation_id' => 'required|string',
            'reason' => 'nullable|string|max:500',
        ]);

        if (!Cache::get('wacaraka_handover_enabled', true)) {
            return ['ok' => false, 'status' => 403, 'error' => 'Fitur alih chat sedang dinonaktifkan.'];
        }

        $conversation = $this->findConversationOrFail($validated['conversation_id']);
        $result = $this->conversations->requestHandover($conversation, $request->user(), $validated['reason'] ?? null);

        return $result['ok']
            ? ['ok' => true, 'status' => 200, 'data' => ['ok' => true]]
            : ['ok' => false, 'status' => 422, 'error' => $result['error'] ?? 'Gagal meminta handover.'];
    }

    private function approveHandover(Request $request): array
    {
        $validated = $request->validate(['handover_id' => 'required|integer']);
        $handover = WaCarakaHandover::query()->with('conversation')->findOrFail($validated['handover_id']);
        $result = $this->conversations->approveHandover($handover, $request->user());

        return $result['ok']
            ? ['ok' => true, 'status' => 200, 'data' => ['ok' => true]]
            : ['ok' => false, 'status' => 403, 'error' => $result['error'] ?? 'Gagal menyetujui handover.'];
    }

    private function rejectHandover(Request $request): array
    {
        $validated = $request->validate(['handover_id' => 'required|integer']);
        $handover = WaCarakaHandover::query()->with('conversation')->findOrFail($validated['handover_id']);
        $result = $this->conversations->rejectHandover($handover, $request->user());

        return $result['ok']
            ? ['ok' => true, 'status' => 200, 'data' => ['ok' => true]]
            : ['ok' => false, 'status' => 403, 'error' => $result['error'] ?? 'Gagal menolak handover.'];
    }

    private function forceHandover(Request $request): array
    {
        $validated = $request->validate(['conversation_id' => 'required|string']);
        $conversation = $this->findConversationOrFail($validated['conversation_id']);
        $result = $this->conversations->forceHandover($conversation, $request->user());

        return $result['ok']
            ? ['ok' => true, 'status' => 200, 'data' => ['ok' => true]]
            : ['ok' => false, 'status' => 403, 'error' => $result['error'] ?? 'Gagal melakukan force handover.'];
    }

    private function closeConversation(Request $request): array
    {
        $validated = $request->validate(['conversation_id' => 'required|string']);
        $conversation = $this->findConversationOrFail($validated['conversation_id']);
        $result = $this->conversations->closeConversation($conversation, $request->user());

        if ($result['ok']) {
            // Tandai semua pesan inbound yang belum dibalas sebagai 'selesai'
            // agar tidak masuk ke hitungan "Pesan Masuk Baru"
            WaCarakaMessage::query()
                ->where('conversation_id', $conversation->conversation_id)
                ->where('direction', 'inbound')
                ->whereNull('replied_at')
                ->update(['replied_at' => now()]);

            // Kirim pesan macro penutup SETELAH response dikembalikan ke browser
            // (fire-and-forget) agar operator tidak perlu menunggu runtime WA.
            if ($conversation->remote_number) {
                $macroText = "Baik, jika tidak ada pertanyaan lagi, kami tutup percakapan ini. Terima kasih,\n\nوَالسَّلَامُ عَلَيْكُمْ وَرَحْمَةُ اللَّهِ وَبَرَكَاتُهُ";
                $remoteNumber  = $conversation->remote_number;
                $senderLabel   = $this->senderLabel($request->user());
                $userId        = $request->user()->id;
                $waService     = $this->wa;
                app()->terminating(static function () use ($waService, $remoteNumber, $macroText, $senderLabel, $userId) {
                    try {
                        $waService->sendText($remoteNumber, $macroText, $senderLabel, $userId);
                    } catch (\Throwable) {
                        // Silent — jangan crash setelah response dikirim
                    }
                });
            }
        }

        return $result['ok']
            ? ['ok' => true, 'status' => 200, 'data' => ['ok' => true]]
            : ['ok' => false, 'status' => 403, 'error' => $result['error'] ?? 'Gagal menutup percakapan.'];
    }

    private function reopenConversation(Request $request): array
    {
        if (!$request->user()->isSuperAdmin()) {
            return ['ok' => false, 'status' => 403, 'error' => 'Hanya superadmin yang dapat membuka ulang percakapan secara manual.'];
        }

        $validated = $request->validate(['conversation_id' => 'required|string']);
        $conversation = $this->findConversationOrFail($validated['conversation_id']);
        
        $this->conversations->reopenConversation($conversation);

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function markRead(Request $request): array
    {
        $validated = $request->validate(['conversation_id' => 'required|string']);
        $conversation = $this->findConversationOrFail($validated['conversation_id']);
        $conversation->markRead();

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function markCustomer(Request $request): array
    {
        $validated = $request->validate([
            'conversation_id' => 'required|string',
            'label' => 'required|string|max:60',
            'tone' => 'nullable|string|max:32',
            'note' => 'nullable|string|max:500',
            'is_pinned' => 'nullable|boolean',
        ]);

        $conversation = $this->findConversationOrFail($validated['conversation_id']);

        $mark = WaCarakaConversationMark::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'wa_caraka_conversation_id' => $conversation->id,
            ],
            [
                'label' => $validated['label'],
                'tone' => $validated['tone'] ?? 'amber',
                'note' => $validated['note'] ?? null,
                'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            ],
        );

        return ['ok' => true, 'status' => 200, 'data' => [
            'mark' => $this->formatMark($mark),
        ]];
    }

    private function unmarkCustomer(Request $request): array
    {
        $validated = $request->validate(['conversation_id' => 'required|string']);
        $conversation = $this->findConversationOrFail($validated['conversation_id']);

        WaCarakaConversationMark::query()
            ->where('user_id', $request->user()->id)
            ->where('wa_caraka_conversation_id', $conversation->id)
            ->delete();

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function togglePin(Request $request): array
    {
        $validated = $request->validate([
            'conversation_id' => 'required|string',
        ]);

        $conversation = $this->findConversationOrFail($validated['conversation_id']);

        $mark = WaCarakaConversationMark::query()->firstOrNew([
            'user_id' => $request->user()->id,
            'wa_caraka_conversation_id' => $conversation->id,
        ]);

        if (!$mark->exists) {
            $mark->label = 'Sematkan';
            $mark->tone = 'sky';
        }

        $mark->is_pinned = !$mark->is_pinned;
        $mark->save();

        return ['ok' => true, 'status' => 200, 'data' => [
            'isPinned' => (bool) $mark->is_pinned,
            'mark' => $this->formatMark($mark),
        ]];
    }

    private function deleteMessage(Request $request): array
    {
        $validated = $request->validate(['message_id' => 'required|integer']);
        WaCarakaMessage::query()->whereKey($validated['message_id'])->delete();

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function clearConversation(Request $request): array
    {
        $validated = $request->validate(['conversation_id' => 'required|string']);
        $conversation = $this->findConversationOrFail($validated['conversation_id']);

        WaCarakaDatabase::transaction(function () use ($conversation) {
            WaCarakaConversationMark::query()
                ->where('wa_caraka_conversation_id', $conversation->id)
                ->delete();

            WaCarakaHandover::query()
                ->where('conversation_id', $conversation->id)
                ->delete();

            WaCarakaMessage::query()
                ->where('conversation_id', $conversation->conversation_id)
                ->delete();

            $conversation->delete();
        });

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    /**
     * Hapus seluruh isi inbox: semua percakapan, pesan, mark, dan handover.
     * Juga membersihkan history di runtime WA agar pull-inbox berikutnya
     * tidak mengisi ulang percakapan yang baru dihapus.
     * Operasi destruktif — hanya superadmin yang diizinkan.
     */
    private function clearAllConversations(): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_conversations')) {
            return ['ok' => true, 'status' => 200, 'data' => ['ok' => true, 'deleted' => 0]];
        }

        $deleted = 0;

        WaCarakaDatabase::transaction(function () use (&$deleted) {
            WaCarakaConversationMark::query()->delete();
            WaCarakaHandover::query()->delete();
            WaCarakaMessage::query()->delete();
            $deleted = WaCarakaConversation::query()->delete();

            // Hapus juga sync runs agar history-sync tidak dilanjutkan
            if (WaCarakaDatabase::hasTable('wa_caraka_sync_runs')) {
                WaCarakaSyncRun::query()->delete();
            }
        });

        // Coba hapus juga history di runtime WA (Node.js) — non-blocking.
        // Jika runtime tidak support endpoint ini, diabaikan saja.
        try {
            $this->wa->clearHistory();
        } catch (\Throwable) {
            // Silent — runtime mungkin tidak support /history/clear
        }

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true, 'deleted' => $deleted]];
    }

    private function resetState(): array
    {
        $now = now();

        if (WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            WaCarakaMessage::query()
                ->where('direction', 'inbound')
                ->whereNull('replied_at')
                ->update(['replied_at' => $now]);
        }

        if (WaCarakaDatabase::hasTable('wa_caraka_conversations')) {
            WaCarakaConversation::query()->update(['unread_count' => 0]);
            WaCarakaConversation::query()
                ->where('status', '!=', 'closed')
                ->update(['status' => 'open']);
        }

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true]];
    }

    private function toggleHandoverEnabled(Request $request): array
    {
        if (!$request->user()->isSuperAdmin()) {
            return ['ok' => false, 'status' => 403, 'error' => 'Hanya superadmin yang dapat mengubah pengaturan ini.'];
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        Cache::forever('wacaraka_handover_enabled', (bool) $validated['enabled']);

        return ['ok' => true, 'status' => 200, 'data' => [
            'ok' => true,
            'handoverEnabled' => (bool) $validated['enabled'],
        ]];
    }

    private function inboxData($user): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_conversations')) {
            return [];
        }

        $latestMessages = WaCarakaMessage::query()
            ->with('user:id,name,alias')
            ->whereIn('id', function ($query) {
                $query->from('wa_caraka_messages')
                    ->selectRaw('MAX(id)')
                    ->groupBy('conversation_id');
            })
            ->get()
            ->keyBy('conversation_id');

        $marks = WaCarakaConversationMark::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('wa_caraka_conversation_id');

        $excludeNumbers = ['engine-health-check', 'tokenless-route-check', 'status@broadcast', 'health-check', 'health_check'];

        $rows = WaCarakaConversation::query()
            ->whereNotIn('remote_number', $excludeNumbers)
            ->with(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias'])
            ->orderByDesc('last_activity_at')
            ->get();

        $deduped = $rows
            ->groupBy(fn (WaCarakaConversation $conversation) => $conversation->resolved_number ?: WaCarakaMessage::normalizeRemoteNumber((string) $conversation->remote_number))
            ->map(function (Collection $group) use ($latestMessages) {
                return $group->sortByDesc(function (WaCarakaConversation $conversation) use ($latestMessages) {
                    $message = $latestMessages->get($conversation->conversation_id);
                    return optional($message?->created_at)->timestamp ?? optional($conversation->last_activity_at)->timestamp ?? 0;
                })->first();
            })
            ->sortByDesc(function (WaCarakaConversation $conversation) use ($marks) {
                $mark = $marks->get($conversation->id);
                return (bool) ($mark?->is_pinned ?? false);
            })
            ->values();

        // ── Batch-resolve profiles dari server runtime (server .33) ────────────
        // Hanya resolve kontak yang profile-nya belum ada atau sudah expire (>23 jam)
        $profileCacheTtl = 23 * 3600;
        $needsResolve = $deduped->filter(function (WaCarakaConversation $c) use ($profileCacheTtl) {
            if (!$c->profile_synced_at) return true;
            return $c->profile_synced_at->diffInSeconds(now()) > $profileCacheTtl;
        });

        if ($needsResolve->isNotEmpty()) {
            $jids = $needsResolve->pluck('remote_number')->filter()->values()->all();
            try {
                $resolved = $this->wa->resolveContactsMeta($jids);
                $resolvedItems = collect($resolved['data']['items'] ?? []);

                foreach ($needsResolve as $convo) {
                    $item = $resolvedItems->firstWhere('jid', $convo->remote_number);
                    if (!$item) continue;

                    $updatePayload = [
                        'profile_synced_at' => now(),
                    ];

                    // Update foto profil (bisa null jika privat)
                    if (array_key_exists('profilePhotoUrl', $item)) {
                        $updatePayload['profile_photo_url'] = $item['profilePhotoUrl'];
                    }

                    // Simpan nomor HP terresolve dari @lid → nomor WA nyata
                    $resolvedJid = $item['resolvedJid'] ?? null;
                    if ($resolvedJid && $resolvedJid !== $convo->remote_number) {
                        $resolvedPhone = preg_replace('/@s\.whatsapp\.net$/i', '', $resolvedJid);
                        $updatePayload['resolved_number'] = $resolvedPhone;
                    }

                    // Update display name jika belum ada atau lebih baik
                    $displayName = $item['displayName'] ?? null;
                    if ($displayName && (!$convo->remote_name || is_numeric($convo->remote_name))) {
                        $updatePayload['remote_name'] = $displayName;
                        $convo->remote_name = $displayName;
                    }

                    $convo->update($updatePayload);
                    $convo->profile_photo_url = $updatePayload['profile_photo_url'] ?? $convo->profile_photo_url;
                    $convo->resolved_number = $updatePayload['resolved_number'] ?? $convo->resolved_number;
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[WaCaraka] Batch profile resolve failed', ['error' => $e->getMessage()]);
            }
        }

        return $deduped->map(function (WaCarakaConversation $conversation) use ($latestMessages, $marks, $user) {
            $formatted = $this->formatConversation($conversation, $user);
            $message = $latestMessages->get($conversation->conversation_id);
            $mark = $marks->get($conversation->id);

            return array_merge($formatted, [
                'lastMessagePreview' => $message ? (trim((string) $message->message_text) !== '' ? $message->message_text : $this->messageFallback($message)) : 'Belum ada pesan',
                'lastMessageType' => $message?->message_type ?? 'text',
                'lastMessageDirection' => $message?->direction ?? 'inbound',
                'lastActivityTs' => $message?->created_at?->timestamp ?? $conversation->last_activity_at?->timestamp ?? 0,
                'lastMessageMedia' => $message ? $this->lastMessageMedia($message) : null,
                'customerMark' => $mark ? $this->formatMark($mark) : null,
                'hasUnreplied' => $message ? ($message->direction === 'inbound' && is_null($message->replied_at)) : false,
            ]);
        })->all();
    }

    private function formatConversation(WaCarakaConversation $conversation, $user): array
    {
        $conversation->loadMissing(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']);

        $ownerPresence = null;
        if ($conversation->claimed_by && $conversation->last_activity_at) {
            $ownerPresence = $conversation->last_activity_at->gte(now()->subMinutes(2))
                ? 'active'
                : ($conversation->last_activity_at->gte(now()->subMinutes(10)) ? 'standby' : 'idle');
        }

        return [
            'conversationId'  => $conversation->conversation_id,
            'remoteNumber'    => $conversation->remote_number,
            'remoteName'      => $conversation->remote_name,
            // Nomor HP terresolve dari @lid (null untuk nomor biasa)
            'resolvedNumber'  => $conversation->resolved_number,
            // Foto profil WA — sudah di-cache di DB, aman dikirim langsung
            'profilePhotoUrl' => $conversation->profile_photo_url,
            'status'          => $conversation->status,
            'unreadCount'     => (int) $conversation->unread_count,
            'lastActivityAt'  => $conversation->last_activity_at?->diffForHumans(),
            'claimedAt'       => $conversation->claimed_at?->diffForHumans(),
            'ownership'       => !$conversation->claimed_by ? 'unclaimed' : ($conversation->claimed_by === $user->id ? 'mine' : 'locked'),
            'ownerPresence'   => $ownerPresence,
            'justClaimed'     => (bool) $conversation->claimed_at?->gte(now()->subMinutes(3)),
            'canReply'        => true,
            'owner'           => $conversation->owner ? [
                'id'    => $conversation->owner->id,
                'name'  => $conversation->owner->name,
                'alias' => $conversation->owner->alias,
            ] : null,
            'pendingHandover' => $conversation->pendingHandover ? [
                'id'        => $conversation->pendingHandover->id,
                'requestor' => [
                    'id'   => $conversation->pendingHandover->requestor?->id,
                    'name' => $conversation->pendingHandover->requestor?->name,
                ],
            ] : null,
        ];
    }

    private function formatMark(WaCarakaConversationMark $mark): array
    {
        return [
            'label' => $mark->label,
            'tone' => $mark->tone,
            'note' => $mark->note,
            'isPinned' => (bool) $mark->is_pinned,
        ];
    }

    private function lastMessageMedia(WaCarakaMessage $message): ?array
    {
        $media = is_array($message->metadata['media'] ?? null) ? $message->metadata['media'] : null;
        if (!$media) {
            return null;
        }

        $mime = $media['mimetype'] ?? null;
        $kind = $media['kind'] ?? $message->message_type;
        $url = $media['dataUrl'] ?? $media['url'] ?? null;

        return [
            'kind' => $kind,
            'url' => $url,
            'fileName' => $media['fileName'] ?? null,
            'mimeType' => $mime,
            'hasVisualPreview' => (bool) ($url && (in_array($kind, ['image', 'sticker'], true) || Str::startsWith((string) $mime, 'image/'))),
        ];
    }

    private function messageFallback(WaCarakaMessage $message): string
    {
        return match ($message->message_type) {
            'image' => '[image]',
            'sticker' => '[sticker]',
            'document' => '[document]',
            'video' => '[video]',
            'audio' => '[audio]',
            default => '[pesan kosong]',
        };
    }

    private function buildReportStats(): array
    {
        $today = now()->toDateString();
        $weekStart = now()->subDays(6)->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $dailyMetrics = WaCarakaDatabase::hasTable('wa_caraka_daily_metrics')
            ? WaCarakaDailyMetric::query()->orderBy('metric_date')->get()
            : collect();

        $dailyByDate = $dailyMetrics->keyBy('metric_date');

        $summary = [
            'inboundToday' => (int) optional($dailyByDate->get($today))->inbound_messages,
            'inboundWeek' => (int) $dailyMetrics->where('metric_date', '>=', $weekStart)->sum('inbound_messages'),
            'inboundMonth' => (int) $dailyMetrics->where('metric_date', '>=', $monthStart)->sum('inbound_messages'),
            'activeConversations' => WaCarakaDatabase::hasTable('wa_caraka_conversations')
                ? WaCarakaConversation::query()->whereIn('status', ['pending', 'open'])->count()
                : 0,
        ];

        $dailyInbound = collect(range(6, 0))
            ->map(function ($offset) use ($dailyByDate) {
                $date = now()->subDays($offset)->toDateString();
                return [
                    'label' => Carbon::parse($date)->translatedFormat('d M'),
                    'count' => (int) optional($dailyByDate->get($date))->inbound_messages,
                ];
            })->all();

        $weeklyInbound = collect(range(3, 0))
            ->map(function ($offset) {
                $start = now()->startOfWeek()->subWeeks($offset);
                $end = $start->copy()->endOfWeek();
                $count = WaCarakaDatabase::hasTable('wa_caraka_daily_metrics')
                    ? WaCarakaDailyMetric::query()
                        ->whereBetween('metric_date', [$start->toDateString(), $end->toDateString()])
                        ->sum('inbound_messages')
                    : 0;

                return [
                    'label' => $start->translatedFormat('d M'),
                    'count' => (int) $count,
                ];
            })->all();

        $monthlyInbound = collect(range(11, 0))
            ->map(function ($offset) {
                $month = now()->startOfMonth()->subMonths($offset);
                $count = WaCarakaDatabase::hasTable('wa_caraka_daily_metrics')
                    ? WaCarakaDailyMetric::query()
                        ->whereBetween('metric_date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
                        ->sum('inbound_messages')
                    : 0;

                return [
                    'label' => $month->translatedFormat('M Y'),
                    'count' => (int) $count,
                ];
            })->all();

        $latestSync = WaCarakaDatabase::hasTable('wa_caraka_sync_runs')
            ? WaCarakaSyncRun::query()->latest('updated_at')->first()
            : null;

        $messageTypes = WaCarakaDatabase::hasTable('wa_caraka_messages')
            ? WaCarakaMessage::query()
                ->selectRaw('message_type, COUNT(*) as aggregate')
                ->groupBy('message_type')
                ->orderByDesc('aggregate')
                ->limit(6)
                ->get()
                ->map(fn ($row) => [
                    'type' => $row->message_type ?: 'unknown',
                    'label' => ucfirst((string) ($row->message_type ?: 'unknown')),
                    'count' => (int) $row->aggregate,
                ])->all()
            : [];

        $topContacts = WaCarakaDatabase::hasTable('wa_caraka_messages')
            ? WaCarakaMessage::query()
                ->selectRaw('remote_number, COUNT(*) as aggregate')
                ->groupBy('remote_number')
                ->orderByDesc('aggregate')
                ->limit(6)
                ->get()
                ->map(fn ($row) => [
                    'name' => $row->remote_number,
                    'messages' => (int) $row->aggregate,
                ])->all()
            : [];

        $operatorStats = WaCarakaDatabase::hasTable('wa_caraka_messages')
            ? WaCarakaMessage::query()
                ->with('user:id,name,alias')
                ->where('direction', 'outbound')
                ->whereNotNull('user_id')
                ->get()
                ->groupBy('user_id')
                ->map(function (Collection $messages) {
                    $user = $messages->first()?->user;
                    return [
                        'name' => $user ? ($user->alias ?: $user->name) : 'Operator',
                        'conversations' => $messages->pluck('conversation_id')->filter()->unique()->count(),
                        'outboundMessages' => $messages->count(),
                    ];
                })
                ->sortByDesc('outboundMessages')
                ->values()
                ->take(8)
                ->all()
            : [];

        $snapshots = WaCarakaDatabase::hasTable('wa_caraka_monthly_snapshots')
            ? WaCarakaMonthlySnapshot::query()
                ->orderByDesc('month_key')
                ->limit(12)
                ->get()
                ->map(fn (WaCarakaMonthlySnapshot $snapshot) => [
                    'monthKey' => $snapshot->month_key,
                    'label' => Carbon::parse($snapshot->month_start)->translatedFormat('M Y'),
                    'totalMessages' => (int) $snapshot->total_messages,
                    'inboundMessages' => (int) $snapshot->inbound_messages,
                    'outboundMessages' => (int) $snapshot->outbound_messages,
                    'historyMessages' => (int) $snapshot->history_messages,
                    'realtimeMessages' => (int) $snapshot->realtime_messages,
                    'activeConversations' => (int) $snapshot->active_conversations,
                    'uniqueContacts' => (int) $snapshot->unique_contacts,
                    'topOperatorName' => $snapshot->top_operator_name,
                ])
                ->values()
            : collect();

        $latestSnapshot = $snapshots->first();

        return [
            'generatedAt' => now()->toISOString(),
            'summary' => $summary,
            'dailyInbound' => $dailyInbound,
            'weeklyInbound' => $weeklyInbound,
            'monthlyInbound' => $monthlyInbound,
            'historySync' => [
                'latest' => $latestSync ? [
                    'status' => $latestSync->status,
                    'messagesReceived' => (int) $latestSync->messages_received,
                    'messagesImported' => (int) $latestSync->messages_imported,
                    'messagesDuplicate' => (int) $latestSync->messages_duplicate,
                    'progress' => (int) $latestSync->progress,
                    'updatedAt' => $latestSync->updated_at?->toISOString(),
                ] : null,
            ],
            'messageTypes' => $messageTypes,
            'topContacts' => $topContacts,
            'operatorStats' => $operatorStats,
            'executiveSummary' => [
                'totalMessages' => WaCarakaDatabase::hasTable('wa_caraka_messages') ? WaCarakaMessage::query()->count() : 0,
                'totalConversations' => WaCarakaDatabase::hasTable('wa_caraka_conversations') ? WaCarakaConversation::query()->count() : 0,
            ],
            'snapshots' => [
                'latest' => $latestSnapshot,
                'timeline' => $snapshots->all(),
            ],
        ];
    }

    private function runtimeHeaders(): array
    {
        $token = (string) config('wa_caraka.token', env('LW_WA_V2_TOKEN', ''));

        return $token !== '' ? ['X-WA-V2-Token' => $token] : [];
    }

    private function mediaPayloadBytes(string $dataUrl): int
    {
        if (preg_match('/^data:[^;]+;base64,(.*)$/s', $dataUrl, $matches)) {
            $decoded = base64_decode($matches[1], true);
            return $decoded === false ? PHP_INT_MAX : strlen($decoded);
        }

        return strlen($dataUrl);
    }

    private function remoteNumberFromConversationId(string $conversationId): ?string
    {
        return WaCarakaConversation::query()
            ->where('conversation_id', $conversationId)
            ->value('remote_number');
    }

    private function senderLabel($user): string
    {
        return $user->alias ?: $user->name ?: $user->email ?: 'operator';
    }

    private function isAdmin($user): bool
    {
        return $user->role === 'admin' || $user->isSuperAdmin();
    }

    private function findConversationOrFail(string $conversationId): WaCarakaConversation
    {
        return WaCarakaConversation::query()
            ->where('conversation_id', $conversationId)
            ->firstOrFail();
    }
}
