<?php

namespace App\Services;

use App\Events\WaCarakaConversationUpdated;
use App\Events\WaCarakaMessageReceived;
use App\Events\WaCarakaMessageSynced;
use App\Jobs\SendWaCarakaOutboundMessage;
use App\Models\WaCarakaConversation;
use App\Models\WaCarakaLog;
use App\Models\WaCarakaMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * WaCarakaService
 *
 * Full-stack messaging gateway service for the WA Caraka module.
 * Handles runtime communication (Node.js/Baileys), message logging,
 * inbound webhook processing, inbox queries, and broadcast.
 *
 * SSO is handled at the controller/middleware layer.
 */
class WaCarakaService
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;
    protected int $broadcastLimit;

    public function __construct()
    {
        $this->baseUrl        = rtrim(config('wa_caraka.base_url', env('LW_WA_V2_BASE', 'http://127.0.0.1:8790')), '/');
        $this->token          = config('wa_caraka.token', env('LW_WA_V2_TOKEN', ''));
        $this->timeout        = (int) config('wa_caraka.timeout', 20);
        $this->broadcastLimit = (int) config('wa_caraka.broadcast_limit', 50);
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function broadcastLimit(): int
    {
        return $this->broadcastLimit;
    }

    // ══════════════════════════════════════════════
    // Internal HTTP Client
    // ══════════════════════════════════════════════

    protected function request()
    {
        $builder = Http::timeout($this->timeout)->withHeaders([
            'Accept' => 'application/json',
        ]);

        if (!empty($this->token)) {
            $builder = $builder->withHeaders([
                'X-WA-V2-Token' => $this->token,
            ]);
        }

        return $builder;
    }

    protected function get(string $path, array $query = []): array
    {
        try {
            $response = $this->request()->get($this->baseUrl . $path, $query);
            return $this->wrap($response);
        } catch (\Exception $e) {
            Log::error('[WaCaraka] GET failed', ['path' => $path, 'error' => $e->getMessage()]);
            return $this->error('Gagal terhubung ke WA runtime', $e->getMessage());
        }
    }

    protected function post(string $path, array $data = []): array
    {
        try {
            $response = $this->request()->post($this->baseUrl . $path, $data);
            return $this->wrap($response);
        } catch (\Exception $e) {
            Log::error('[WaCaraka] POST failed', ['path' => $path, 'error' => $e->getMessage()]);
            return $this->error('Gagal terhubung ke WA runtime', $e->getMessage());
        }
    }

    private function wrap(\Illuminate\Http\Client\Response $response): array
    {
        return [
            'ok'     => $response->successful(),
            'status' => $response->status(),
            'data'   => $response->json(),
        ];
    }

    private function error(string $message, ?string $detail = null, int $status = 502): array
    {
        return [
            'ok'     => false,
            'status' => $status,
            'error'  => $message,
            'detail' => $detail,
        ];
    }

    // ══════════════════════════════════════════════
    // Device / Session Management
    // ══════════════════════════════════════════════

    public function health(): array
    {
        return $this->get('/health');
    }

    public function qr(): array
    {
        return $this->get('/qr');
    }

    public function refreshQr(): array
    {
        return $this->post('/refresh-qr');
    }

    public function restart(): array
    {
        return $this->post('/restart');
    }

    public function reconnect(): array
    {
        return $this->post('/reconnect');
    }

    public function disconnect(): array
    {
        return $this->post('/disconnect');
    }

    // ══════════════════════════════════════════════
    // Runtime History (from Node.js process)
    // ══════════════════════════════════════════════

    public function history(): array
    {
        return $this->get('/history');
    }

    public function clearHistory(): array
    {
        return $this->post('/history/clear');
    }

    public function getLidMappings(): array
    {
        return $this->get('/lid-mappings');
    }

    public function syncContacts(): array
    {
        $response = $this->post('/contacts/sync');

        // Some runtimes do not implement /contacts/sync yet.
        // Fallback to current lid mappings so UI action stays useful.
        if (($response['status'] ?? 0) === 404 || ($response['ok'] ?? false) === false) {
            $mappings = $this->getLidMappings();
            $pairs = $mappings['data']['pairs'] ?? [];

            return [
                'ok' => true,
                'status' => 200,
                'data' => [
                    'ok' => true,
                    'scanned' => null,
                    'learned' => 0,
                    'lidMappings' => is_array($pairs) ? count($pairs) : 0,
                    'fallback' => true,
                ],
            ];
        }

        return $response;
    }

    public function resolveContactsMeta(array $jids): array
    {
        $normalized = collect($jids)
            ->filter(fn ($jid) => is_string($jid) && trim($jid) !== '')
            ->map(fn ($jid) => trim((string) $jid))
            ->unique()
            ->values()
            ->all();

        if (empty($normalized)) {
            return [
                'ok' => true,
                'status' => 200,
                'data' => ['ok' => true, 'items' => []],
            ];
        }

        $response = $this->post('/contacts/resolve', ['jids' => $normalized]);

        if (($response['status'] ?? 0) === 404) {
            return [
                'ok' => true,
                'status' => 200,
                'data' => ['ok' => true, 'items' => [], 'fallback' => true],
            ];
        }

        return $response;
    }

    // ══════════════════════════════════════════════
    // Outbound Messaging
    // ══════════════════════════════════════════════

    /**
     * Send a single text message via runtime and log it.
     */
    public function sendText(string $to, string $text, ?string $sender = null, ?int $userId = null, ?\App\Models\User $user = null): array
    {
        return $this->sendRuntimeText($to, $text, $sender, $userId);
    }

    public function queueText(string $to, string $text, ?string $sender = null, ?int $userId = null): array
    {
        $queuedMessage = $this->createQueuedOutboundMessage($to, $text, $userId);

        if (!$queuedMessage) {
            return $this->sendRuntimeText($to, $text, $sender, $userId);
        }

        $this->dispatchMessageSynced($queuedMessage->fresh('user:id,name,alias'));

        $job = new SendWaCarakaOutboundMessage($queuedMessage->id, $sender);

        if ($this->shouldDispatchOutboundAsync()) {
            dispatch($job->onQueue(config('wa_caraka.queue', 'wa-caraka')));
        } else {
            dispatch_sync($job);
        }

        $queuedMessage = $queuedMessage->fresh('user:id,name,alias');

        return [
            'ok' => true,
            'status' => 200,
            'queued' => true,
            'data' => [
                'queuedMessageId' => $queuedMessage?->id,
                'status' => $queuedMessage?->status ?? 'queued',
                'conversationId' => $queuedMessage?->conversation_id,
            ],
        ];
    }

    public function queueBroadcastText(array $recipients, string $text, ?string $sender = null, ?int $userId = null): array
    {
        if (count($recipients) > $this->broadcastLimit) {
            return $this->error(
                sprintf('Maksimal %d penerima per broadcast.', $this->broadcastLimit),
                'Kurangi jumlah penerima lalu kirim ulang.',
                422,
            );
        }

        $results = [];

        foreach ($recipients as $to) {
            $res = $this->queueText($to, $text, $sender, $userId);
            $results[] = [
                'to' => $to,
                'ok' => $res['ok'],
                'status' => $res['data']['status'] ?? ($res['ok'] ? 'queued' : 'failed'),
                'message' => $res['error'] ?? ($res['queued'] ?? false ? 'QUEUED' : 'OK'),
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'total' => count($recipients),
                'succeeded' => count(array_filter($results, fn ($r) => in_array($r['status'], ['sent', 'queued'], true))),
                'failed' => count(array_filter($results, fn ($r) => $r['status'] === 'failed')),
                'results' => $results,
            ],
        ];
    }

    public function deliverQueuedMessage(int $messageId, ?string $sender = null): array
    {
        $message = WaCarakaMessage::query()->find($messageId);

        if (!$message || $message->direction !== 'outbound') {
            return $this->error('Pesan antrean tidak ditemukan.', null, 404);
        }

        if (in_array($message->status, ['sent', 'delivered', 'read'], true)) {
            return [
                'ok' => true,
                'status' => 200,
                'data' => ['messageId' => $message->wa_message_id, 'status' => $message->status],
            ];
        }

        $response = $this->post('/send-text', [
            'to' => $message->remote_number,
            'text' => $message->message_text,
        ]);

        $message->forceFill([
            'wa_message_id' => $response['data']['messageId'] ?? $message->wa_message_id,
            'status' => $response['ok'] ? 'sent' : 'failed',
            'metadata' => $response['data'] ?? ['error' => $response['error'] ?? null],
        ])->save();

        $this->syncConversation($message->fresh());

        $storedMessage = $message->fresh('user:id,name,alias');
        if ($storedMessage) {
            $this->dispatchMessageSynced($storedMessage);
        }

        $this->logLegacy([
            'sender' => $sender,
            'receiver' => $message->remote_number,
            'message' => $message->message_text,
            'type' => $message->message_type,
            'status' => $response['ok'] ? 'sent' : 'failed',
            'payload' => $response['data'] ?? ['error' => $response['error'] ?? null],
        ]);

        return $response;
    }

    /**
     * Reply to a specific inbound message.
     */
    public function replyTo(WaCarakaMessage $inboundMessage, string $text, int $userId): array
    {
        $response = $this->sendText(
            $inboundMessage->remote_number,
            $text,
            null,
            $userId,
        );

        // Mark original inbound as replied
        if ($response['ok']) {
            $inboundMessage->update(['replied_at' => now()]);
        }

        return $response;
    }

    /**
     * Bulk send — same message to multiple recipients.
     */
    public function broadcastText(array $recipients, string $text, ?string $sender = null, ?int $userId = null): array
    {
        if (count($recipients) > $this->broadcastLimit) {
            return $this->error(
                sprintf('Maksimal %d penerima per broadcast.', $this->broadcastLimit),
                'Kurangi jumlah penerima lalu kirim ulang.',
                422,
            );
        }

        $results = [];

        foreach ($recipients as $to) {
            $res = $this->sendText($to, $text, $sender, $userId);
            $results[] = [
                'to'      => $to,
                'ok'      => $res['ok'],
                'status'  => $res['status'],
                'message' => $res['error'] ?? 'OK',
            ];
        }

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => [
                'total'     => count($recipients),
                'succeeded' => count(array_filter($results, fn ($r) => $r['ok'])),
                'failed'    => count(array_filter($results, fn ($r) => !$r['ok'])),
                'results'   => $results,
            ],
        ];
    }

    protected function sendRuntimeText(string $to, string $text, ?string $sender = null, ?int $userId = null): array
    {
        $response = $this->post('/send-text', [
            'to'   => $to,
            'text' => $text,
        ]);

        $conversationId = WaCarakaMessage::conversationIdFor($to);

        $outboundMsg = $this->storeMessageAndSync([
            'user_id'         => $userId,
            'direction'       => 'outbound',
            'remote_number'   => $to,
            'message_text'    => $text,
            'message_type'    => 'text',
            'wa_message_id'   => $response['data']['messageId'] ?? null,
            'status'          => $response['ok'] ? 'sent' : 'failed',
            'conversation_id' => $conversationId,
            'metadata'        => $response['data'] ?? ['error' => $response['error'] ?? null],
        ]);

        if ($outboundMsg) {
            $this->dispatchMessageSynced($outboundMsg);
        }

        $this->logLegacy([
            'sender'   => $sender,
            'receiver' => $to,
            'message'  => $text,
            'type'     => 'text',
            'status'   => $response['ok'] ? 'sent' : 'failed',
            'payload'  => $response['data'] ?? ['error' => $response['error'] ?? null],
        ]);

        return $response;
    }

    // ══════════════════════════════════════════════
    // Inbound Webhook Handler
    // ══════════════════════════════════════════════

    /**
     * Process an incoming message pushed by the WA runtime webhook.
     * Called from the webhook controller endpoint.
     */
    public function handleInbound(array $payload): WaCarakaMessage
    {
        $raw = is_array($payload['raw'] ?? null) ? $payload['raw'] : [];

        $chatAddress = (string) (
            $payload['chatId']
            ?? $payload['remoteJid']
            ?? $payload['remote']
            ?? ($raw['chatId'] ?? null)
            ?? ($raw['remoteJid'] ?? null)
            ?? $payload['from']
            ?? ''
        );
        $senderAddress = (string) (
            $payload['fromPn']
            ?? $payload['resolvedFromJid']
            ?? $payload['from']
            ?? $payload['participant']
            ?? $payload['author']
            ?? ($raw['participant'] ?? null)
            ?? ($raw['author'] ?? null)
            ?? ($raw['fromRaw'] ?? null)
            ?? $chatAddress
        );

        $isGroup = str_ends_with($chatAddress, '@g.us')
            || (bool) ($payload['isGroup'] ?? false)
            || (bool) ($raw['isGroup'] ?? false)
            || str_ends_with((string) ($raw['chatId'] ?? ''), '@g.us');

        if ($isGroup && !str_ends_with($chatAddress, '@g.us')) {
            $chatAddress = (string) ($raw['chatId'] ?? $chatAddress);
        }

        $remoteNumber = $isGroup ? $chatAddress : $senderAddress;

        // Guard: keep canonical chat JID for groups so group/personal never merge.
        if ($isGroup && str_ends_with((string) ($raw['chatId'] ?? ''), '@g.us')) {
            $remoteNumber = (string) $raw['chatId'];
        }

        $conversationId = WaCarakaMessage::conversationIdFor($remoteNumber);

        $attributes = [
            'user_id'         => null, // unassigned initially
            'direction'       => 'inbound',
            'remote_number'   => $remoteNumber,
            'local_number'    => $payload['to'] ?? $payload['local'] ?? null,
            'message_text'    => $payload['text'] ?? $payload['body'] ?? $payload['message'] ?? null,
            'message_type'    => $payload['type'] ?? 'text',
            'wa_message_id'   => $payload['id'] ?? $payload['messageId'] ?? null,
            'status'          => 'received',
            'conversation_id' => $conversationId,
            'metadata'        => $payload,
        ];

        $waMessageId = $payload['id'] ?? $payload['messageId'] ?? null;

        if ($waMessageId) {
            $message = WaCarakaMessage::firstOrCreate(
                ['wa_message_id' => $waMessageId],
                $attributes,
            );
        } else {
            $message = WaCarakaMessage::create($attributes);
        }

        // Always sync conversation record (create or update last_activity)
        $this->syncConversation($message);

        if ($message->wasRecentlyCreated) {
            try {
                WaCarakaMessageReceived::dispatch($message);
            } catch (\Throwable $e) {
                Log::warning('[WaCaraka] Failed to dispatch message received event', [
                    'message_id' => $message->id,
                    'error'      => $e->getMessage(),
                ]);
            }

            // Chatbot auto-reply for text messages
            if (($message->message_type ?? 'text') === 'text' && !empty($message->message_text)) {
                try {
                    $chatbot = app(WaCarakaChatbotService::class);
                    $reply = $chatbot->processInbound(
                        $message->remote_number,
                        $message->message_text,
                    );

                    if ($reply !== null) {
                        $this->sendText($message->remote_number, $reply, 'CHATBOT', null);
                        $message->update(['replied_at' => now()]);
                    }
                } catch (\Exception $e) {
                    Log::warning('[WaCaraka] Chatbot auto-reply failed', [
                        'message_id' => $message->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }
        }

        return $message;
    }

    /**
     * Try to pull inbox from runtime (if endpoint exists).
     * Falls back gracefully if runtime doesn't support it yet.
     */
    public function pullInbox(?string $since = null): array
    {
        $query = $since ? ['since' => $since] : [];

        $response = $this->get('/messages', $query);

        if (!$response['ok']) {
            return [
                'ok'     => true,
                'status' => 200,
                'data'   => [
                    'supported' => false,
                    'pulled' => 0,
                    'stored' => 0,
                    'message' => 'Runtime belum mendukung endpoint inbox.',
                ],
            ];
        }

        $messages = $response['data']['messages'] ?? $response['data'] ?? [];

        // Store each pulled message if not already stored
        $stored = 0;
        foreach ($messages as $msg) {
            $waId = $msg['id'] ?? $msg['messageId'] ?? null;

            if ($waId && WaCarakaMessage::where('wa_message_id', $waId)->exists()) {
                continue;
            }

            $message = $this->handleInbound($msg);

            if ($message->wasRecentlyCreated) {
                $stored++;
            }
        }

        return [
            'ok'     => true,
            'status' => 200,
            'data'   => [
                'supported' => true,
                'pulled' => count($messages),
                'stored' => $stored,
            ],
        ];
    }

    // ══════════════════════════════════════════════
    // Inbox Queries (from local DB)
    // ══════════════════════════════════════════════

    /**
     * Get inbox conversations (grouped by remote_number).
     */
    public function conversations(int $limit = 30): array
    {
        if (!Schema::hasTable('wa_caraka_messages')) {
            return [];
        }

        return WaCarakaMessage::query()
            ->select('remote_number', 'conversation_id')
            ->selectRaw('MAX(created_at) as last_message_at')
            ->selectRaw('COUNT(*) as message_count')
            ->selectRaw("SUM(CASE WHEN direction = 'inbound' AND replied_at IS NULL THEN 1 ELSE 0 END) as unreplied_count")
            ->groupBy('remote_number', 'conversation_id')
            ->orderByDesc('last_message_at')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'remoteNumber'   => $row->remote_number,
                'conversationId' => $row->conversation_id,
                'lastMessageAt'  => $row->last_message_at,
                'messageCount'   => (int) $row->message_count,
                'unrepliedCount' => (int) $row->unreplied_count,
            ])
            ->all();
    }

    /**
     * Get messages for a specific conversation (by remote number).
     */
    public function conversationMessages(string $remoteNumber, int $limit = 50): array
    {
        if (!Schema::hasTable('wa_caraka_messages')) {
            return [];
        }

        return WaCarakaMessage::query()
            ->fromNumber($remoteNumber)
            ->with('user:id,name,alias')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(function (WaCarakaMessage $msg) {
                $metadata = is_array($msg->metadata) ? $msg->metadata : [];
                $context = $this->messageContext($msg);

                return [
                    'id'            => $msg->id,
                    'direction'     => $msg->direction,
                    'remoteNumber'  => $msg->remote_number,
                    'text'          => $msg->message_text,
                    'type'          => $msg->message_type,
                    'status'        => $msg->status,
                    'waMessageId'   => $msg->wa_message_id,
                    'operator'      => $msg->user ? ($msg->user->alias ?: $msg->user->name) : null,
                    'repliedAt'     => $msg->replied_at
                        ? $msg->replied_at->setTimezone('Asia/Jakarta')->format('d M H:i') . ' WIB'
                        : null,
                    'sentAt'        => $msg->created_at
                        ? $msg->created_at->setTimezone('Asia/Jakarta')->format('d M H:i') . ' WIB'
                        : null,
                    'metadata'      => $metadata,
                    'isGroup'       => $context['is_group'],
                    'groupName'     => $context['group_name'],
                    'senderName'    => $context['sender_name'],
                    'senderKey'     => $context['sender_key'],
                ];
            })
            ->all();
    }

    private function messageContext(WaCarakaMessage $message): array
    {
        $metadata = is_array($message->metadata) ? $message->metadata : [];

        $remote = (string) ($message->remote_number ?? '');
        $remoteJid = (string) ($metadata['remoteJid'] ?? $metadata['chatId'] ?? $remote);
        $participant = (string) ($metadata['participant'] ?? $metadata['author'] ?? $metadata['from'] ?? '');

        $isGroup = str_ends_with($remoteJid, '@g.us') || str_ends_with($remote, '@g.us') || (bool) ($metadata['isGroup'] ?? false);

        $groupName = $metadata['groupName']
            ?? $metadata['groupSubject']
            ?? ($isGroup ? ($metadata['pushName'] ?? null) : null);

        $senderName = $metadata['senderName']
            ?? $metadata['participantName']
            ?? $metadata['pushName']
            ?? null;

        if ($message->direction === 'outbound') {
            $senderName = $message->user ? ($message->user->alias ?: $message->user->name) : ($senderName ?? 'Operator');
        }

        if (!$senderName && $participant !== '') {
            $senderName = $participant;
        }

        if (!$senderName) {
            $senderName = $message->direction === 'outbound' ? 'Operator' : 'Kontak';
        }

        return [
            'is_group' => $isGroup,
            'group_name' => $groupName,
            'sender_name' => $senderName,
            'sender_key' => $participant !== '' ? $participant : ($isGroup ? $remoteJid : $remote),
        ];
    }

    // ══════════════════════════════════════════════
    // Stats & Metrics (local DB)
    // ══════════════════════════════════════════════

    /**
     * Stats from the legacy wa_caraka_logs table.
     */
    public function stats(): array
    {
        if (!Schema::hasTable('wa_caraka_logs')) {
            return [
                'total' => 0, 'sent' => 0, 'failed' => 0,
                'today' => 0, 'lastSent' => 'Belum ada',
            ];
        }

        return [
            'total'    => WaCarakaLog::count(),
            'sent'     => WaCarakaLog::where('status', 'sent')->count(),
            'failed'   => WaCarakaLog::where('status', 'failed')->count(),
            'today'    => WaCarakaLog::whereDate('created_at', today())->count(),
            'lastSent' => optional(WaCarakaLog::latest()->first())?->created_at?->diffForHumans() ?? 'Belum ada',
        ];
    }

    /**
     * Enhanced stats from the new wa_caraka_messages table.
     */
    public function messageStats(): array
    {
        if (!Schema::hasTable('wa_caraka_messages')) {
            return [
                'totalMessages' => 0, 'inbound' => 0, 'outbound' => 0,
                'unreplied' => 0, 'todayInbound' => 0, 'todayOutbound' => 0,
                'conversations' => 0,
            ];
        }

        // Pending reply is counted from conversations shown in inbox:
        // chat terakhir bukan dari WA Caraka (direction terakhir !== outbound).
        // Use effective runtime timestamp when available; pull-inbox can import
        // older messages with newer DB ids.
        $inboxConversationIds = WaCarakaConversation::query()
            ->whereIn('conversation_id', function ($query) {
                $query->from('wa_caraka_messages')
                    ->select('conversation_id')
                    ->whereNotNull('conversation_id')
                    ->groupBy('conversation_id');
            })
            ->pluck('conversation_id')
            ->filter()
            ->values();

        $latestByConversation = [];

        WaCarakaMessage::query()
            ->select(['conversation_id', 'direction', 'created_at', 'metadata'])
            ->whereIn('conversation_id', $inboxConversationIds)
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$latestByConversation) {
                foreach ($rows as $row) {
                    $metadata = is_array($row->metadata) ? $row->metadata : [];
                    $rawTs = $metadata['timestamp'] ?? ($metadata['raw']['timestamp'] ?? null);

                    $effectiveAt = $row->created_at;
                    if (is_string($rawTs) && trim($rawTs) !== '') {
                        try {
                            $parsed = \Illuminate\Support\Carbon::parse($rawTs);
                            if ($parsed) {
                                $effectiveAt = $parsed;
                            }
                        } catch (\Throwable $e) {
                            // Ignore malformed runtime timestamp, fallback to created_at.
                        }
                    }

                    $cid = (string) $row->conversation_id;
                    if (!isset($latestByConversation[$cid]) || $effectiveAt->gte($latestByConversation[$cid]['at'])) {
                        $latestByConversation[$cid] = [
                            'at' => $effectiveAt,
                            'direction' => $row->direction,
                        ];
                    }
                }
            });

        $unrepliedConversations = collect($latestByConversation)
            ->filter(fn ($item) => ($item['direction'] ?? null) !== 'outbound')
            ->count();

        return [
            'totalMessages'  => WaCarakaMessage::count(),
            'inbound'        => WaCarakaMessage::inbound()->count(),
            'outbound'       => WaCarakaMessage::outbound()->count(),
            'unreplied'      => $unrepliedConversations,
            'todayInbound'   => WaCarakaMessage::inbound()->today()->count(),
            'todayOutbound'  => WaCarakaMessage::outbound()->today()->count(),
            'conversations'  => WaCarakaMessage::distinct('conversation_id')->count('conversation_id'),
        ];
    }

    /**
     * Recent log entries from legacy table.
     */
    public function recentLogs(int $limit = 20): array
    {
        if (!Schema::hasTable('wa_caraka_logs')) {
            return [];
        }

        $displayLimit = (int) config('wa_caraka.log_display_limit', $limit);

        return WaCarakaLog::latest()
            ->limit(min($limit, $displayLimit))
            ->get()
            ->map(fn (WaCarakaLog $log) => [
                'id'       => $log->id,
                'sender'   => $log->sender,
                'receiver' => $log->receiver,
                'message'  => $log->message,
                'type'     => $log->type,
                'status'   => $log->status,
                'sentAt'   => $log->created_at?->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
            ])
            ->all();
    }

    // ══════════════════════════════════════════════
    // Internal Storage
    // ══════════════════════════════════════════════

    private function storeMessage(array $data): void
    {
        if (!Schema::hasTable('wa_caraka_messages')) {
            return;
        }

        try {
            WaCarakaMessage::create($data);
        } catch (\Exception $e) {
            Log::warning('[WaCaraka] Failed to store message', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Store outbound/inbound message and synchronize conversation state.
     */
    private function storeMessageAndSync(array $data): ?WaCarakaMessage
    {
        if (!Schema::hasTable('wa_caraka_messages')) {
            return null;
        }

        try {
            $message = WaCarakaMessage::create($data);
            $this->syncConversation($message);

            return $message;
        } catch (\Exception $e) {
            Log::warning('[WaCaraka] Failed to store message and sync conversation', ['error' => $e->getMessage()]);

            return null;
        }
    }

    private function logLegacy(array $data): void
    {
        if (!config('wa_caraka.logging_enabled', true) || !Schema::hasTable('wa_caraka_logs')) {
            return;
        }

        try {
            WaCarakaLog::create($data);
        } catch (\Exception $e) {
            Log::warning('[WaCaraka] Failed to write legacy log', ['error' => $e->getMessage()]);
        }
    }

    private function createQueuedOutboundMessage(string $to, string $text, ?int $userId = null): ?WaCarakaMessage
    {
        if (!Schema::hasTable('wa_caraka_messages')) {
            return null;
        }

        $message = $this->storeMessageAndSync([
            'user_id' => $userId,
            'direction' => 'outbound',
            'remote_number' => $to,
            'message_text' => $text,
            'message_type' => 'text',
            'status' => 'queued',
            'conversation_id' => WaCarakaMessage::conversationIdFor($to),
            'metadata' => [
                'queued' => true,
                'queuedAt' => now()->toISOString(),
            ],
        ]);

        return $message?->fresh('user:id,name,alias');
    }

    private function dispatchMessageSynced(?WaCarakaMessage $message): void
    {
        if (!$message) {
            return;
        }

        try {
            WaCarakaMessageSynced::dispatch($message);
        } catch (\Throwable $e) {
            Log::warning('[WaCaraka] Broadcast dispatch failed (non-critical)', ['error' => $e->getMessage()]);
        }
    }

    private function shouldDispatchOutboundAsync(): bool
    {
        if (!config('wa_caraka.async_dispatch', true)) {
            return false;
        }

        return !app()->runningUnitTests();
    }

    /**
     * Sync the wa_caraka_conversations record for a message.
     * Creates the conversation row if it does not exist yet,
     * and increments unread_count + updates last_activity_at for inbound.
     * Called for both inbound (webhook) and outbound (send/broadcast).
     */
    private function syncConversation(WaCarakaMessage $message): void
    {
        if (!Schema::hasTable('wa_caraka_conversations')) {
            return;
        }

        try {
            $activityAt = $this->resolveMessageActivityAt($message);

            $convo = WaCarakaConversation::query()
                ->where('conversation_id', $message->conversation_id)
                ->first();

            if (!$convo) {
                // Prevent duplicate threads for same WA chat when legacy/new conversation keys differ.
                $convo = WaCarakaConversation::query()
                    ->where('remote_number', $message->remote_number)
                    ->orderByDesc('last_activity_at')
                    ->first();
            }

            if (!$convo) {
                $convo = WaCarakaConversation::create([
                    'conversation_id'  => $message->conversation_id,
                    'remote_number'    => $message->remote_number,
                    'status'           => 'pending',
                    'last_activity_at' => $activityAt,
                ]);
            }

            $metadata = is_array($message->metadata) ? $message->metadata : [];
            $resolvedName = $metadata['groupName']
                ?? $metadata['groupSubject']
                ?? $metadata['senderName']
                ?? $metadata['participantName']
                ?? $metadata['pushName']
                ?? ($metadata['raw']['meta']['notifyName'] ?? null)
                ?? ($metadata['raw']['notifyName'] ?? null)
                ?? null;

                // Prioritize name sources: groupName/groupSubject > senderName/participantName/pushName > notifyName
                // Always update name if we found a new one and it's better than what we have
                // OR if the current name is empty (never set)
                $shouldUpdateName = false;
                if ($resolvedName) {
                    if (!$convo->remote_name) {
                        // First time setting a name
                        $shouldUpdateName = true;
                        $convo->remote_name = $resolvedName;
                    } elseif ($convo->remote_name !== $resolvedName) {
                        // Name changed - update if the new one is not generic/fallback
                        // (e.g., prefer actual names over JID numbers)
                        if (!is_numeric($resolvedName) || !is_numeric($convo->remote_name)) {
                            $shouldUpdateName = true;
                            $convo->remote_name = $resolvedName;
                        }
                    }
                }

                $lastActivityAt = $convo->last_activity_at;
                if (!$lastActivityAt || $activityAt->greaterThan($lastActivityAt)) {
                    $lastActivityAt = $activityAt;
                }

                $updateData = ['last_activity_at' => $lastActivityAt];
                if ($shouldUpdateName) {
                    $updateData['remote_name'] = $convo->remote_name;
                }

                if ($message->direction === 'inbound') {
                    // Increment unread only for newly created messages
                    if ($message->wasRecentlyCreated) {
                        $convo->increment('unread_count');
                    }
                    $convo->update($updateData);
                } else {
                    // Outbound: update activity timestamp, keep status as open
                    $updateData['status'] = $convo->status === 'pending' ? 'open' : $convo->status;
                    $convo->update($updateData);
            }

            WaCarakaConversationUpdated::dispatch($convo->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));
        } catch (\Exception $e) {
            Log::warning('[WaCaraka] Failed to sync conversation', [
                'conversation_id' => $message->conversation_id,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    private function resolveMessageActivityAt(WaCarakaMessage $message): \Illuminate\Support\Carbon
    {
        $metadata = is_array($message->metadata) ? $message->metadata : [];
        $rawTimestamp = $metadata['timestamp'] ?? ($metadata['raw']['timestamp'] ?? null);

        if (is_numeric($rawTimestamp)) {
            $value = (int) $rawTimestamp;
            // Heuristic: runtime can send ms timestamp.
            if ($value > 9999999999) {
                $value = (int) floor($value / 1000);
            }
            try {
                return \Illuminate\Support\Carbon::createFromTimestamp($value);
            } catch (\Throwable $e) {
                // fallback below
            }
        }

        if (is_string($rawTimestamp) && trim($rawTimestamp) !== '') {
            try {
                return \Illuminate\Support\Carbon::parse($rawTimestamp);
            } catch (\Throwable $e) {
                // fallback below
            }
        }

        return $message->created_at ?? now();
    }
}
