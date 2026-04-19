<?php

namespace App\Http\Controllers;

use App\Models\WaCarakaConversation;
use App\Models\WaCarakaConversationMark;
use App\Models\WaCarakaHandover;
use App\Models\WaCarakaMessage;
use App\Models\WaCarakaTicket;
use App\Models\User;
use App\Services\WaCarakaConversationService;
use App\Services\WaCarakaService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WaCarakaController extends Controller
{
    protected WaCarakaService $waService;
    protected WaCarakaConversationService $conversationService;

    public function __construct(WaCarakaService $waService, WaCarakaConversationService $conversationService)
    {
        $this->waService = $waService;
        $this->conversationService = $conversationService;
    }

    /**
     * Display the WaCaraka Dashboard (Operator Inbox / Control Center).
     */
    public function index(): Response
    {
        $user = auth()->user();

        return Inertia::render('Lawangsewu/WaCaraka/Index', [
            'appMeta'       => LawangsewuPortal::appMeta(),
            'navGroups'     => LawangsewuPortal::navGroups(),
            'authUser'      => [
                'id'           => $user->id,
                'name'         => $user->name,
                'alias'        => $user->alias,
                'role'         => $user->role,
                'isAdmin'      => $user->isSuperAdmin() || $user->role === 'admin',
                'isSuperAdmin' => $user->isSuperAdmin(),
            ],
            // Runtime config info for UI display
            'config'        => [
                'baseUrl'        => $this->waService->baseUrl(),
                'broadcastLimit' => $this->waService->broadcastLimit(),
                'loggingEnabled' => config('wa_caraka.logging_enabled', true),
                'timeout'        => config('wa_caraka.timeout', 20),
                'background'     => \Illuminate\Support\Facades\Cache::get('wacaraka_background'),
            ],
            // Legacy log stats (wa_caraka_logs table)
            'stats'         => $this->waService->stats(),
            'messageStats'  => $this->waService->messageStats(),
            'convoStats'    => $this->getConvoStats(),
            'ticketStats'   => WaCarakaTicket::ticketStats(),
            'recentTickets' => WaCarakaTicket::recent(30),
        ]);
    }

    public function reports(): Response
    {
        $user = auth()->user();
        if (!$this->isAdmin($user)) {
            abort(403, 'Halaman ini hanya untuk admin/superadmin.');
        }

        return Inertia::render('Lawangsewu/WaCaraka/Reports', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'authUser' => [
                'id'           => $user->id,
                'name'         => $user->name,
                'alias'        => $user->alias,
                'role'         => $user->role,
                'isAdmin'      => $this->isAdmin($user),
                'isSuperAdmin' => $user->isSuperAdmin(),
            ],
            'reportStats' => $this->reportStatsData(),
        ]);
    }

    public function reportsData(): JsonResponse
    {
        $user = auth()->user();
        if (!$this->isAdmin($user)) {
            return response()->json(['error' => 'Aksi ini hanya dapat dilakukan oleh admin/superadmin.'], 403);
        }

        return response()->json($this->reportStatsData());
    }

    /**
     * Unified Proxy / Action Router for the Frontend.
     */
    public function proxy(Request $request, string $action): JsonResponse
    {
        $user = $request->user();
        $sender = $user?->name ?? $user?->email ?? 'operator';
        $userId = $user?->id;

        try {
            return match ($action) {
                // ──────────────────────────────────────────────
                // 1. Runtime / Device Logic (Direct Proxy)
                // ──────────────────────────────────────────────
                'health'      => response()->json($this->waService->health()['data'] ?? []),
                'qr'          => response()->json($this->waService->qr()['data'] ?? []),
                'refresh-qr'  => response()->json($this->waService->refreshQr()['data'] ?? ['ok' => true]),
                'restart'     => $this->adminOnlyRuntimeAction($user, fn () => $this->waService->restart()),
                'reconnect'   => $this->adminOnlyRuntimeAction($user, fn () => $this->waService->reconnect()),
                'disconnect'  => $this->adminOnlyRuntimeAction($user, fn () => $this->waService->disconnect()),
                'history'     => response()->json($this->waService->history()['data'] ?? []),
                'history/clear' => $this->adminOnlyRuntimeAction($user, fn () => $this->waService->clearHistory()),
                'lid-mappings'  => response()->json($this->waService->getLidMappings()['data'] ?? []),
                'sync-contacts' => $this->adminOnlyRuntimeAction($user, fn () => $this->syncContactsAndUpdateNames()),

                // ──────────────────────────────────────────────
                // 2. Messaging & Inbox (Local DB + Service)
                // ──────────────────────────────────────────────
                'inbox' => $this->getInbox(),
                'conversation' => $this->getConversationMessages($request),
                'mark-read' => $this->markAsRead($request),
                'pull-inbox' => response()->json($this->waService->pullInbox()['data']),
                
                'reply' => $this->sendReply($request),
                'send-text' => $this->sendDirect($request, $sender, $userId),
                'broadcast' => $this->broadcast($request, $user),

                // ──────────────────────────────────────────────
                // 3. Handover / Ownership
                // ──────────────────────────────────────────────
                'request-handover' => $this->requestHandover($request),
                'approve-handover' => $this->respondHandover($request, true),
                'reject-handover'  => $this->respondHandover($request, false),
                'force-handover'   => $this->forceHandover($request),
                'close'            => $this->closeConversation($request),
                'mark-customer'    => $this->markCustomer($request),
                'unmark-customer'  => $this->unmarkCustomer($request),

                // ──────────────────────────────────────────────
                // 4. Tickets (Pengaduan / Konsultasi)
                // ──────────────────────────────────────────────
                'tickets'      => response()->json(WaCarakaTicket::recent(
                    max(1, min((int) $request->input('limit', 30), 100)),
                    $request->input('type'),
                    $request->input('status')
                )),
                'ticket-stats' => response()->json(WaCarakaTicket::ticketStats()),
                'ticket-reply' => $this->saveTicketReply($request),
                'ticket-send'  => $this->sendTicketReply($request),
                'ticket-transfer' => $this->transferTicket($request),
                'ticket-close'    => $this->closeTicket($request),

                // ──────────────────────────────────────────────
                // 5. Manage Messages
                // ──────────────────────────────────────────────
                'delete-message'  => $this->deleteMessage($request),
                'clear-conversation' => $this->superAdminOnlyAction($user, fn () => $this->clearConversationState($request)),
                'report-stats' => $this->adminOnlyRuntimeAction($user, fn () => [
                    'ok' => true,
                    'status' => 200,
                    'data' => $this->reportStatsData(),
                ]),

                // ──────────────────────────────────────────────
                // 5. Admin Tools
                // ──────────────────────────────────────────────
                'reset-state'   => $this->adminOnlyRuntimeAction($user, fn () => $this->softResetState()),
                'clear-inbox'   => $this->superAdminOnlyAction($user, fn () => $this->clearInboxState()),
                'toggle-handover-enabled' => $this->superAdminOnlyAction($user, fn () => $this->toggleHandoverEnabled($request)),
                'get-handover-enabled' => response()->json(['ok' => true, 'handoverEnabled' => \App\Models\WaCarakaSetting::get('handover_enabled', true)]),

                // ──────────────────────────────────────────────
                // 6. Global Stats
                // ──────────────────────────────────────────────
                'stats'         => response()->json($this->waService->stats()),
                'logs'          => response()->json($this->waService->recentLogs(100)),
                'message-stats' => response()->json($this->waService->messageStats()),
                'convo-stats'   => response()->json($this->getConvoStats()),

                default => response()->json(['error' => "Action '$action' not defined."], 404),
            };
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('[WaCaraka] Proxy action failed', [
                'action' => $action,
                'user_id' => $user?->id,
                'message' => $e->getMessage(),
                'class' => $e::class,
            ]);

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════
    // Inbox / Conversation Helpers
    // ══════════════════════════════════════════════

    protected function getInbox(): JsonResponse
    {
        $user = auth()->user();

        $conversationRows = WaCarakaConversation::query()
            ->with('owner:id,name,alias', 'pendingHandover.requestor:id,name,alias')
            ->whereIn('conversation_id', function ($query) {
                $query->from('wa_caraka_messages')
                    ->select('conversation_id')
                    ->whereNotNull('conversation_id')
                    ->groupBy('conversation_id');
            })
            ->orderByDesc('last_activity_at')
            ->limit(200)
            ->get()
            // Defensive dedupe: one inbox row per remote number.
            ->unique('remote_number')
            ->values();

        $marksByConversation = WaCarakaConversationMark::query()
            ->where('user_id', $user->id)
            ->whereIn('wa_caraka_conversation_id', $conversationRows->pluck('id'))
            ->get()
            ->keyBy('wa_caraka_conversation_id');

        // Latest message for ordering/recency.
        $latestMessageIds = WaCarakaMessage::query()
            ->selectRaw('MAX(id) as latest_id')
            ->whereIn('conversation_id', $conversationRows->pluck('conversation_id'))
            ->groupBy('conversation_id');

        $latestMessageByConversation = WaCarakaMessage::query()
            ->whereIn('id', $latestMessageIds)
            ->select('conversation_id', 'metadata', 'created_at')
            ->get()
            ->keyBy('conversation_id');

        // Prefer latest inbound metadata for profile photo (outbound metadata often lacks avatar fields).
        $latestInboundMessageIds = WaCarakaMessage::query()
            ->selectRaw('MAX(id) as latest_id')
            ->whereIn('conversation_id', $conversationRows->pluck('conversation_id'))
            ->where('direction', 'inbound')
            ->groupBy('conversation_id');

        $latestInboundMessageByConversation = WaCarakaMessage::query()
            ->whereIn('id', $latestInboundMessageIds)
            ->select('conversation_id', 'metadata')
            ->get()
            ->keyBy('conversation_id');

        $runtimeMetaResponse = $this->waService->resolveContactsMeta(
            $conversationRows->pluck('remote_number')->filter()->values()->all()
        );

        $runtimeMetaByRemoteNumber = collect(is_array($runtimeMetaResponse['data']['items'] ?? null)
            ? $runtimeMetaResponse['data']['items']
            : [])
            ->filter(fn ($item) => is_array($item) && !empty($item['jid']))
            ->keyBy('jid');

        $conversations = $conversationRows
            ->map(function ($c) use ($user, $marksByConversation, $latestMessageByConversation, $latestInboundMessageByConversation, $runtimeMetaByRemoteNumber) {
                $permission = $this->conversationService->canReply($c, $user);
                $isGroup = str_ends_with((string) $c->remote_number, '@g.us');
                $latestAny = $latestMessageByConversation->get($c->conversation_id);
                $latestInbound = $latestInboundMessageByConversation->get($c->conversation_id);
                $metadata = is_array($latestAny?->metadata) ? $latestAny->metadata : [];
                $inboundMetadata = is_array($latestInbound?->metadata) ? $latestInbound->metadata : [];
                $runtimeMeta = $runtimeMetaByRemoteNumber->get($c->remote_number);
                $runtimeMeta = is_array($runtimeMeta) ? $runtimeMeta : [];

                // Use server-recorded last_activity_at for ordering (matches WhatsApp Web behaviour:
                // newest received/sent at the top, not by the WA message creation timestamp).
                $effectiveLastActivity = $c->last_activity_at ?? $latestAny?->created_at;

                $resolvedName = $this->resolveConversationDisplayName($c, $metadata, $runtimeMeta);
                $groupName = $isGroup
                    ? ($this->extractGroupName($metadata, $runtimeMeta)
                        ?: $resolvedName
                        ?: ('Grup ' . preg_replace('/@g\.us$/', '', (string) $c->remote_number)))
                    : null;
                $displayTitle = $isGroup
                    ? ($groupName ?: 'Grup WhatsApp')
                    : ($resolvedName ?: $c->remote_number);

                $conversation = [
                'conversationId'   => $c->conversation_id,
                'remoteNumber'     => $c->remote_number,
                'remoteName'       => $resolvedName,
                'displayTitle'     => $displayTitle,
                'isGroup'          => $isGroup,
                'groupName'        => $groupName,
                'status'           => $c->status,
                'unreadCount'      => $c->unread_count,
                'lastActivityAt'   => $effectiveLastActivity?->diffForHumans(),
                'lastActivityTs'   => $effectiveLastActivity?->getTimestamp(),
                'claimedAt'        => $c->claimed_at?->diffForHumans(),
                'owner'            => $c->owner ? ['id' => $c->owner->id, 'name' => $c->owner->name, 'alias' => $c->owner->alias] : null,
                'ownership'        => $c->claimed_by === null ? 'unclaimed' : ($c->claimed_by === $user?->id ? 'mine' : 'locked'),
                'ownerPresence'    => $this->ownerPresenceFor($c),
                'justClaimed'      => $this->wasJustClaimed($c),
                'canReply'         => $permission['allowed'],
                'lockReason'       => $permission['reason'],
                'pendingHandover'  => $c->pendingHandover ? [
                    'id'        => $c->pendingHandover->id,
                    'requestor' => ['id' => $c->pendingHandover->requestor->id, 'name' => $c->pendingHandover->requestor->name],
                ] : null,
                'customerMark'     => null,
                'profilePhotoUrl'  => $this->extractProfilePhotoUrl($inboundMetadata, $runtimeMeta),
                ];

                $mark = $marksByConversation->get($c->id);
                if (!$mark) {
                    return $conversation;
                }

                $conversation['customerMark'] = [
                    'label' => $mark->label,
                    'tone' => $mark->tone,
                    'note' => $mark->note,
                    'isPinned' => (bool) $mark->is_pinned,
                ];

                return $conversation;
            })
            ->sortByDesc(fn (array $conversation) => (int) ($conversation['lastActivityTs'] ?? 0))
            ->take(50)
            ->values();

        return response()->json(['conversations' => $conversations]);
    }

    protected function effectiveMessageTime(?WaCarakaMessage $message, ?Carbon $fallback = null): ?Carbon
    {
        if (!$message) {
            return $fallback;
        }

        $metadata = is_array($message->metadata) ? $message->metadata : [];
        $rawTs = $metadata['timestamp'] ?? ($metadata['raw']['timestamp'] ?? null);

        if (is_numeric($rawTs)) {
            $value = (int) $rawTs;
            if ($value > 9999999999) {
                $value = (int) floor($value / 1000);
            }
            try {
                return Carbon::createFromTimestamp($value);
            } catch (\Throwable $e) {
                // fallback below
            }
        }

        if (is_string($rawTs) && trim($rawTs) !== '') {
            try {
                return Carbon::parse($rawTs);
            } catch (\Throwable $e) {
                // fallback below
            }
        }

        return $message->created_at ?? $fallback;
    }

    protected function markCustomer(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'conversation_id' => ['required', 'string'],
            'label' => ['required', 'string', 'max:40'],
            'tone' => ['nullable', 'string', 'in:amber,emerald,rose,sky,violet,slate'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $conversation = WaCarakaConversation::query()
            ->where('conversation_id', $payload['conversation_id'])
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        $mark = WaCarakaConversationMark::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'wa_caraka_conversation_id' => $conversation->id,
            ],
            [
                'label' => trim($payload['label']),
                'tone' => $payload['tone'] ?? 'amber',
                'note' => isset($payload['note']) ? trim((string) $payload['note']) : null,
                'is_pinned' => (bool) ($payload['is_pinned'] ?? false),
            ],
        );

        return response()->json([
            'ok' => true,
            'mark' => [
                'label' => $mark->label,
                'tone' => $mark->tone,
                'note' => $mark->note,
                'isPinned' => (bool) $mark->is_pinned,
            ],
        ]);
    }

    protected function unmarkCustomer(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'conversation_id' => ['required', 'string'],
        ]);

        $conversation = WaCarakaConversation::query()
            ->where('conversation_id', $payload['conversation_id'])
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        WaCarakaConversationMark::query()
            ->where('user_id', $request->user()->id)
            ->where('wa_caraka_conversation_id', $conversation->id)
            ->delete();

        return response()->json(['ok' => true]);
    }

    protected function getConversationMessages(Request $request): JsonResponse
    {
        $remoteNumber = $request->input('remote_number');
        if (!$remoteNumber) return response()->json(['error' => 'Remote number is required'], 422);

        $messages = $this->waService->conversationMessages($remoteNumber);
        return response()->json(['messages' => $messages]);
    }

    protected function markAsRead(Request $request): JsonResponse
    {
        $convoId = $request->input('conversation_id');
        $convo = WaCarakaConversation::where('conversation_id', $convoId)->first();
        if ($convo) $convo->markRead();
        return response()->json(['ok' => true]);
    }

    protected function sendReply(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Sesi login tidak valid. Silakan muat ulang halaman dan login kembali.'], 401);
        }

        $convoId = trim((string) $request->input('conversation_id', ''));
        $text = trim((string) $request->input('text', ''));

        if ($convoId === '' || $text === '') {
            return response()->json(['error' => 'conversation_id dan text wajib diisi.'], 422);
        }

        $convo = WaCarakaConversation::where('conversation_id', $convoId)->first();
        if (!$convo) return response()->json(['error' => 'Conversation not found'], 404);

        $permission = $this->conversationService->canReply($convo, $user);
        if (!$permission['allowed']) {
            return response()->json(['error' => $permission['reason'] ?? 'This conversation is claimed by another operator'], 403);
        }

        try {
            $convo = $this->conversationService->claimForReply($convo, $user);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['error' => 'Percakapan sedang diproses operator lain. Coba kirim ulang.'], 409);
        }

        $target = $this->resolveReplyTarget($convo);

        $res = $this->waService->queueText($target, $text, 'OPERATOR', $user->id);

        if ($res['ok']) {
            // Mark all unreplied inbound messages in this conversation as replied
            WaCarakaMessage::where('conversation_id', $convo->conversation_id)
                ->where('direction', 'inbound')
                ->whereNull('replied_at')
                ->update(['replied_at' => now()]);
        }

        return response()->json([
            'ok' => $res['ok'],
            'data' => $res['data'] ?? null,
            'error' => $res['error'] ?? null,
            'queued' => $res['queued'] ?? false,
            'conversation' => [
                'conversationId' => $convo->conversation_id,
                'owner' => $convo->owner ? ['id' => $convo->owner->id, 'name' => $convo->owner->name, 'alias' => $convo->owner->alias] : null,
                'claimedAt' => $convo->claimed_at?->diffForHumans(),
                'status' => $convo->status,
            ],
        ]);
    }

    protected function sendDirect(Request $request, string $sender, ?int $userId): JsonResponse
    {
        $validated = $request->validate([
            'to'   => 'required|string|min:8|max:20',
            'text' => 'required|string|max:4096',
        ]);

        $res = $this->waService->queueText($validated['to'], $validated['text'], $sender, $userId);
        return response()->json(['ok' => $res['ok'], 'data' => $res['data'] ?? null, 'error' => $res['error'] ?? null, 'queued' => $res['queued'] ?? false]);
    }

    private function resolveReplyTarget(WaCarakaConversation $convo): string
    {
        $remote = (string) $convo->remote_number;

        if (str_ends_with($remote, '@g.us') || str_ends_with($remote, '@lid')) {
            return $remote;
        }

        $latestInbound = WaCarakaMessage::where('conversation_id', $convo->conversation_id)
            ->where('direction', 'inbound')
            ->latest('id')
            ->first(['metadata']);

        if (!$latestInbound || !is_array($latestInbound->metadata)) {
            return $remote;
        }

        $lid = data_get($latestInbound->metadata, 'fromRaw')
            ?? data_get($latestInbound->metadata, 'fromLid')
            ?? data_get($latestInbound->metadata, 'raw.fromRaw')
            ?? data_get($latestInbound->metadata, 'raw.fromLid');

        if (is_string($lid) && str_ends_with($lid, '@lid')) {
            return $lid;
        }

        return $remote;
    }

    protected function broadcast(Request $request, $user): JsonResponse
    {
        if (!$this->isAdmin($user)) {
            return response()->json(['error' => 'Aksi ini hanya dapat dilakukan oleh admin.'], 403);
        }

        $validated = $request->validate([
            'recipients'   => 'required|array|min:1|max:' . $this->waService->broadcastLimit(),
            'recipients.*' => 'required|string|min:8|max:20',
            'text'         => 'required|string|max:4096',
        ]);

        $res = $this->waService->queueBroadcastText(
            $validated['recipients'],
            $validated['text'],
            $user?->name ?? $user?->email ?? 'operator',
            $user?->id,
        );

        return response()->json($res['data'] ?? ['ok' => $res['ok']], $res['status'] ?? 200);
    }

    // ══════════════════════════════════════════════
    // Handover Helpers
    // ══════════════════════════════════════════════

    protected function requestHandover(Request $request): JsonResponse
    {
        // Check if handover is enabled
        if (!\App\Models\WaCarakaSetting::get('handover_enabled', true)) {
            return response()->json(['error' => 'Fitur alih chat sedang dinonaktifkan'], 403);
        }

        $convoId = $request->input('conversation_id');
        $reason  = $request->input('reason');

        $convo = WaCarakaConversation::where('conversation_id', $convoId)->first();
        if (!$convo || !$convo->claimed_by) return response()->json(['error' => 'Invalid conversation'], 422);

        $handover = WaCarakaHandover::create([
            'conversation_id' => $convo->id,
            'requested_by'    => auth()->id(),
            'requested_to'    => $convo->claimed_by,
            'status'          => 'pending',
            'reason'          => $reason,
        ]);

        return response()->json(['ok' => true, 'handover_id' => $handover->id]);
    }

    protected function respondHandover(Request $request, bool $approve): JsonResponse
    {
        $handoverId = $request->input('handover_id');
        $handover   = WaCarakaHandover::findOrFail($handoverId);

        if ($handover->requested_to !== auth()->id() && !auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Unauthorized response'], 403);
        }

        if ($approve) $handover->approve();
        else $handover->reject();

        return response()->json(['ok' => true]);
    }

    protected function forceHandover(Request $request): JsonResponse
    {
        $convoId = $request->input('conversation_id');
        $convo   = WaCarakaConversation::where('conversation_id', $convoId)->first();

        if (!auth()->user()->isSuperAdmin() && auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Admin only'], 403);
        }

        $this->conversationService->forceHandover($convo, auth()->user());
        return response()->json(['ok' => true]);
    }

    protected function closeConversation(Request $request): JsonResponse
    {
        $convoId = $request->input('conversation_id');
        $convo   = WaCarakaConversation::where('conversation_id', $convoId)->first();

        if (!$convo) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        return response()->json($this->conversationService->closeConversation($convo, auth()->user()));
    }

    protected function ownerPresenceFor(WaCarakaConversation $conversation): ?string
    {
        if (!$conversation->claimed_by || !$conversation->last_activity_at) {
            return null;
        }

        if ($conversation->last_activity_at->gte(now()->subMinutes(2))) {
            return 'active';
        }

        if ($conversation->last_activity_at->gte(now()->subMinutes(10))) {
            return 'standby';
        }

        return 'idle';
    }

    protected function wasJustClaimed(WaCarakaConversation $conversation): bool
    {
        return (bool) $conversation->claimed_at?->gte(now()->subMinutes(3));
    }

    // ══════════════════════════════════════════════
    // Ticket Helpers
    // ══════════════════════════════════════════════

    protected function saveTicketReply(Request $request): JsonResponse
    {
        $ticketId = $request->input('ticket_id');
        $reply    = $request->input('reply');

        $ticket = WaCarakaTicket::findOrFail($ticketId);
        $ticket->update([
            'reply'       => $reply,
            'status'      => 'replied',
            'replied_at'  => now(),
            'assigned_to' => auth()->id(),
        ]);

        return response()->json(['ok' => true]);
    }

    protected function sendTicketReply(Request $request): JsonResponse
    {
        $ticketId = $request->input('ticket_id');
        $ticket   = WaCarakaTicket::findOrFail($ticketId);

        if (!$ticket->reply) return response()->json(['error' => 'Reply content is empty'], 422);

        $fullReply = $ticket->replyPrefix() . "\n\n" . $ticket->reply;
        $res = $this->waService->sendText($ticket->remote_number, $fullReply, 'TICKET_REPLY', auth()->id());

        if ($res['ok']) {
            $ticket->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
        }

        return response()->json(['ok' => $res['ok'], 'error' => $res['error'] ?? null]);
    }

    protected function transferTicket(Request $request): JsonResponse
    {
        $ticketId = $request->input('ticket_id');
        $newType  = $request->input('new_type');

        if (!in_array($newType, ['pengaduan', 'konsultasi', 'umum'], true)) {
            return response()->json(['error' => 'Tipe tiket tidak valid'], 422);
        }

        $ticket = WaCarakaTicket::findOrFail($ticketId);
        $ticket->update(['type' => $newType]);

        return response()->json(['ok' => true]);
    }

    protected function closeTicket(Request $request): JsonResponse
    {
        $ticketId = $request->input('ticket_id');
        $ticket   = WaCarakaTicket::findOrFail($ticketId);
        $ticket->update(['status' => 'closed']);

        return response()->json(['ok' => true]);
    }

    // ══════════════════════════════════════════════
    // Manage Messages Helpers
    // ══════════════════════════════════════════════

    protected function deleteMessage(Request $request): JsonResponse
    {
        if (!$this->isAdmin(auth()->user())) {
            return response()->json(['error' => 'Aksi ini hanya dapat dilakukan oleh admin/superadmin.'], 403);
        }

        $messageId = $request->input('message_id');
        $message = WaCarakaMessage::find($messageId);

        if ($message) {
            $message->delete();
        }

        return response()->json(['ok' => true]);
    }

    protected function clearConversationState(Request $request): array
    {
        $conversationId = (string) $request->input('conversation_id', '');
        if ($conversationId === '') {
            return ['ok' => false, 'status' => 422, 'error' => 'conversation_id wajib diisi.'];
        }

        $conversation = WaCarakaConversation::query()
            ->where('conversation_id', $conversationId)
            ->first();

        if (!$conversation) {
            return ['ok' => true, 'status' => 200, 'data' => ['ok' => true, 'deleted' => ['messages' => 0, 'marks' => 0, 'handovers' => 0, 'conversation' => 0]]];
        }

        $deleted = ['messages' => 0, 'marks' => 0, 'handovers' => 0, 'conversation' => 0];

        DB::transaction(function () use (&$deleted, $conversation) {
            $deleted['handovers'] = WaCarakaHandover::query()->where('conversation_id', $conversation->id)->delete();
            $deleted['marks'] = WaCarakaConversationMark::query()->where('wa_caraka_conversation_id', $conversation->id)->delete();
            $deleted['messages'] = WaCarakaMessage::query()->where('conversation_id', $conversation->conversation_id)->delete();
            $deleted['conversation'] = WaCarakaConversation::query()->where('id', $conversation->id)->delete();
        });

        return ['ok' => true, 'status' => 200, 'data' => ['ok' => true, 'deleted' => $deleted]];
    }

    // ══════════════════════════════════════════════
    // Meta / Stats
    // ══════════════════════════════════════════════

    protected function getConvoStats(): array
    {
        return [
            'total'            => WaCarakaConversation::count(),
            'open'             => WaCarakaConversation::open()->count(),
            'pending'          => WaCarakaConversation::pending()->count(),
            'closed'           => WaCarakaConversation::closed()->count(),
            'pendingHandovers' => WaCarakaHandover::where('status', 'pending')->count(),
        ];
    }

    protected function syncContactsAndUpdateNames(): array
    {
        $result = $this->waService->syncContacts();

        $conversations = WaCarakaConversation::query()
            ->select('id', 'conversation_id', 'remote_number', 'remote_name')
            ->get();

        $latestInboundMessageIds = WaCarakaMessage::query()
            ->selectRaw('MAX(id) as latest_id')
            ->whereIn('conversation_id', $conversations->pluck('conversation_id'))
            ->where('direction', 'inbound')
            ->groupBy('conversation_id');

        $latestMessageByConversation = WaCarakaMessage::query()
            ->whereIn('id', $latestInboundMessageIds)
            ->select('conversation_id', 'metadata')
            ->get()
            ->keyBy('conversation_id');

        $runtimeMetaResponse = $this->waService->resolveContactsMeta(
            $conversations->pluck('remote_number')->filter()->values()->all()
        );

        $runtimeMetaByRemoteNumber = collect(is_array($runtimeMetaResponse['data']['items'] ?? null)
            ? $runtimeMetaResponse['data']['items']
            : [])
            ->filter(fn ($item) => is_array($item) && !empty($item['jid']))
            ->keyBy('jid');

        $updatedNames = 0;

        foreach ($conversations as $conversation) {
            $latest = $latestMessageByConversation->get($conversation->conversation_id);
            $metadata = is_array($latest?->metadata) ? $latest->metadata : [];
            $runtimeMeta = $runtimeMetaByRemoteNumber->get($conversation->remote_number);
            $runtimeMeta = is_array($runtimeMeta) ? $runtimeMeta : [];
            $resolvedName = $this->resolveConversationDisplayName($conversation, $metadata, $runtimeMeta);

            if (!$resolvedName || $conversation->remote_name === $resolvedName) {
                continue;
            }

            $conversation->remote_name = $resolvedName;
            $conversation->save();
            $updatedNames++;
        }

        $result['data'] = array_merge($result['data'] ?? [], [
            'updatedNames' => $updatedNames,
        ]);

        return $result;
    }

    protected function reportStatsData(): array
    {
        $today = now()->startOfDay();

        $daily = collect(range(13, 0))->map(function (int $daysAgo) use ($today) {
            $start = (clone $today)->subDays($daysAgo);
            $end = (clone $start)->endOfDay();
            return [
                'label' => $start->format('d M'),
                'count' => WaCarakaMessage::query()
                    ->where('direction', 'inbound')
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ];
        })->values();

        $weekly = collect(range(11, 0))->map(function (int $weeksAgo) {
            $start = now()->startOfWeek(Carbon::MONDAY)->subWeeks($weeksAgo);
            $end = (clone $start)->endOfWeek(Carbon::SUNDAY);
            return [
                'label' => $start->format('d M'),
                'count' => WaCarakaMessage::query()
                    ->where('direction', 'inbound')
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ];
        })->values();

        $monthly = collect(range(11, 0))->map(function (int $monthsAgo) {
            $start = now()->startOfMonth()->subMonths($monthsAgo);
            $end = (clone $start)->endOfMonth();
            return [
                'label' => $start->translatedFormat('M Y'),
                'count' => WaCarakaMessage::query()
                    ->where('direction', 'inbound')
                    ->whereBetween('created_at', [$start, $end])
                    ->count(),
            ];
        })->values();

        $conversationCounts = WaCarakaConversation::query()
            ->select('claimed_by', DB::raw('COUNT(*) as total'))
            ->whereNotNull('claimed_by')
            ->groupBy('claimed_by')
            ->pluck('total', 'claimed_by');

        $replyCounts = WaCarakaMessage::query()
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->where('direction', 'outbound')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $operators = User::query()
            ->whereIn('id', $conversationCounts->keys()->merge($replyCounts->keys())->unique()->values())
            ->get(['id', 'name', 'alias'])
            ->map(function (User $user) use ($conversationCounts, $replyCounts) {
                return [
                    'id' => $user->id,
                    'name' => $user->alias ?: $user->name,
                    'conversations' => (int) ($conversationCounts[$user->id] ?? 0),
                    'outboundMessages' => (int) ($replyCounts[$user->id] ?? 0),
                ];
            })
            ->sortByDesc(fn (array $row) => $row['conversations'])
            ->values();

        return [
            'generatedAt' => now()->toIso8601String(),
            'summary' => [
                'inboundToday' => WaCarakaMessage::query()->where('direction', 'inbound')->whereDate('created_at', today())->count(),
                'inboundWeek' => WaCarakaMessage::query()->where('direction', 'inbound')->whereBetween('created_at', [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)])->count(),
                'inboundMonth' => WaCarakaMessage::query()->where('direction', 'inbound')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'activeConversations' => WaCarakaConversation::query()->whereIn('status', ['open', 'pending'])->count(),
            ],
            'dailyInbound' => $daily,
            'weeklyInbound' => $weekly,
            'monthlyInbound' => $monthly,
            'operatorStats' => $operators,
        ];
    }

    protected function resolveConversationDisplayName(WaCarakaConversation $conversation, array $metadata, array $runtimeMeta = []): ?string
    {
        $fallback = $this->cleanResolvedName($conversation->remote_name);

        if (str_ends_with((string) $conversation->remote_number, '@g.us')) {
            return $this->extractGroupName($metadata, $runtimeMeta) ?: $fallback;
        }

        return $this->extractContactName($metadata, $runtimeMeta) ?: $fallback;
    }

    protected function extractContactName(array $metadata, array $runtimeMeta = []): ?string
    {
        $candidates = [
            data_get($runtimeMeta, 'displayName'),
            data_get($metadata, 'senderName'),
            data_get($metadata, 'participantName'),
            data_get($metadata, 'pushName'),
            data_get($metadata, 'raw.meta.notifyName'),
            data_get($metadata, 'raw.notifyName'),
            data_get($metadata, 'raw.meta.senderName'),
        ];

        foreach ($candidates as $candidate) {
            $resolved = $this->cleanResolvedName($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    protected function extractGroupName(array $metadata, array $runtimeMeta = []): ?string
    {
        $candidates = [
            data_get($runtimeMeta, 'groupName'),
            data_get($runtimeMeta, 'displayName'),
            data_get($metadata, 'groupName'),
            data_get($metadata, 'groupSubject'),
            data_get($metadata, 'raw.groupName'),
            data_get($metadata, 'raw.groupSubject'),
            data_get($metadata, 'raw.meta.subject'),
            data_get($metadata, 'raw.subject'),
        ];

        foreach ($candidates as $candidate) {
            $resolved = $this->cleanResolvedName($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    protected function cleanResolvedName(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (str_contains($trimmed, '@s.whatsapp.net') || str_contains($trimmed, '@g.us') || str_contains($trimmed, '@lid')) {
            return null;
        }

        return $trimmed;
    }

    protected function extractProfilePhotoUrl(array $metadata, array $runtimeMeta = []): ?string
    {
        $candidates = [
            data_get($runtimeMeta, 'profilePhotoUrl'),
            data_get($runtimeMeta, 'avatarUrl'),
            data_get($metadata, 'profilePhotoUrl'),
            data_get($metadata, 'avatarUrl'),
            data_get($metadata, 'profilePicUrl'),
            data_get($metadata, 'senderProfilePicUrl'),
            data_get($metadata, 'raw.profilePhotoUrl'),
            data_get($metadata, 'raw.avatarUrl'),
            data_get($metadata, 'raw.profilePicUrl'),
            data_get($metadata, 'raw.senderProfilePicUrl'),
            data_get($metadata, 'raw.meta.profilePhotoUrl'),
            data_get($metadata, 'raw.meta.avatarUrl'),
            data_get($metadata, 'raw.meta.profilePicUrl'),
            data_get($metadata, 'raw.meta.senderProfilePicUrl'),
            data_get($metadata, 'raw.meta.profilePicUrl'),
            data_get($metadata, 'raw.meta.avatar'),
            data_get($metadata, 'raw.contextInfo.profilePicUrl'),
            data_get($metadata, 'raw.msgContextInfo.profilePicUrl'),
            data_get($metadata, 'raw.messageContextInfo.profilePicUrl'),
        ];

        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            $url = trim($candidate);
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, 'data:image/')) {
                return $url;
            }
        }

        return null;
    }

    protected function softResetState(): array
    {
        $updated = WaCarakaMessage::query()
            ->where('direction', 'inbound')
            ->whereNull('replied_at')
            ->update(['replied_at' => now()]);

        WaCarakaConversation::query()
            ->where('status', '!=', 'closed')
            ->update(['status' => 'open', 'unread_count' => 0]);

        return ['ok' => true, 'data' => ['ok' => true, 'updatedMessages' => $updated]];
    }

    protected function clearInboxState(): array
    {
        $deleted = [
            'handovers' => 0,
            'marks' => 0,
            'messages' => 0,
            'conversations' => 0,
            'logs' => 0,
        ];

        DB::transaction(function () use (&$deleted) {
            if (Schema::hasTable('wa_caraka_handovers')) {
                $deleted['handovers'] = DB::table('wa_caraka_handovers')->delete();
            }
            if (Schema::hasTable('wa_caraka_conversation_marks')) {
                $deleted['marks'] = DB::table('wa_caraka_conversation_marks')->delete();
            }
            if (Schema::hasTable('wa_caraka_messages')) {
                $deleted['messages'] = DB::table('wa_caraka_messages')->delete();
            }
            if (Schema::hasTable('wa_caraka_conversations')) {
                $deleted['conversations'] = DB::table('wa_caraka_conversations')->delete();
            }
            if (Schema::hasTable('wa_caraka_logs')) {
                $deleted['logs'] = DB::table('wa_caraka_logs')->delete();
            }
        });

        // Best effort: clear runtime history too.
        $this->waService->clearHistory();

        return [
            'ok' => true,
            'status' => 200,
            'data' => [
                'ok' => true,
                'deleted' => $deleted,
            ],
        ];
    }

    protected function adminOnlyRuntimeAction($user, callable $callback): JsonResponse
    {
        if (!$this->isAdmin($user)) {
            return response()->json(['error' => 'Aksi ini hanya dapat dilakukan oleh admin.'], 403);
        }

        $result = $callback();

        return response()->json($result['data'] ?? [], $result['status'] ?? 200);
    }

    protected function superAdminOnlyAction($user, callable $callback): JsonResponse
    {
        if (!$user?->isSuperAdmin()) {
            return response()->json(['error' => 'Aksi ini hanya dapat dilakukan oleh superadmin.'], 403);
        }

        $result = $callback();

        return response()->json($result['data'] ?? [], $result['status'] ?? 200);
    }

    protected function isAdmin($user): bool
    {
        return (bool) ($user?->isSuperAdmin() || $user?->role === 'admin');
    }

    private function toggleHandoverEnabled(Request $request): array
    {
        $enabled = $request->input('enabled', false);
        \App\Models\WaCarakaSetting::set('handover_enabled', (bool) $enabled, 'boolean');
        
        return [
            'ok' => true,
            'handoverEnabled' => $enabled,
            'message' => 'Pengaturan alih chat telah ' . ($enabled ? 'diaktifkan' : 'dinonaktifkan'),
        ];
    }
}
