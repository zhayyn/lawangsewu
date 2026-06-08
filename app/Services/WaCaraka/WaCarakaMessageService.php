<?php

namespace App\Services\WaCaraka;

use App\Events\WaCarakaMessageReceived;
use App\Events\WaCarakaMessageSynced;
use App\Jobs\SendWaCarakaOutboundMessage;
use App\Models\User;
use App\Models\WaCarakaConversation;
use App\Models\WaCarakaLog;
use App\Models\WaCarakaMessage;
use App\Models\WaCarakaSyncRun;
use App\Support\WaCarakaDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * WaCarakaMessageService
 *
 * Handles all message operations: sending, receiving, broadcasting, and queueing.
 * Part of the refactored WaCarakaService split.
 */
class WaCarakaMessageService
{
    private WaCarakaHttpClient $http;
    private WaCarakaConversationService $conversations;
    private WaCarakaStatsService $stats;

    /** @var array<int, bool> */
    protected array $validatedUserIds = [];

    protected int $broadcastLimit;

    public function __construct(
        WaCarakaHttpClient $http,
        WaCarakaConversationService $conversations,
        WaCarakaStatsService $stats
    ) {
        $this->http = $http;
        $this->conversations = $conversations;
        $this->stats = $stats;
        $this->broadcastLimit = (int) config('wa_caraka.broadcast_limit', 50);
    }

    // ══════════════════════════════════════════════
    // Accessors
    // ══════════════════════════════════════════════

    public function broadcastLimit(): int
    {
        return $this->broadcastLimit;
    }

    // ══════════════════════════════════════════════
    // Device / Session Management (delegates)
    // ══════════════════════════════════════════════

    public function health(): array
    {
        return $this->http->get('/health');
    }

    public function qr(): array
    {
        return $this->http->get('/qr');
    }

    public function refreshQr(): array
    {
        return $this->http->post('/refresh-qr');
    }

    public function restart(): array
    {
        return $this->http->post('/restart');
    }

    public function reconnect(): array
    {
        return $this->http->post('/reconnect');
    }

    public function disconnect(): array
    {
        return $this->http->post('/disconnect');
    }

    // ══════════════════════════════════════════════
    // Runtime History
    // ══════════════════════════════════════════════

    public function history(): array
    {
        return $this->http->get('/history');
    }

    public function clearHistory(): array
    {
        return $this->http->post('/history/clear');
    }

    public function clearInbox(): array
    {
        try {
            WaCarakaMessage::truncate();
            WaCarakaConversation::truncate();
            Log::info('[WaCaraka/Message] Inbox cleared by superadmin');
            return ['ok' => true, 'status' => 200];
        } catch (\Exception $e) {
            Log::error('[WaCaraka/Message] Failed to clear inbox', ['error' => $e->getMessage()]);
            return $this->http->error('Gagal menghapus inbox', $e->getMessage());
        }
    }

    public function getLidMappings(): array
    {
        return $this->http->get('/lid-mappings');
    }

    public function syncContacts(): array
    {
        $response = $this->http->post('/contacts/sync');

        if (($response['status'] ?? 0) === 404 || ($response['ok'] ?? false) === false) {
            $mappings = $this->http->get('/lid-mappings');
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

        $response = $this->http->post('/contacts/resolve', ['jids' => $normalized]);

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
    // Pull Inbox
    // ══════════════════════════════════════════════

    public function pullInbox(?string $since = null): array
    {
        if ($since === null && WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            $latestMessageAt = WaCarakaMessage::query()->max('created_at');

            if ($latestMessageAt) {
                $since = Carbon::parse($latestMessageAt)
                    ->subMinutes(5)
                    ->toIso8601String();
            }
        }

        $query = $since ? ['since' => $since] : [];
        $response = $this->http->get('/messages', $query);

        if (!$response['ok']) {
            return [
                'ok' => true,
                'status' => 200,
                'data' => [
                    'supported' => false,
                    'pulled' => 0,
                    'stored' => 0,
                    'message' => 'Runtime belum mendukung endpoint inbox.',
                ],
            ];
        }

        $messages = $response['data']['messages'] ?? $response['data'] ?? [];

        $stored = 0;
        foreach ($messages as $msg) {
            if ($this->shouldIgnoreInboundPayload((array) $msg)) {
                continue;
            }

            $waId = $msg['id'] ?? $msg['messageId'] ?? null;

            if ($waId && WaCarakaMessage::where('wa_message_id', $waId)->exists()) {
                continue;
            }

            $message = $this->ingestWebhookMessage(array_merge($msg, [
                'direction' => $msg['direction'] ?? 'inbound',
                'syncSource' => $msg['syncSource'] ?? 'realtime',
            ]));

            if ($message->wasRecentlyCreated) {
                $stored++;
            }
        }

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'supported' => true,
                'pulled' => count($messages),
                'stored' => $stored,
            ],
        ];
    }

    // ══════════════════════════════════════════════
    // Outbound Messaging
    // ══════════════════════════════════════════════

    public function sendText(string $to, string $text, ?string $sender = null, ?int $userId = null, ?string $quoteWaId = null): array
    {
        return $this->sendRuntimeText($to, $text, $sender, $userId, $quoteWaId);
    }

    public function sendMedia(string $to, array $mediaPayload, ?string $sender = null, ?int $userId = null): array
    {
        return $this->sendRuntimeMedia($to, $mediaPayload, $sender, $userId);
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

        return $this->deliverQueuedMessage($queuedMessage->id, $sender);
    }

    public function queueBroadcastText(array $recipients, string $text, ?string $sender = null, ?int $userId = null): array
    {
        if (count($recipients) > $this->broadcastLimit) {
            return $this->http->error(
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
            return $this->http->error('Pesan antrean tidak ditemukan.', null, 404);
        }

        if (in_array($message->status, ['sent', 'delivered', 'read'], true)) {
            return [
                'ok' => true,
                'status' => 200,
                'data' => ['messageId' => $message->wa_message_id, 'status' => $message->status],
            ];
        }

        $response = $this->http->post('/send-text', [
            'to' => $message->remote_number,
            'text' => $message->message_text,
        ]);

        $message->forceFill([
            'wa_message_id' => $response['data']['messageId'] ?? $message->wa_message_id,
            'status' => $response['ok'] ? 'sent' : 'failed',
            'metadata' => $response['data'] ?? ['error' => $response['error'] ?? null],
        ])->save();

        $freshMessage = $message->fresh();
        if ($freshMessage) {
            $this->conversations->syncConversation($freshMessage);
        }

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

    public function replyTo(WaCarakaMessage $inboundMessage, string $text, int $userId): array
    {
        $response = $this->sendText(
            $inboundMessage->remote_number,
            $text,
            null,
            $userId,
        );

        if ($response['ok']) {
            $inboundMessage->update(['replied_at' => now()]);
        }

        return $response;
    }

    public function broadcastText(array $recipients, string $text, ?string $sender = null, ?int $userId = null): array
    {
        if (count($recipients) > $this->broadcastLimit) {
            return $this->http->error(
                sprintf('Maksimal %d penerima per broadcast.', $this->broadcastLimit),
                'Kurangi jumlah penerima lalu kirim ulang.',
                422,
            );
        }

        $results = [];

        foreach ($recipients as $to) {
            $res = $this->sendText($to, $text, $sender, $userId);
            $results[] = [
                'to' => $to,
                'ok' => $res['ok'],
                'status' => $res['status'],
                'message' => $res['error'] ?? 'OK',
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'total' => count($recipients),
                'succeeded' => count(array_filter($results, fn ($r) => $r['ok'])),
                'failed' => count(array_filter($results, fn ($r) => !$r['ok'])),
                'results' => $results,
            ],
        ];
    }

    /**
     * Recall / unsend a WhatsApp message.
     *
     * @param  string   $jid
     * @param  string   $messageId
     * @param  int|null $userId
     * @return array
     */
    public function unsendMessage(string $jid, string $messageId, ?int $userId = null): array
    {
        $jid = trim($jid);
        $messageId = trim($messageId);

        if ($jid === '' || $messageId === '') {
            Log::warning('[WaCaraka/Message][Unsend] Validation failed', [
                'jid' => $jid,
                'message_id' => $messageId,
                'user_id' => $userId,
            ]);
            return $this->http->error('JID dan Message ID wajib diisi.', null, 422);
        }

        if (!preg_match('/@(c\.us|g\.us|s\.whatsapp\.net|lid)$/i', $jid) && !preg_match('/^\d+$/', $jid)) {
            Log::warning('[WaCaraka/Message][Unsend] Invalid JID format', ['jid' => $jid, 'user_id' => $userId]);
            return $this->http->error('Format JID tidak valid. Gunakan format 628xxx@c.us atau 628xxx@g.us.', null, 422);
        }

        if (WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            $localMsg = WaCarakaMessage::query()
                ->where('wa_message_id', $messageId)
                ->first(['id', 'created_at']);

            if ($localMsg) {
                $minutesAgo = now()->diffInMinutes($localMsg->created_at, true);
                if ($minutesAgo > 60) {
                    Log::info('[WaCaraka/Message][Unsend] Rejected — older than 60 min', [
                        'wa_message_id' => $messageId,
                        'minutes_ago' => $minutesAgo,
                        'user_id' => $userId,
                    ]);
                    return $this->http->error(
                        sprintf('Pesan tidak dapat di-recall karena sudah %.0f menit lalu (batas 60 menit).', $minutesAgo),
                        null,
                        400,
                    );
                }
            }
        }

        Log::info('[WaCaraka/Message][Unsend] Attempting via bridge', [
            'jid' => $jid,
            'wa_message_id' => $messageId,
            'user_id' => $userId,
        ]);

        $result = $this->http->post('/unsend-message', [
            'to' => $jid,
            'message_id' => $messageId,
        ]);

        $logCtx = [
            'jid' => $jid,
            'wa_message_id' => $messageId,
            'user_id' => $userId,
            'bridge_status' => $result['status'] ?? null,
            'bridge_ok' => $result['ok'] ?? false,
            'bridge_error' => $result['error'] ?? null,
        ];

        if ($result['ok']) {
            Log::info('[WaCaraka/Message][Unsend] Success', $logCtx);
        } else {
            Log::warning('[WaCaraka/Message][Unsend] Bridge rejected', $logCtx);
        }

        return $result;
    }

    // ══════════════════════════════════════════════
    // Internal Send Methods
    // ══════════════════════════════════════════════

    protected function sendRuntimeText(string $to, string $text, ?string $sender = null, ?int $userId = null, ?string $quoteWaId = null): array
    {
        $normalizedTo = WaCarakaMessage::normalizeRemoteNumber($to);

        $payload = [
            'to' => $normalizedTo,
            'text' => $text,
            'force' => true,
        ];
        if ($quoteWaId) {
            $payload['quote_wa_id'] = $quoteWaId;
            $payload['quote'] = $quoteWaId;
        }

        $response = $this->http->post('/send-text', $payload);

        $conversationId = WaCarakaMessage::conversationIdFor($normalizedTo);

        $outboundMsg = $this->storeMessageAndSync([
            'user_id' => $userId,
            'direction' => 'outbound',
            'remote_number' => $normalizedTo,
            'message_text' => $text,
            'message_type' => 'text',
            'wa_message_id' => $response['data']['messageId'] ?? null,
            'status' => $response['ok'] ? 'sent' : 'failed',
            'conversation_id' => $conversationId,
            'metadata' => $response['data'] ?? ['error' => $response['error'] ?? null],
        ]);

        if ($outboundMsg) {
            $this->dispatchMessageSynced($outboundMsg);
        }

        $this->logLegacy([
            'sender' => $sender,
            'receiver' => $normalizedTo,
            'message' => $text,
            'type' => 'text',
            'status' => $response['ok'] ? 'sent' : 'failed',
            'payload' => $response['data'] ?? ['error' => $response['error'] ?? null],
        ]);

        return $response;
    }

    protected function sendRuntimeMedia(string $to, array $mediaPayload, ?string $sender = null, ?int $userId = null): array
    {
        $normalizedTo = WaCarakaMessage::normalizeRemoteNumber($to);
        $kind = strtolower(trim((string) ($mediaPayload['media_kind'] ?? $mediaPayload['kind'] ?? 'document')));
        $caption = (string) ($mediaPayload['caption'] ?? '');
        $fileName = $this->normalizeOutgoingMediaFileName($mediaPayload['file_name'] ?? null, $kind, $mediaPayload['media_url'] ?? null);
        $mimeType = $this->normalizeOutgoingMediaMimeType(
            $mediaPayload['mime_type'] ?? null,
            $fileName,
            $kind,
            $mediaPayload['media_url'] ?? null,
        );
        $mediaUrl = isset($mediaPayload['media_url']) ? trim((string) $mediaPayload['media_url']) : null;

        $payload = [
            'to' => $normalizedTo,
            'media_kind' => $kind,
            'media_url' => $mediaUrl,
            'mime_type' => $mimeType,
            'file_name' => $fileName,
            'caption' => $caption,
            'ptt' => (bool) ($mediaPayload['ptt'] ?? false),
            'force' => true,
        ];

        if (!empty($mediaPayload['quote_wa_id'])) {
            $payload['quote_wa_id'] = $mediaPayload['quote_wa_id'];
            $payload['quote'] = $mediaPayload['quote_wa_id'];
        }

        $response = $this->http->post('/send-media', $payload);

        $conversationId = WaCarakaMessage::conversationIdFor($normalizedTo);

        $metadata = $response['data'] ?? [];
        if (!is_array($metadata)) {
            $metadata = ['raw' => $metadata];
        }

        $existingMedia = is_array($metadata['media'] ?? null) ? $metadata['media'] : [];
        $sourceUrl = $existingMedia['source'] ?? $existingMedia['url'] ?? null;
        $byteLength = $existingMedia['byteLength'] ?? $this->byteLengthFromDataUrl($mediaUrl);

        $metadata['media'] = array_merge(
            $existingMedia,
            [
                'kind' => $kind,
                'mimetype' => $mimeType,
                'fileName' => $fileName,
                'caption' => $caption,
                'byteLength' => $byteLength,
                'dataUrl' => ($kind === 'image' || $kind === 'sticker') && !$sourceUrl ? $mediaUrl : null,
                'url' => $sourceUrl ?? $mediaUrl,
            ],
        );

        $outboundMsg = $this->storeMessageAndSync([
            'user_id' => $userId,
            'direction' => 'outbound',
            'remote_number' => $normalizedTo,
            'message_text' => $caption,
            'message_type' => $kind,
            'wa_message_id' => $response['data']['messageId'] ?? null,
            'status' => $response['ok'] ? 'sent' : 'failed',
            'conversation_id' => $conversationId,
            'metadata' => $metadata,
        ]);

        if ($outboundMsg) {
            $this->dispatchMessageSynced($outboundMsg);
        }

        $this->logLegacy([
            'sender' => $sender,
            'receiver' => $normalizedTo,
            'message' => $caption,
            'type' => $kind,
            'status' => $response['ok'] ? 'sent' : 'failed',
            'payload' => $response['data'] ?? ['error' => $response['error'] ?? null],
        ]);

        return $response;
    }

    // ══════════════════════════════════════════════
    // Media Normalization
    // ══════════════════════════════════════════════

    private function byteLengthFromDataUrl(?string $mediaUrl): ?int
    {
        if (!is_string($mediaUrl) || !str_starts_with($mediaUrl, 'data:')) {
            return null;
        }

        $parts = explode(',', $mediaUrl, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $payload = preg_replace('/\s+/', '', $parts[1] ?? '');
        if ($payload === '') {
            return null;
        }

        $padding = 0;
        if (str_ends_with($payload, '==')) {
            $padding = 2;
        } elseif (str_ends_with($payload, '=')) {
            $padding = 1;
        }

        return (int) max(0, ((strlen($payload) * 3) / 4) - $padding);
    }

    private function normalizeOutgoingMediaFileName(mixed $fileName, string $kind, mixed $mediaUrl = null): ?string
    {
        $normalized = trim((string) ($fileName ?? ''));
        if ($normalized !== '') {
            return $normalized;
        }

        if (is_string($mediaUrl) && preg_match('#^https?://#i', $mediaUrl)) {
            $path = parse_url($mediaUrl, PHP_URL_PATH);
            $candidate = trim((string) basename((string) $path));
            if ($candidate !== '' && $candidate !== '/' && $candidate !== '.') {
                return $candidate;
            }
        }

        return match ($kind) {
            'image' => 'image.jpg',
            'video' => 'video.mp4',
            'audio' => 'audio.ogg',
            'sticker' => 'sticker.webp',
            default => 'document.bin',
        };
    }

    private function normalizeOutgoingMediaMimeType(mixed $mimeType, ?string $fileName, string $kind, mixed $mediaUrl = null): ?string
    {
        $normalized = strtolower(trim((string) ($mimeType ?? '')));
        if ($normalized !== '') {
            return $normalized;
        }

        if (is_string($mediaUrl) && preg_match('/^data:([^;,]+);base64,/i', $mediaUrl, $matches)) {
            return strtolower(trim((string) ($matches[1] ?? ''))) ?: null;
        }

        $extension = strtolower((string) pathinfo((string) ($fileName ?? ''), PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'mp3' => 'audio/mpeg',
            'ogg', 'oga' => 'audio/ogg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'pdf' => 'application/pdf',
            'rtf' => 'application/rtf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'zip' => 'application/zip',
            'rar' => 'application/vnd.rar',
            '7z' => 'application/x-7z-compressed',
            default => match ($kind) {
                'image' => 'image/jpeg',
                'video' => 'video/mp4',
                'audio' => 'audio/ogg',
                'sticker' => 'image/webp',
                default => 'application/octet-stream',
            },
        };
    }

    // ══════════════════════════════════════════════
    // Inbound Webhook Handler
    // ══════════════════════════════════════════════

    public function handleInbound(array $payload): WaCarakaMessage
    {
        $message = $this->ingestWebhookMessage(array_merge($payload, [
            'direction' => 'inbound',
            'syncSource' => $payload['syncSource'] ?? 'realtime',
        ]));

        if ($message->wasRecentlyCreated) {
            try {
                WaCarakaMessageReceived::dispatch($message);
            } catch (\Throwable $e) {
                Log::warning('[WaCaraka/Message] Failed to dispatch message received event', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $message;
    }

    public function shouldIgnoreInboundPayload(array $payload): bool
    {
        $candidates = [
            $payload['from'] ?? null,
            $payload['number'] ?? null,
            $payload['remote'] ?? null,
            $payload['remoteJid'] ?? null,
            $payload['fromPn'] ?? null,
            $payload['resolvedFrom'] ?? null,
            $payload['resolvedFromJid'] ?? null,
        ];

        $systemSenders = ['engine-health-check', 'health-check', 'health_check'];
        foreach ($candidates as $candidate) {
            $normalized = strtolower(trim((string) $candidate));
            if ($normalized !== '' && in_array($normalized, $systemSenders, true)) {
                return true;
            }
        }

        $messageText = strtolower(trim((string) (
            $payload['text']
            ?? $payload['body']
            ?? $payload['message']
            ?? ''
        )));

        return $messageText === 'tokenless-route-check';
    }

    public function ingestWebhookMessage(array $payload): WaCarakaMessage
    {
        $attributes = $this->buildMessageAttributesFromWebhookPayload($payload);

        return $this->storeWebhookMessage($attributes);
    }

    public function ingestHistorySyncBatch(array $payload): array
    {
        $items = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
        $run = $this->beginOrUpdateSyncRun($payload);

        $imported = 0;
        $duplicates = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $message = $this->ingestWebhookMessage(array_merge((array) $item, [
                    'syncSource' => 'history',
                    'historySync' => true,
                    'historyRunKey' => $run->run_key,
                ]));

                if ($message->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $duplicates++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('[WaCaraka/Message] History sync item failed', [
                    'run_key' => $run->run_key,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $run = $this->finishSyncRunProgress($run, $payload, count($items), $imported, $duplicates, $failed);

        return [
            'runKey' => $run->run_key,
            'status' => $run->status,
            'received' => count($items),
            'imported' => $imported,
            'duplicates' => $duplicates,
            'failed' => $failed,
            'progress' => $run->progress,
        ];
    }

    private function buildMessageAttributesFromWebhookPayload(array $payload): array
    {
        $raw = is_array($payload['raw'] ?? null) ? $payload['raw'] : [];
        $direction = $this->resolveWebhookDirection($payload);

        $chatAddress = (string) (
            $payload['chatId']
            ?? $payload['remoteJid']
            ?? $payload['remote']
            ?? ($raw['chatId'] ?? null)
            ?? ($raw['remoteJid'] ?? null)
            ?? $payload['to']
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

        $remoteNumber = $direction === 'outbound'
            ? ($isGroup ? $chatAddress : ($chatAddress ?: $payload['to'] ?? $senderAddress))
            : ($isGroup ? $chatAddress : $senderAddress);

        if ($isGroup && str_ends_with((string) ($raw['chatId'] ?? ''), '@g.us')) {
            $remoteNumber = (string) $raw['chatId'];
        }

        $remoteNumber = WaCarakaMessage::normalizeRemoteNumber($remoteNumber);

        // LID Resolution
        if (str_ends_with($remoteNumber, '@lid')) {
            $existingResolved = \App\Models\WaCarakaConversation::query()
                ->where('remote_number', $remoteNumber)
                ->whereNotNull('resolved_number')
                ->value('resolved_number');

            if ($existingResolved) {
                Log::debug('[WaCaraka/Message][LID] Resolved from DB', [
                    'lid' => $remoteNumber,
                    'resolved' => $existingResolved,
                ]);
                $payload['resolvedNumber'] = $existingResolved;
            }
        }

        $payload = $this->normalizePayloadMediaUrls($payload);
        $timestamp = $this->resolvePayloadTimestamp($payload);

        return [
            'user_id' => null,
            'direction' => $direction,
            'remote_number' => $remoteNumber,
            'local_number' => $payload['to'] ?? $payload['local'] ?? null,
            'message_text' => $payload['text'] ?? $payload['body'] ?? $payload['message'] ?? null,
            'message_type' => $payload['type'] ?? 'text',
            'wa_message_id' => $payload['id'] ?? $payload['messageId'] ?? null,
            'status' => $payload['status'] ?? ($direction === 'inbound' ? 'received' : 'sent'),
            'conversation_id' => WaCarakaMessage::conversationIdFor($remoteNumber),
            'metadata' => $this->conversations->compactMessageMetadata($payload),
            'occurred_at' => $timestamp,
        ];
    }

    private function resolveWebhookDirection(array $payload): string
    {
        $direction = strtolower(trim((string) ($payload['direction'] ?? '')));
        if (in_array($direction, ['inbound', 'outbound'], true)) {
            return $direction;
        }

        return (bool) ($payload['fromMe'] ?? false) ? 'outbound' : 'inbound';
    }

    private function storeWebhookMessage(array $attributes): WaCarakaMessage
    {
        $occurredAt = $attributes['occurred_at'] ?? null;
        unset($attributes['occurred_at']);

        $waMessageId = $attributes['wa_message_id'] ?? null;
        if ($waMessageId) {
            $existing = WaCarakaMessage::query()->where('wa_message_id', $waMessageId)->first();
            if ($existing) {
                return $existing;
            }
        }

        $message = new WaCarakaMessage($attributes);

        if ($occurredAt instanceof Carbon) {
            $message->setCreatedAt($occurredAt);
            $message->setUpdatedAt($occurredAt);
        }

        $message->save();
        $this->conversations->syncConversation($message);
        $this->stats->recordDailyMetricForMessage($message);

        return $message;
    }

    // ══════════════════════════════════════════════
    // Media URL Processing
    // ══════════════════════════════════════════════

    private function normalizePayloadMediaUrls(array $payload): array
    {
        $media = $payload['media'] ?? null;
        $rawUrl = is_string($media['url'] ?? null) ? $media['url'] : null;
        $bridgeUrl = is_string($media['bridgeUrl'] ?? null) ? $media['bridgeUrl'] : null;
        $proxyUrl = is_string($media['proxyUrl'] ?? null) ? $media['proxyUrl'] : null;
        $rawDataUrl = is_string($media['dataUrl'] ?? null) ? $media['dataUrl'] : null;

        if (!$media) {
            return $payload;
        }

        $raw = is_array($payload['raw'] ?? null) ? $payload['raw'] : [];
        $rawMediaData = $raw['mediaData'] ?? null;
        if (is_string($rawMediaData) && str_starts_with($rawMediaData, 'data:') && strlen($rawMediaData) > 100) {
            $tokenSrc = $rawUrl ?? $payload['id'] ?? uniqid();
            preg_match('~/internal/media/([a-f0-9]{16,})~i', (string) $tokenSrc, $tm);
            $token = isset($tm[1]) ? strtolower($tm[1]) : substr(md5($tokenSrc), 0, 32);
            $filename = basename(parse_url((string) ($rawUrl ?? ''), PHP_URL_PATH)) ?: ($token . '.bin');

            $localUrl = $this->storeBase64MediaLocally($rawMediaData, $token, $filename);
            if ($localUrl !== null) {
                $payload['media']['url'] = $localUrl;
                $payload['media']['dataUrl'] = $localUrl;
                return $payload;
            }
        }

        if (is_string($rawDataUrl) && str_starts_with($rawDataUrl, 'data:') && strlen($rawDataUrl) > 100) {
            $tokenSrc = $rawUrl ?? $payload['id'] ?? uniqid();
            preg_match('~/internal/media/([a-f0-9]{16,})~i', (string) $tokenSrc, $tm);
            $token = isset($tm[1]) ? strtolower($tm[1]) : substr(md5($tokenSrc), 0, 32);
            $filename = basename(parse_url((string) ($rawUrl ?? ''), PHP_URL_PATH)) ?: ($token . '.bin');

            $localUrl = $this->storeBase64MediaLocally($rawDataUrl, $token, $filename);
            if ($localUrl !== null) {
                $payload['media']['url'] = $localUrl;
                $payload['media']['dataUrl'] = $localUrl;
                return $payload;
            }
        }

        $internalPattern = '~/internal/media/([a-f0-9]{16,})(?:/([^/?#]*))?~i';
        $primaryUrl = null;
        $token = null;
        $filename = '';

        foreach (array_filter([$rawUrl, $bridgeUrl]) as $candidate) {
            if (preg_match($internalPattern, (string) $candidate, $m)) {
                $primaryUrl = $candidate;
                $token = strtolower($m[1]);
                $filename = $m[2] ?? '';
                break;
            }
        }

        if ($primaryUrl !== null && $token !== null) {
            $urlsToTry = array_unique(array_filter([$rawUrl, $bridgeUrl]));
            $localUrl = null;
            foreach ($urlsToTry as $tryUrl) {
                $localUrl = $this->downloadAndStoreInboundMedia((string) $tryUrl, $token, $filename);
                if ($localUrl !== null) {
                    break;
                }
            }

            if ($localUrl !== null) {
                $payload['media']['url'] = $localUrl;
                $payload['media']['dataUrl'] = $localUrl;
            } else {
                $proxyPath = $filename !== '' ? $token . '/' . $filename : $token;
                $laravelProxyUrl = route('lawangsewu.wacaraka.media', ['path' => $proxyPath]);
                $payload['media']['url'] = $laravelProxyUrl;
                $payload['media']['dataUrl'] = $laravelProxyUrl;
            }

            return $payload;
        }

        $httpUrl = $rawUrl ?? $bridgeUrl;
        if (is_string($httpUrl) && preg_match('#^https?://#i', $httpUrl)) {
            $parsedPath = parse_url($httpUrl, PHP_URL_PATH);
            $basename = basename((string) $parsedPath);
            $token = substr(md5($httpUrl), 0, 32);
            $localUrl = $this->downloadAndStoreInboundMedia($httpUrl, $token, $basename);
            if ($localUrl !== null) {
                $payload['media']['url'] = $localUrl;
                $payload['media']['dataUrl'] = $localUrl;
                return $payload;
            }
            $payload['media']['dataUrl'] = $httpUrl;
            return $payload;
        }

        if (is_string($proxyUrl) && str_contains($proxyUrl, '/wa-caraka/media/')) {
            $payload['media']['url'] = $proxyUrl;
            $payload['media']['dataUrl'] = $proxyUrl;
            return $payload;
        }

        if (
            isset($payload['media']['mediaToken'])
            && empty($payload['media']['dataUrl'])
            && preg_match('/^[a-f0-9]{32,}$/', $payload['media']['mediaToken'])
        ) {
            $mediaProxyUrl = route('lawangsewu.wacaraka.media', [
                'path' => $payload['media']['mediaToken'],
            ]);
            $payload['media']['dataUrl'] = $mediaProxyUrl;
            $payload['media']['url'] = $mediaProxyUrl;
        }

        return $payload;
    }

    private function storeBase64MediaLocally(string $dataUrl, string $token, string $filename = ''): ?string
    {
        try {
            if (!preg_match('/^data:([^;]+);base64,(.+)$/s', $dataUrl, $parts)) {
                return null;
            }

            $mime = trim($parts[1]);
            $binary = base64_decode($parts[2], strict: false);

            if ($binary === false || strlen($binary) < 10) {
                return null;
            }

            $ext = $this->extFromMime($mime) ?? (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'bin');
            $safeName = $filename !== ''
                ? preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($filename))
                : $token . '.' . $ext;

            $storagePath = 'wa-media/' . $token . '/' . $safeName;
            Storage::disk('public')->put($storagePath, $binary);

            return Storage::disk('public')->url($storagePath);
        } catch (\Throwable $e) {
            Log::debug('[WaCaraka/Message] storeBase64 failed: ' . $e->getMessage());
            return null;
        }
    }

    private function downloadAndStoreInboundMedia(string $sourceUrl, string $token, string $filename = ''): ?string
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->withHeaders($this->runtimeHeaders())
                ->get($sourceUrl);

            if ($response->failed()) {
                Log::debug('[WaCaraka/Message] Download HTTP failed', [
                    'url' => $sourceUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $body = $response->body();

            if (empty($body)) {
                Log::debug('[WaCaraka/Message] Response body empty', ['url' => $sourceUrl]);
                return null;
            }

            $first6 = strtolower(substr(ltrim($body), 0, 6));
            if (str_starts_with($first6, '<!doc') || str_starts_with($first6, '<html')
                || (str_starts_with($first6, '{"ok"') && !str_starts_with($body, "\xff\xd8"))
            ) {
                Log::debug('[WaCaraka/Message] Response not binary', [
                    'url' => $sourceUrl,
                    'preview' => substr($body, 0, 80),
                ]);
                return null;
            }

            $contentType = $response->header('Content-Type', 'application/octet-stream');
            $ext = $this->extFromMime($contentType) ?? (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'bin');
            $safeName = $filename !== ''
                ? preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($filename))
                : $token . '.' . $ext;

            $storagePath = 'wa-media/' . $token . '/' . $safeName;
            Storage::disk('public')->put($storagePath, $body);

            Log::debug('[WaCaraka/Message] Media saved', [
                'url' => $sourceUrl,
                'path' => $storagePath,
                'bytes' => strlen($body),
            ]);

            return Storage::disk('public')->url($storagePath);
        } catch (\Throwable $e) {
            Log::debug('[WaCaraka/Message] Exception download: ' . $e->getMessage(), [
                'url' => $sourceUrl,
            ]);
            return null;
        }
    }

    private function runtimeHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];
        $token = config('wa_caraka.token', env('LW_WA_V2_TOKEN', ''));
        if (!empty($token)) {
            $headers['X-WA-V2-Token'] = $token;
        }
        return $headers;
    }

    private function extFromMime(string $mime): ?string
    {
        $mime = strtolower(trim($mime));
        $mime = explode(';', $mime)[0];
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'image/bmp' => 'bmp',
            'image/svg+xml' => 'svg',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'audio/mpeg' => 'mp3',
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
            'audio/webm' => 'webm',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            default => null,
        };
    }

    private function resolvePayloadTimestamp(array $payload): ?Carbon
    {
        $raw = is_array($payload['raw'] ?? null) ? $payload['raw'] : [];
        $candidates = [
            $payload['timestamp'] ?? null,
            $payload['messageTimestamp'] ?? null,
            $raw['messageTimestamp'] ?? null,
            $raw['messageTimestamp.low'] ?? null,
            data_get($raw, 'messageTimestamp'),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate instanceof Carbon) {
                return $candidate;
            }

            if (is_object($candidate) && isset($candidate->low) && is_numeric($candidate->low)) {
                $candidate = $candidate->low;
            }

            if (is_array($candidate) && isset($candidate['low']) && is_numeric($candidate['low'])) {
                $candidate = $candidate['low'];
            }

            if (is_numeric($candidate)) {
                $value = (int) $candidate;
                if ($value > 9999999999) {
                    $value = (int) floor($value / 1000);
                }

                if ($value > 0) {
                    return Carbon::createFromTimestamp($value);
                }
            }

            if (is_string($candidate) && trim($candidate) !== '') {
                try {
                    return Carbon::parse($candidate);
                } catch (\Throwable $e) {
                    // Try next candidate.
                }
            }
        }

        return null;
    }

    // ══════════════════════════════════════════════
    // Sync Run Management
    // ══════════════════════════════════════════════

    private function beginOrUpdateSyncRun(array $payload): WaCarakaSyncRun
    {
        $runKey = trim((string) ($payload['runKey'] ?? ''));
        if ($runKey === '') {
            $runKey = 'history-' . now()->format('YmdHis');
        }

        return WaCarakaSyncRun::query()->updateOrCreate(
            ['run_key' => $runKey],
            [
                'source' => 'history',
                'status' => 'running',
                'connection_jid' => $payload['connectionJid'] ?? null,
                'device_label' => $payload['deviceLabel'] ?? null,
                'sync_type' => $payload['syncType'] ?? null,
                'progress' => is_numeric($payload['progress'] ?? null) ? (int) $payload['progress'] : null,
                'started_at' => isset($payload['startedAt']) ? Carbon::parse((string) $payload['startedAt']) : now(),
                'last_event_at' => now(),
                'meta' => [
                    'isLatest' => (bool) ($payload['isLatest'] ?? false),
                    'historySync' => true,
                ],
            ],
        );
    }

    private function finishSyncRunProgress(WaCarakaSyncRun $run, array $payload, int $received, int $imported, int $duplicates, int $failed): WaCarakaSyncRun
    {
        $run->fill([
            'status' => (bool) ($payload['isLatest'] ?? false) ? 'completed' : 'running',
            'sync_type' => $payload['syncType'] ?? $run->sync_type,
            'progress' => is_numeric($payload['progress'] ?? null) ? (int) $payload['progress'] : $run->progress,
            'last_event_at' => now(),
            'finished_at' => (bool) ($payload['isLatest'] ?? false) ? now() : $run->finished_at,
        ]);

        $run->batches_count += 1;
        $run->chats_count += count(is_array($payload['chats'] ?? null) ? $payload['chats'] : []);
        $run->contacts_count += count(is_array($payload['contacts'] ?? null) ? $payload['contacts'] : []);
        $run->messages_received += $received;
        $run->messages_imported += $imported;
        $run->messages_duplicate += $duplicates;
        $run->messages_failed += $failed;
        $run->save();

        return $run->fresh();
    }

    // ══════════════════════════════════════════════
    // Internal Storage Helpers
    // ══════════════════════════════════════════════

    public function storeMessage(array $data): void
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            return;
        }

        try {
            WaCarakaMessage::create($data);
        } catch (\Exception $e) {
            Log::warning('[WaCaraka/Message] Failed to store message', ['error' => $e->getMessage()]);
        }
    }

    public function storeMessageAndSync(array $data): ?WaCarakaMessage
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            return null;
        }

        $data['user_id'] = $this->normalizeUserId($data['user_id'] ?? null);

        try {
            $message = WaCarakaMessage::create($data);
            $this->conversations->syncConversation($message);
            $this->stats->recordDailyMetricForMessage($message);

            return $message;
        } catch (\Exception $e) {
            Log::warning('[WaCaraka/Message] Failed to store message and sync', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function normalizeUserId($userId): ?int
    {
        $normalized = filter_var($userId, FILTER_VALIDATE_INT);
        if ($normalized === false || $normalized === null || $normalized <= 0) {
            return null;
        }

        if (array_key_exists($normalized, $this->validatedUserIds)) {
            return $this->validatedUserIds[$normalized] ? $normalized : null;
        }

        $exists = User::query()->whereKey($normalized)->exists();
        $this->validatedUserIds[$normalized] = $exists;

        return $exists ? $normalized : null;
    }

    public function createQueuedOutboundMessage(string $to, string $text, ?int $userId = null): ?WaCarakaMessage
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            return null;
        }

        $normalizedTo = WaCarakaMessage::normalizeRemoteNumber($to);

        $message = $this->storeMessageAndSync([
            'user_id' => $userId,
            'direction' => 'outbound',
            'remote_number' => $normalizedTo,
            'message_text' => $text,
            'message_type' => 'text',
            'status' => 'queued',
            'conversation_id' => WaCarakaMessage::conversationIdFor($normalizedTo),
            'metadata' => [
                'queued' => true,
                'queuedAt' => now()->toISOString(),
            ],
        ]);

        return $message?->fresh('user:id,name,alias');
    }

    public function logLegacy(array $data): void
    {
        if (!config('wa_caraka.logging_enabled', true) || !WaCarakaDatabase::hasTable('wa_caraka_logs')) {
            return;
        }

        try {
            WaCarakaLog::create($data);
        } catch (\Exception $e) {
            Log::warning('[WaCaraka/Message] Failed to write legacy log', ['error' => $e->getMessage()]);
        }
    }

    private function dispatchMessageSynced(?WaCarakaMessage $message): void
    {
        if (!$message) {
            return;
        }

        try {
            WaCarakaMessageSynced::dispatch($message);
        } catch (\Throwable $e) {
            Log::warning('[WaCaraka/Message] Broadcast dispatch failed', ['error' => $e->getMessage()]);
        }
    }

    private function shouldDispatchOutboundAsync(): bool
    {
        if (!config('wa_caraka.async_dispatch', true)) {
            return false;
        }

        if (app()->runningUnitTests()) {
            return false;
        }

        return config('queue.default') !== 'sync';
    }
}