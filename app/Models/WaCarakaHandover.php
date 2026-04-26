<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WaCarakaHandover
 *
 * A handover/takeover request for a conversation.
 * Lifecycle:
 *   - Operator B requests takeover of conversation owned by A → status: pending
 *   - Operator A approves → conversation.claimed_by = B, status: approved
 *   - Operator A rejects → status: rejected
 *   - Admin force takeover → force_approved = true, status: approved
 *   - Requestor cancels → status: cancelled
 */
class WaCarakaHandover extends WaCarakaModel
{
    protected $fillable = [
        'conversation_id',
        'requested_by',
        'requested_to',
        'status',
        'reason',
        'force_approved',
        'decided_at',
    ];

    protected $casts = [
        'force_approved' => 'boolean',
        'decided_at'     => 'datetime',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaCarakaConversation::class, 'conversation_id');
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_to');
    }

    // ──────────────────────────────────────────────
    // Status helpers
    // ──────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(): void
    {
        $this->update(['status' => 'approved', 'decided_at' => now()]);
        $this->conversation->transferTo($this->requestor);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected', 'decided_at' => now()]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled', 'decided_at' => now()]);
    }
}
