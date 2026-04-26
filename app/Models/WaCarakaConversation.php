<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * WaCarakaConversation
 *
 * Represents a conversation thread with a remote WA number.
 * Implements the "claimed conversation" ownership model:
 *   - status: pending (no reply yet), open (active), closed (done)
 *   - claimed_by: the operator who first replied and now "owns" this conversation
 *   - Other operators may request a handover (see WaCarakaHandover)
 */
class WaCarakaConversation extends WaCarakaModel
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'remote_number',
        'remote_name',
        'status',
        'claimed_by',
        'claimed_at',
        'last_activity_at',
        'unread_count',
    ];

    protected $casts = [
        'claimed_at'       => 'datetime',
        'last_activity_at' => 'datetime',
        'unread_count'     => 'integer',
        'force_approved'   => 'boolean',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WaCarakaMessage::class, 'conversation_id', 'conversation_id')
            ->orderBy('created_at');
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(WaCarakaHandover::class, 'conversation_id');
    }

    public function pendingHandover(): HasOne
    {
        return $this->hasOne(WaCarakaHandover::class, 'conversation_id')
            ->where('status', 'pending')
            ->latest();
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeClaimedBy($query, int $userId)
    {
        return $query->where('claimed_by', $userId);
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public function isUnclaimed(): bool
    {
        return is_null($this->claimed_by);
    }

    public function isClaimedBy(int $userId): bool
    {
        return $this->claimed_by === $userId;
    }

    /**
     * Claim this conversation for an operator.
     * Sets status to 'open' and records claimed_at.
     */
    public function claimFor(User $user): void
    {
        $this->update([
            'claimed_by' => $user->id,
            'claimed_at' => now(),
            'status'     => 'open',
        ]);
    }

    /**
     * Transfer ownership to a new operator (after approved handover).
     */
    public function transferTo(User $newOwner): void
    {
        $this->update([
            'claimed_by' => $newOwner->id,
            'claimed_at' => now(),
            'status'     => 'open',
        ]);
    }

    /**
     * Increment unread count and update last activity.
     */
    public function recordInbound(): void
    {
        $this->increment('unread_count');
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Reset unread count (when operator reads the conversation).
     */
    public function markRead(): void
    {
        $this->update(['unread_count' => 0]);
    }
}
