<?php

namespace App\Http\Controllers;

use App\Models\WaCarakaConversation;
use App\Models\WaCarakaConversationMark;
use App\Models\WaCarakaHandover;
use App\Models\WaCarakaMessage;
use App\Models\WaCarakaTicket;
use App\Services\WaCarakaConversationService;
use App\Services\WaCarakaService;
use App\Support\LawangsewuPortal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

                // ──────────────────────────────────────────────
                // 5. Global Stats
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
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
            ->orderByDesc('last_activity_at')
            ->limit(50)
            ->get();

        $marksByConversation = WaCarakaConversationMark::query()
            ->where('user_id', $user->id)
            ->whereIn('wa_caraka_conversation_id', $conversationRows->pluck('id'))
            ->get()
            ->keyBy('wa_caraka_conversation_id');

        $conversations = $conversationRows
            ->map(function ($c) use ($user) {
                $permission = $this->conversationService->canReply($c, $user);
                $isGroup = str_ends_with((string) $c->remote_number, '@g.us');
                $groupName = $isGroup ? ($c->remote_name ?: null) : null;
                $displayTitle = $isGroup
                    ? ($groupName ?: 'Grup WhatsApp')
                    : ($c->remote_name ?: $c->remote_number);

                return [
                'conversationId'   => $c->conversation_id,
                'remoteNumber'     => $c->remote_number,
                'remoteName'       => $c->remote_name,
                'displayTitle'     => $displayTitle,
                'isGroup'          => $isGroup,
                'groupName'        => $groupName,
                'status'           => $c->status,
                'unreadCount'      => $c->unread_count,
                'lastActivityAt'   => $c->last_activity_at?->diffForHumans(),
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
                ];
            })
            ->map(function (array $conversation) use ($conversationRows, $marksByConversation) {
                $row = $conversationRows->firstWhere('conversation_id', $conversation['conversationId']);
                if (!$row) {
                    return $conversation;
                }

                $mark = $marksByConversation->get($row->id);
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
            });

        return response()->json(['conversations' => $conversations]);
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
        $convoId = $request->input('conversation_id');
        $text    = $request->input('text');

        $convo = WaCarakaConversation::where('conversation_id', $convoId)->first();
        if (!$convo) return response()->json(['error' => 'Conversation not found'], 404);

        $permission = $this->conversationService->canReply($convo, auth()->user());
        if (!$permission['allowed']) {
            return response()->json(['error' => $permission['reason'] ?? 'This conversation is claimed by another operator'], 403);
        }

        $convo = $this->conversationService->claimForReply($convo, auth()->user());

        $res = $this->waService->queueText($convo->remote_number, $text, 'OPERATOR', auth()->id());
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
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['error' => 'Aksi ini hanya dapat dilakukan oleh superadmin.'], 403);
        }

        $messageId = $request->input('message_id');
        $message = WaCarakaMessage::find($messageId);

        if ($message) {
            $message->delete();
        }

        return response()->json(['ok' => true]);
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

    protected function adminOnlyRuntimeAction($user, callable $callback): JsonResponse
    {
        if (!$this->isAdmin($user)) {
            return response()->json(['error' => 'Aksi ini hanya dapat dilakukan oleh admin.'], 403);
        }

        $result = $callback();

        return response()->json($result['data'] ?? [], $result['status'] ?? 200);
    }

    protected function isAdmin($user): bool
    {
        return (bool) ($user?->isSuperAdmin() || $user?->role === 'admin');
    }
}
