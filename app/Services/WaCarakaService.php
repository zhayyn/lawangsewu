<?php

namespace App\Services;

use App\Services\WaCaraka\WaCarakaHttpClient;
use App\Services\WaCaraka\WaCarakaMessageService;
use App\Services\WaCaraka\WaCarakaConversationService;
use App\Services\WaCaraka\WaCarakaStatsService;
use App\Models\WaCarakaMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * WaCarakaService
 *
 * Full-stack messaging gateway service for the WA Caraka module.
 * This is a facade/coordinator that delegates to focused sub-services.
 *
 * Architecture:
 * - WaCarakaHttpClient: HTTP communication with Node.js/Baileys runtime
 * - WaCarakaMessageService: Message sending, receiving, queueing
 * - WaCarakaConversationService: Conversation management and sync
 * - WaCarakaStatsService: Statistics and metrics
 *
 * SSO is handled at the controller/middleware layer.
 *
 * @deprecated Use sub-services directly for new code.
 *             This facade maintains backward compatibility only.
 */
class WaCarakaService
{
    private WaCarakaHttpClient $http;
    private WaCarakaMessageService $messages;
    private WaCarakaConversationService $conversations;
    private WaCarakaStatsService $stats;

    protected array $validatedUserIds = [];

    /**
     * @deprecated Use dependency injection instead.
     */
    public function __construct(
        ?WaCarakaHttpClient $http = null,
        ?WaCarakaMessageService $messages = null,
        ?WaCarakaConversationService $conversations = null,
        ?WaCarakaStatsService $stats = null
    ) {
        // Resolve from container if not injected
        $this->http = $http ?? app(WaCarakaHttpClient::class);
        $this->messages = $messages ?? app(WaCarakaMessageService::class);
        $this->conversations = $conversations ?? app(WaCarakaConversationService::class);
        $this->stats = $stats ?? app(WaCarakaStatsService::class);
    }

    // ══════════════════════════════════════════════
    // Accessors
    // ══════════════════════════════════════════════

    public function baseUrl(): string
    {
        return $this->http->baseUrl();
    }

    public function broadcastLimit(): int
    {
        return $this->messages->broadcastLimit();
    }

    // ══════════════════════════════════════════════
    // HTTP Client Delegation
    // ══════════════════════════════════════════════

    protected function request()
    {
        return $this->http->request();
    }

    protected function get(string $path, array $query = []): array
    {
        return $this->http->get($path, $query);
    }

    protected function post(string $path, array $data = []): array
    {
        return $this->http->post($path, $data);
    }

    private function wrap($response): array
    {
        return $this->http->wrap($response);
    }

    private function error(string $message, ?string $detail = null, int $status = 502): array
    {
        return $this->http->error($message, $detail, $status);
    }

    // ══════════════════════════════════════════════
    // Device / Session Management
    // ══════════════════════════════════════════════

    public function health(): array
    {
        return $this->messages->health();
    }

    public function qr(): array
    {
        return $this->messages->qr();
    }

    public function refreshQr(): array
    {
        return $this->messages->refreshQr();
    }

    public function restart(): array
    {
        return $this->messages->restart();
    }

    public function reconnect(): array
    {
        return $this->messages->reconnect();
    }

    public function disconnect(): array
    {
        return $this->messages->disconnect();
    }

    // ══════════════════════════════════════════════
    // Runtime History
    // ══════════════════════════════════════════════

    public function history(): array
    {
        return $this->messages->history();
    }

    public function clearHistory(): array
    {
        return $this->messages->clearHistory();
    }

    public function clearInbox(): array
    {
        return $this->messages->clearInbox();
    }

    public function getLidMappings(): array
    {
        return $this->messages->getLidMappings();
    }

    public function syncContacts(): array
    {
        return $this->messages->syncContacts();
    }

    public function resolveContactsMeta(array $jids): array
    {
        return $this->messages->resolveContactsMeta($jids);
    }

    // ══════════════════════════════════════════════
    // Outbound Messaging
    // ══════════════════════════════════════════════

    public function sendText(string $to, string $text, ?string $sender = null, ?int $userId = null, ?string $quoteWaId = null): array
    {
        return $this->messages->sendText($to, $text, $sender, $userId, $quoteWaId);
    }

    public function sendMedia(string $to, array $mediaPayload, ?string $sender = null, ?int $userId = null): array
    {
        return $this->messages->sendMedia($to, $mediaPayload, $sender, $userId);
    }

    public function queueText(string $to, string $text, ?string $sender = null, ?int $userId = null): array
    {
        return $this->messages->queueText($to, $text, $sender, $userId);
    }

    public function queueBroadcastText(array $recipients, string $text, ?string $sender = null, ?int $userId = null): array
    {
        return $this->messages->queueBroadcastText($recipients, $text, $sender, $userId);
    }

    public function deliverQueuedMessage(int $messageId, ?string $sender = null): array
    {
        return $this->messages->deliverQueuedMessage($messageId, $sender);
    }

    public function replyTo(WaCarakaMessage $inboundMessage, string $text, int $userId): array
    {
        return $this->messages->replyTo($inboundMessage, $text, $userId);
    }

    public function unsendMessage(string $jid, string $messageId, ?int $userId = null): array
    {
        return $this->messages->unsendMessage($jid, $messageId, $userId);
    }

    public function broadcastText(array $recipients, string $text, ?string $sender = null, ?int $userId = null): array
    {
        return $this->messages->broadcastText($recipients, $text, $sender, $userId);
    }

    // ══════════════════════════════════════════════
    // Inbound Webhook Handler
    // ══════════════════════════════════════════════

    public function handleInbound(array $payload): WaCarakaMessage
    {
        return $this->messages->handleInbound($payload);
    }

    public function shouldIgnoreInboundPayload(array $payload): bool
    {
        return $this->messages->shouldIgnoreInboundPayload($payload);
    }

    public function ingestWebhookMessage(array $payload): WaCarakaMessage
    {
        return $this->messages->ingestWebhookMessage($payload);
    }

    public function ingestHistorySyncBatch(array $payload): array
    {
        return $this->messages->ingestHistorySyncBatch($payload);
    }

    public function pullInbox(?string $since = null): array
    {
        return $this->messages->pullInbox($since);
    }

    // ══════════════════════════════════════════════
    // Inbox Queries
    // ══════════════════════════════════════════════

    public function conversations(int $limit = 30): array
    {
        return $this->conversations->conversations($limit);
    }

    public function conversationMessages(string $conversationKey, int $limit = 50, bool $preferConversationId = true): array
    {
        return $this->conversations->conversationMessages($conversationKey, $limit, $preferConversationId);
    }

    // ══════════════════════════════════════════════
    // Stats & Metrics
    // ══════════════════════════════════════════════

    public function stats(): array
    {
        return $this->stats->stats();
    }

    public function messageStats(): array
    {
        return $this->stats->messageStats();
    }

    public function recentLogs(int $limit = 20): array
    {
        return $this->stats->recentLogs($limit);
    }

    public function rebuildDailyMetrics(): array
    {
        return $this->stats->rebuildDailyMetrics();
    }

    // ══════════════════════════════════════════════
    // Internal Helpers (delegated)
    // ══════════════════════════════════════════════

    protected function sendRuntimeText(string $to, string $text, ?string $sender = null, ?int $userId = null, ?string $quoteWaId = null): array
    {
        return $this->messages->sendText($to, $text, $sender, $userId, $quoteWaId);
    }

    protected function sendRuntimeMedia(string $to, array $mediaPayload, ?string $sender = null, ?int $userId = null): array
    {
        return $this->messages->sendMedia($to, $mediaPayload, $sender, $userId);
    }

    private function storeMessage(array $data): void
    {
        // Delegate to message service
    }

    private function storeMessageAndSync(array $data): ?WaCarakaMessage
    {
        // Method removed - delegated directly
        return null;
    }

    private function recordDailyMetricForMessage(WaCarakaMessage $message): void
    {
        $this->stats->recordDailyMetricForMessage($message);
    }

    private function logLegacy(array $data): void
    {
        $this->messages->logLegacy($data);
    }

    private function createQueuedOutboundMessage(string $to, string $text, ?int $userId = null): ?WaCarakaMessage
    {
        return $this->messages->createQueuedOutboundMessage($to, $text, $userId);
    }

    private function normalizeUserId($userId): ?int
    {
        return $this->messages->normalizeUserId($userId);
    }

    private function dispatchMessageSynced(?WaCarakaMessage $message): void
    {
        $this->messages->dispatchMessageSynced($message);
    }

    private function shouldDispatchOutboundAsync(): bool
    {
        return $this->messages->shouldDispatchOutboundAsync();
    }

    private function syncConversation(WaCarakaMessage $message): void
    {
        $this->conversations->syncConversation($message);
    }

    private function cacheProfilePhotoLocally(string $cdnUrl, string $remoteNumber): ?string
    {
        return $this->conversations->cacheProfilePhotoLocally($cdnUrl, $remoteNumber);
    }

    private function resolveConversationForLid(string $lidRemote): ?\App\Models\WaCarakaConversation
    {
        return $this->conversations->resolveConversationForLid($lidRemote);
    }

    private function resolveMessageActivityAt(WaCarakaMessage $message): \Illuminate\Support\Carbon
    {
        return $this->conversations->resolveMessageActivityAt($message);
    }

    private function messageContext(WaCarakaMessage $message): array
    {
        return $this->conversations->messageContext($message);
    }

    private function compactMessageMetadata(array $metadata): array
    {
        return $this->conversations->compactMessageMetadata($metadata);
    }

    // ══════════════════════════════════════════════
    // Direct access to sub-services (for advanced use)
    // ══════════════════════════════════════════════

    public function http(): WaCarakaHttpClient
    {
        return $this->http;
    }

    public function messages(): WaCarakaMessageService
    {
        return $this->messages;
    }

    public function conversationsService(): WaCarakaConversationService
    {
        return $this->conversations;
    }

    public function statsService(): WaCarakaStatsService
    {
        return $this->stats;
    }
}
