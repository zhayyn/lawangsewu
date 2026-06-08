<?php

namespace App\Services\WaCaraka;

use App\Models\User;
use App\Models\WaCarakaConversation;
use App\Models\WaCarakaMessage;
use App\Support\WaCarakaDatabase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * WaCarakaConversationService
 *
 * Handles conversation management, message querying, and conversation state sync.
 * Part of the refactored WaCarakaService split.
 */
class WaCarakaConversationService
{
    private WaCarakaHttpClient $http;
    private WaCarakaStatsService $stats;

    public function __construct(
        WaCarakaHttpClient $http,
        WaCarakaStatsService $stats
    ) {
        $this->http = $http;
        $this->stats = $stats;
    }

    // ══════════════════════════════════════════════
    // Conversation Queries
    // ══════════════════════════════════════════════

    /**
     * Get inbox conversations (grouped by remote_number).
     *
     * @param  int  $limit
     * @return array
     */
    public function conversations(int $limit = 30): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_messages')) {
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
     *
     * @param  string  $conversationKey
     * @param  int     $limit
     * @param  bool    $preferConversationId
     * @return array
     */
    public function conversationMessages(string $conversationKey, int $limit = 50, bool $preferConversationId = true): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_messages')) {
            return [];
        }

        $normalizedKey = trim($conversationKey);
        if ($normalizedKey === '') {
            return [];
        }

        $query = WaCarakaMessage::query();

        $query->where(function (Builder $builder) use ($normalizedKey, $preferConversationId) {
            if ($preferConversationId) {
                $relatedConversationIds = $this->relatedConversationIdsFor($normalizedKey);

                if (!empty($relatedConversationIds)) {
                    $builder->whereIn('conversation_id', $relatedConversationIds);
                    return;
                }

                $builder->where('conversation_id', $normalizedKey);
                return;
            }

            $normalizedRemote = WaCarakaMessage::normalizeRemoteNumber($normalizedKey);

            $builder->where('remote_number', $normalizedRemote)
                ->orWhere('remote_number', $normalizedKey)
                ->orWhere('conversation_id', $normalizedKey);
        });

        return $query
            ->with('user:id,name,alias')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(function (WaCarakaMessage $msg) {
                $metadata = $this->compactMessageMetadata(is_array($msg->metadata) ? $msg->metadata : []);
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
                    // ISO8601 for JS time calculations (e.g. 60-minute recall window)
                    'sentAtRaw'     => $msg->created_at?->toISOString(),
                    'metadata'      => $metadata,
                    'isGroup'       => $context['is_group'],
                    'groupName'     => $context['group_name'],
                    'senderName'    => $context['sender_name'],
                    'senderKey'     => $context['sender_key'],
                ];
            })
            ->all();
    }

    /**
     * Resolve all conversation IDs that still belong to the same logical chat.
     *
     * @param  string  $conversationId
     * @return array
     */
    public function relatedConversationIdsFor(string $conversationId): array
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_conversations')) {
            return [$conversationId];
        }

        $conversation = WaCarakaConversation::query()
            ->where('conversation_id', $conversationId)
            ->first(['conversation_id', 'remote_number']);

        if (!$conversation) {
            return [$conversationId];
        }

        $normalizedRemote = WaCarakaMessage::normalizeRemoteNumber((string) $conversation->remote_number);
        if ($normalizedRemote === '') {
            return [$conversationId];
        }

        // Build all possible JID formats for the same phone number.
        $base = preg_replace('/@[^@]+$/', '', $normalizedRemote);
        $candidates = array_unique(array_filter([
            $normalizedRemote,
            $base,
            $base . '@c.us',
            $base . '@s.whatsapp.net',
            $base . '@lid',
        ]));

        $relatedIds = WaCarakaConversation::query()
            ->select(['conversation_id', 'remote_number'])
            ->where(function ($q) use ($candidates) {
                foreach ($candidates as $c) {
                    $q->orWhere('remote_number', $c);
                }
            })
            ->get()
            ->filter(fn (WaCarakaConversation $item) => WaCarakaMessage::normalizeRemoteNumber((string) $item->remote_number) === $normalizedRemote)
            ->pluck('conversation_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!in_array($conversationId, $relatedIds, true)) {
            $relatedIds[] = $conversationId;
        }

        return $relatedIds;
    }

    // ══════════════════════════════════════════════
    // Conversation Sync
    // ══════════════════════════════════════════════

    /**
     * Sync the wa_caraka_conversations record for a message.
     * Creates the conversation row if it does not exist yet,
     * and increments unread_count + updates last_activity_at for inbound.
     *
     * @param  WaCarakaMessage  $message
     * @return void
     */
    public function syncConversation(WaCarakaMessage $message): void
    {
        if (!WaCarakaDatabase::hasTable('wa_caraka_conversations')) {
            return;
        }

        try {
            $activityAt = $this->resolveMessageActivityAt($message);
            $normalizedRemote = WaCarakaMessage::normalizeRemoteNumber((string) $message->remote_number);
            $normalizedConversationId = WaCarakaMessage::conversationIdFor($normalizedRemote);

            if ($normalizedRemote !== '' && $message->remote_number !== $normalizedRemote) {
                $message->remote_number = $normalizedRemote;
            }

            if ($message->conversation_id !== $normalizedConversationId) {
                $message->conversation_id = $normalizedConversationId;
            }

            if ($message->isDirty(['remote_number', 'conversation_id'])) {
                $message->save();
            }

            $convo = WaCarakaConversation::query()
                ->where('conversation_id', $message->conversation_id)
                ->first();

            if (!$convo) {
                // Prevent duplicate threads for same WA chat when legacy/new conversation keys differ.
                $convo = WaCarakaConversation::query()
                    ->get()
                    ->filter(fn (WaCarakaConversation $item) => WaCarakaMessage::normalizeRemoteNumber((string) $item->remote_number) === $normalizedRemote)
                    ->sortByDesc(fn (WaCarakaConversation $item) => optional($item->last_activity_at)?->getTimestamp() ?? 0)
                    ->first();
            }

            // LID resolution
            if (!$convo && str_ends_with($normalizedRemote, '@lid')) {
                $convo = $this->resolveConversationForLid($normalizedRemote);
            }

            // Determine canonical remote_number
            $isRemoteLid = str_ends_with($normalizedRemote, '@lid');
            $existingRemoteIsPhone = $convo
                && $convo->remote_number
                && !str_ends_with(WaCarakaMessage::normalizeRemoteNumber((string) $convo->remote_number), '@lid');
            $canonicalRemote = ($isRemoteLid && $existingRemoteIsPhone)
                ? WaCarakaMessage::normalizeRemoteNumber((string) $convo->remote_number)
                : $normalizedRemote;

            if (!$convo) {
                $convo = WaCarakaConversation::create([
                    'conversation_id'  => $message->conversation_id,
                    'remote_number'    => $canonicalRemote,
                    'status'           => 'pending',
                    'last_activity_at' => $activityAt,
                ]);
            } elseif ($convo->conversation_id !== $message->conversation_id) {
                $canonicalConversationId = $convo->conversation_id;

                WaCarakaMessage::query()
                    ->where('conversation_id', $canonicalConversationId)
                    ->orWhere('conversation_id', $message->conversation_id)
                    ->update(['conversation_id' => $canonicalConversationId]);

                $message->conversation_id = $canonicalConversationId;
                $message->save();
            }

            $metadata = is_array($message->metadata) ? $message->metadata : [];
            $isHistorySync = (bool) ($metadata['historySync'] ?? false) || (($metadata['syncSource'] ?? null) === 'history');
            $resolvedName = $metadata['groupName']
                ?? $metadata['groupSubject']
                ?? $metadata['senderName']
                ?? $metadata['participantName']
                ?? $metadata['pushName']
                ?? ($metadata['raw']['meta']['notifyName'] ?? null)
                ?? ($metadata['raw']['notifyName'] ?? null)
                ?? null;

            $shouldUpdateName = false;
            if ($resolvedName) {
                if (!$convo->remote_name) {
                    $shouldUpdateName = true;
                    $convo->remote_name = $resolvedName;
                } elseif ($convo->remote_name !== $resolvedName) {
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

            $updateData = [
                'last_activity_at' => $lastActivityAt,
                'remote_number'    => $canonicalRemote,
            ];
            if ($shouldUpdateName) {
                $updateData['remote_name'] = $convo->remote_name;
            }

            // Profile photo caching
            $payloadPhotoUrl = $metadata['profilePhotoUrl'] ?? $metadata['raw']['profilePhotoUrl'] ?? null;
            if (is_string($payloadPhotoUrl) && str_starts_with($payloadPhotoUrl, 'http')) {
                $localPhotoUrl = $this->cacheProfilePhotoLocally($payloadPhotoUrl, $canonicalRemote);
                if ($localPhotoUrl) {
                    $updateData['profile_photo_url'] = $localPhotoUrl;
                } elseif (!$convo->profile_photo_url) {
                    $updateData['profile_photo_url'] = $payloadPhotoUrl;
                }
            }

            if ($message->direction === 'inbound') {
                if ($message->wasRecentlyCreated) {
                    $convo->increment('unread_count');
                }
                if (!$isHistorySync && $convo->status === 'closed') {
                    $rawText = trim($message->message_text ?? '');
                    $text = strtolower($rawText);

                    $isQuestion = str_contains($rawText, '?');
                    $isShort = strlen($rawText) <= 45;

                    $isClosing = false;
                    if (!$isQuestion && $isShort) {
                        $cleanText = trim(preg_replace('/[^\p{L}\p{N}\s]/u', '', $text));
                        $closingRegex = '/^(ok|oke|okey|sip|siap|baik|mantap|ya|iya|terima kasih|makasih|tq|thanks|suwun|nuhun)(?:\s+(min|mas|mbak|pak|bu|kak|bang|infonya|informasinya|banyak))?(?:\s+(terima kasih|makasih|tq|thanks|suwun|nuhun))?(?:\s+(min|mas|mbak|pak|bu|kak|bang))?$/i';

                        if (preg_match($closingRegex, $cleanText) || preg_match('/^(terima kasih|makasih|thanks|tq)/i', $cleanText)) {
                            $isClosing = true;
                        }
                    }

                    if ($isClosing) {
                        $message->update(['replied_at' => now()]);
                    } else {
                        $updateData['status'] = 'pending';
                    }
                }
                $convo->update($updateData);
            } else {
                if (!$isHistorySync) {
                    $updateData['status'] = $convo->status === 'pending' ? 'open' : $convo->status;
                }
                $convo->update($updateData);
            }

            $freshConversation = $convo->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']);
            if ($freshConversation) {
                \App\Events\WaCarakaConversationUpdated::dispatch($freshConversation);
            }
        } catch (\Exception $e) {
            Log::warning('[WaCaraka/Conversation] Failed to sync conversation', [
                'conversation_id' => $message->conversation_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync conversation after storing a message.
     *
     * @param  WaCarakaMessage  $message
     * @return void
     */
    public function syncConversationForMessage(WaCarakaMessage $message): void
    {
        $this->syncConversation($message);
        $this->stats->recordDailyMetricForMessage($message);
    }

    // ══════════════════════════════════════════════
    // LID Resolution
    // ══════════════════════════════════════════════

    /**
     * When an inbound message arrives with a LID (@lid) remote_number and no
     * conversation can be found by direct match, call the runtime to resolve
     * the LID to a real phone number and search for an existing conversation.
     *
     * @param  string  $lidRemote
     * @return WaCarakaConversation|null
     */
    public function resolveConversationForLid(string $lidRemote): ?WaCarakaConversation
    {
        if (!str_ends_with($lidRemote, '@lid')) {
            return null;
        }

        try {
            $response = $this->http->post('/contacts/resolve', ['jids' => [$lidRemote]]);
            $items = $response['data']['items'] ?? [];

            if (empty($items)) {
                return null;
            }

            foreach ($items as $item) {
                $phone = $item['pn']
                    ?? $item['phone']
                    ?? $item['number']
                    ?? $item['resolvedNumber']
                    ?? $item['resolved']
                    ?? null;

                if (!$phone) {
                    continue;
                }

                $normalizedPhone = WaCarakaMessage::normalizeRemoteNumber((string) $phone);
                if ($normalizedPhone === '' || str_ends_with($normalizedPhone, '@lid')) {
                    continue;
                }

                $convo = WaCarakaConversation::query()
                    ->get(['id', 'conversation_id', 'remote_number', 'resolved_number', 'status', 'last_activity_at'])
                    ->filter(function (WaCarakaConversation $item) use ($normalizedPhone) {
                        $byRemote = WaCarakaMessage::normalizeRemoteNumber((string) $item->remote_number) === $normalizedPhone;
                        $byResolved = $item->resolved_number
                            && WaCarakaMessage::normalizeRemoteNumber((string) $item->resolved_number) === $normalizedPhone;

                        return $byRemote || $byResolved;
                    })
                    ->sortByDesc(fn (WaCarakaConversation $c) => optional($c->last_activity_at)?->getTimestamp() ?? 0)
                    ->first();

                if ($convo) {
                    Log::info('[WaCaraka/Conversation] LID resolved to existing conversation', [
                        'lid' => $lidRemote,
                        'resolved_phone' => $normalizedPhone,
                        'conversation_id' => $convo->conversation_id,
                    ]);

                    return $convo;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[WaCaraka/Conversation] LID resolution failed', [
                'lid' => $lidRemote,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    // ══════════════════════════════════════════════
    // Utility Methods
    // ══════════════════════════════════════════════

    /**
     * Resolve the activity timestamp for a message.
     *
     * @param  WaCarakaMessage  $message
     * @return Carbon
     */
    public function resolveMessageActivityAt(WaCarakaMessage $message): Carbon
    {
        $metadata = is_array($message->metadata) ? $message->metadata : [];
        $rawTimestamp = $metadata['timestamp'] ?? ($metadata['raw']['timestamp'] ?? null);

        if (is_numeric($rawTimestamp)) {
            $value = (int) $rawTimestamp;
            if ($value > 9999999999) {
                $value = (int) floor($value / 1000);
            }
            try {
                return Carbon::createFromTimestamp($value);
            } catch (\Throwable $e) {
                // fallback below
            }
        }

        if (is_string($rawTimestamp) && trim($rawTimestamp) !== '') {
            try {
                return Carbon::parse($rawTimestamp);
            } catch (\Throwable $e) {
                // fallback below
            }
        }

        return $message->created_at ?? now();
    }

    /**
     * Download foto profil dari CDN WhatsApp dan simpan ke storage lokal.
     *
     * @param  string  $cdnUrl
     * @param  string  $remoteNumber
     * @return string|null
     */
    public function cacheProfilePhotoLocally(string $cdnUrl, string $remoteNumber): ?string
    {
        try {
            $safeRemote = preg_replace('/[^a-zA-Z0-9_-]/', '_', $remoteNumber);
            $storagePath = 'wa-profiles/' . $safeRemote . '.jpg';

            // Check if already cached and fresh (< 7 days)
            if (Storage::disk('public')->exists($storagePath)) {
                $lastModified = Storage::disk('public')->lastModified($storagePath);
                if ((time() - $lastModified) < 7 * 24 * 3600) {
                    return Storage::disk('public')->url($storagePath);
                }
            }

            // Download from CDN
            $response = \Illuminate\Support\Facades\Http::timeout(8)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; WhatsApp/2.24)'])
                ->get($cdnUrl);

            if ($response->failed() || empty($response->body())) {
                return null;
            }

            $body = $response->body();

            // Validate magic bytes
            $magic = substr($body, 0, 4);
            $isJpeg = str_starts_with($magic, "\xFF\xD8\xFF");
            $isPng = $magic === "\x89PNG";
            if (!$isJpeg && !$isPng) {
                return null;
            }

            $ext = $isPng ? 'png' : 'jpg';
            $storagePath = 'wa-profiles/' . $safeRemote . '.' . $ext;
            Storage::disk('public')->put($storagePath, $body);

            Log::debug('[WaCaraka/Conversation] Profile photo cached', [
                'remote' => $remoteNumber,
                'path' => $storagePath,
                'bytes' => strlen($body),
            ]);

            return Storage::disk('public')->url($storagePath);
        } catch (\Throwable $e) {
            Log::debug('[WaCaraka/Conversation] Profile photo cache failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build message context (group info, sender name, etc.).
     *
     * @param  WaCarakaMessage  $message
     * @return array
     */
    public function messageContext(WaCarakaMessage $message): array
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
            if (preg_match('/@(s\.whatsapp\.net|g\.us|lid)$/i', $participant)) {
                $senderName = null;
            } else {
                $senderName = $participant;
            }
        }

        if (!$senderName) {
            $senderName = $message->direction === 'outbound' ? 'Operator' : null;
        }

        return [
            'is_group' => $isGroup,
            'group_name' => $groupName,
            'sender_name' => $senderName,
            'sender_key' => $participant !== '' ? $participant : ($isGroup ? $remoteJid : $remote),
        ];
    }

    /**
     * Compact message metadata for storage/response.
     *
     * @param  array  $metadata
     * @return array
     */
    public function compactMessageMetadata(array $metadata): array
    {
        if (isset($metadata['raw']) && is_array($metadata['raw'])) {
            unset($metadata['raw']['mediaData']);
        }

        if (isset($metadata['media']) && is_array($metadata['media'])) {
            $kind = strtolower((string) ($metadata['media']['kind'] ?? $metadata['type'] ?? ''));
            $dataUrl = $metadata['media']['dataUrl'] ?? null;
            $url = $metadata['media']['url'] ?? null;

            // Proxy internal runtime URL to Laravel URL
            $internalPattern = '~/internal/media/([a-f0-9]{32,})(?:/([^/?#]*))?~i';

            foreach (['url', 'dataUrl'] as $field) {
                $val = $metadata['media'][$field] ?? null;
                if (is_string($val) && preg_match($internalPattern, $val, $m)) {
                    $token = strtolower($m[1]);
                    $filename = $m[2] ?? '';
                    $proxyPath = $filename !== '' ? $token . '/' . $filename : $token;
                    $proxyUrl = route('lawangsewu.wacaraka.media', ['path' => $proxyPath]);
                    $metadata['media'][$field] = $proxyUrl;
                    $metadata['media']['url'] = $proxyUrl;
                    $metadata['media']['dataUrl'] = $proxyUrl;
                    break;
                }
            }

            $dataUrl = $metadata['media']['dataUrl'] ?? null;

            if (
                is_string($dataUrl)
                && !in_array($kind, ['image', 'sticker'], true)
                && !str_starts_with($dataUrl, '/')
                && !str_starts_with($dataUrl, 'http')
            ) {
                unset($metadata['media']['dataUrl']);
            }
        }

        return $metadata;
    }
}