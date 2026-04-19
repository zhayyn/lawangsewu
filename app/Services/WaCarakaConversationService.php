<?php

namespace App\Services;

use App\Models\User;
use App\Models\WaCarakaConversation;
use App\Models\WaCarakaHandover;
use App\Models\WaCarakaMessage;
use App\Events\WaCarakaConversationUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WaCarakaConversationService
 *
 * Manages the "claimed conversation" ownership model for WA Caraka inbox.
 *
 * Rules:
 *   1. All operators can see all conversations.
 *   2. First operator to reply → claims the conversation automatically.
 *   3. Other operators CANNOT reply to a claimed conversation.
 *   4. To take over, they must submit a handover request.
 *   5. The current owner must approve. If approved, ownership transfers.
 *   6. Admins/Superadmins can force takeover without approval.
 */
class WaCarakaConversationService
{
    // ──────────────────────────────────────────────
    // Conversation Lookup / Create
    // ──────────────────────────────────────────────

    /**
     * Get or create a conversation record for a given remote number.
     */
    public function findOrCreate(string $remoteNumber, string $conversationId): WaCarakaConversation
    {
        return WaCarakaConversation::firstOrCreate(
            ['conversation_id' => $conversationId],
            [
                'remote_number'    => $remoteNumber,
                'status'           => 'pending',
                'last_activity_at' => now(),
            ],
        );
    }

    /**
     * Paginated list of conversations for the inbox view.
     * Each row includes ownership and pending handover info.
     */
    public function inbox(array $filters = [], int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        return WaCarakaConversation::query()
            ->with([
                'owner:id,name,alias',
                'pendingHandover.requestor:id,name,alias',
            ])
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['claimed_by']), fn ($q) => $q->where('claimed_by', $filters['claimed_by']))
            ->orderByDesc('last_activity_at')
            ->paginate($perPage);
    }

    /**
     * Check if a user can reply to a specific conversation.
     * Returns ['allowed' => bool, 'reason' => string].
     *
     * Ownership tracking is retained for display purposes, but every
     * authenticated user may reply to any conversation.
     */
    public function canReply(WaCarakaConversation $convo, User $user): array
    {
        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Claim a conversation for a user (e.g. when first replying).
     * No-op if already claimed by this user.
     */
    public function claim(WaCarakaConversation $convo, User $user): void
    {
        if (!$convo->isUnclaimed() && !$convo->isClaimedBy($user->id)) {
            return; // Respect existing ownership
        }

        $convo->claimFor($user);
        $this->safeDispatchUpdate($convo->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));
    }

    /**
     * Claim a conversation atomically for a reply action.
     * Returns a fresh conversation state after attempting the claim.
     */
    public function claimForReply(WaCarakaConversation $convo, User $user): WaCarakaConversation
    {
        return DB::transaction(function () use ($convo, $user) {
            $locked = WaCarakaConversation::query()
                ->whereKey($convo->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isUnclaimed()) {
                $locked->claimFor($user);
            }

            $fresh = $locked->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']);
            $this->safeDispatchUpdate($fresh);

            return $fresh;
        });
    }

    /**
     * Record an inbound message — update/create conversation state.
     */
    public function recordInbound(WaCarakaMessage $message): WaCarakaConversation
    {
        $convo = $this->findOrCreate($message->remote_number, $message->conversation_id);
        $convo->recordInbound();
        $this->safeDispatchUpdate($convo->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));
        return $convo;
    }

    /**
     * Record an outbound message — update last_activity_at.
     */
    public function recordOutbound(WaCarakaMessage $message, User $sender): void
    {
        $convo = $this->findOrCreate($message->remote_number, $message->conversation_id);
        $convo->update(['last_activity_at' => now(), 'status' => 'open']);
        if ($convo->claimed_by !== $sender->id) {
            $convo->update(['claimed_by' => $sender->id, 'claimed_at' => now()]);
        }
        $this->safeDispatchUpdate($convo->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));
    }

    // ──────────────────────────────────────────────
    // Handover / Takeover
    // ──────────────────────────────────────────────

    /**
     * Request a conversation takeover.
     * Returns ['ok' => bool, 'handover' => WaCarakaHandover|null, 'error' => string|null]
     */
    public function requestHandover(WaCarakaConversation $convo, User $requestor, ?string $reason = null): array
    {
        // Can't take over your own conversation
        if ($convo->isClaimedBy($requestor->id)) {
            return ['ok' => false, 'error' => 'Kamu sudah menjadi pemilik percakapan ini.'];
        }

        // Check for existing pending request from this user
        $existing = WaCarakaHandover::where('conversation_id', $convo->id)
            ->where('requested_by', $requestor->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return ['ok' => false, 'error' => 'Sudah ada permintaan pengambilalihan yang menunggu.'];
        }

        $handover = WaCarakaHandover::create([
            'conversation_id' => $convo->id,
            'requested_by'    => $requestor->id,
            'requested_to'    => $convo->claimed_by,
            'reason'          => $reason,
            'status'          => 'pending',
        ]);

        return ['ok' => true, 'handover' => $handover];
    }

    /**
     * Approve a handover request.
     * Only the current owner or an admin may approve.
     */
    public function approveHandover(WaCarakaHandover $handover, User $approver): array
    {
        $isAdmin = $approver->isSuperAdmin() || $approver->role === 'admin';
        $isOwner = $handover->conversation?->isClaimedBy($approver->id);

        if (!$isAdmin && !$isOwner) {
            return ['ok' => false, 'error' => 'Hanya pemilik percakapan atau admin yang dapat menyetujui.'];
        }

        if (!$handover->isPending()) {
            return ['ok' => false, 'error' => 'Permintaan ini sudah diproses.'];
        }

        DB::transaction(function () use ($handover) {
            $handover->approve(); // updates conversation.claimed_by inside
        });

        $this->safeDispatchUpdate($handover->conversation->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));

        return ['ok' => true];
    }

    /**
     * Reject a handover request.
     */
    public function rejectHandover(WaCarakaHandover $handover, User $rejecter): array
    {
        $isAdmin = $rejecter->isSuperAdmin() || $rejecter->role === 'admin';
        $isOwner = $handover->conversation?->isClaimedBy($rejecter->id);

        if (!$isAdmin && !$isOwner) {
            return ['ok' => false, 'error' => 'Hanya pemilik percakapan yang dapat menolak.'];
        }

        if (!$handover->isPending()) {
            return ['ok' => false, 'error' => 'Permintaan ini sudah diproses.'];
        }

        $handover->reject();
        $this->safeDispatchUpdate($handover->conversation->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));

        return ['ok' => true];
    }

    /**
     * Admin/Superadmin force takeover — no approval needed.
     */
    public function forceHandover(WaCarakaConversation $convo, User $admin, ?string $reason = null): array
    {
        if (!$admin->isSuperAdmin() && $admin->role !== 'admin') {
            return ['ok' => false, 'error' => 'Hanya admin yang dapat melakukan pengambilalihan paksa.'];
        }

        WaCarakaHandover::create([
            'conversation_id' => $convo->id,
            'requested_by'    => $admin->id,
            'requested_to'    => $convo->claimed_by,
            'reason'          => $reason ?? 'Force takeover oleh admin.',
            'status'          => 'approved',
            'force_approved'  => true,
            'decided_at'      => now(),
        ]);

        $convo->transferTo($admin);
        $this->safeDispatchUpdate($convo->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));

        return ['ok' => true];
    }

    /**
     * Close a conversation (mark as done).
     * Only owner or admin can close.
     */
    public function closeConversation(WaCarakaConversation $convo, User $user): array
    {
        $isAdmin = $user->isSuperAdmin() || $user->role === 'admin';

        if (!$isAdmin && !$convo->isClaimedBy($user->id)) {
            return ['ok' => false, 'error' => 'Hanya pemilik percakapan atau admin yang dapat menutupnya.'];
        }

        $convo->update(['status' => 'closed']);
        $this->safeDispatchUpdate($convo->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));
        return ['ok' => true];
    }

    /**
     * Reopen a closed conversation.
     */
    public function reopenConversation(WaCarakaConversation $convo): void
    {
        if ($convo->status === 'closed') {
            $convo->update(['status' => $convo->claimed_by ? 'open' : 'pending']);
            $this->safeDispatchUpdate($convo->fresh(['owner:id,name,alias', 'pendingHandover.requestor:id,name,alias']));
        }
    }

    // ──────────────────────────────────────────────
    // Stats helpers
    // ──────────────────────────────────────────────

    public function stats(): array
    {
        return [
            'total'         => WaCarakaConversation::count(),
            'open'          => WaCarakaConversation::open()->count(),
            'pending'       => WaCarakaConversation::pending()->count(),
            'closed'        => WaCarakaConversation::closed()->count(),
            'pendingHandovers' => WaCarakaHandover::where('status', 'pending')->count(),
        ];
    }

    // ──────────────────────────────────────────────
    // Internal Helpers
    // ──────────────────────────────────────────────

    private function safeDispatchUpdate(WaCarakaConversation $convo): void
    {
        try {
            WaCarakaConversationUpdated::dispatch($convo);
        } catch (\Throwable $e) {
            Log::warning('[WaCaraka] ConversationUpdated broadcast failed (non-critical)', [
                'conversation_id' => $convo->conversation_id ?? null,
                'error'           => class_basename($e) . ': ' . substr($e->getMessage(), 0, 120),
            ]);
        }
    }
}
