<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * WaCarakaSession
 *
 * Temporary chatbot conversation state for 2-stage menus.
 * Replaces wamehehe's `qtemp` table.
 *
 * When a user picks a menu that requires input (e.g. "7" = cek perkara),
 * we create a session record. When they send their next message (the input),
 * we look it up here to know what they're responding to.
 */
class WaCarakaSession extends Model
{
    protected $fillable = [
        'remote_number',
        'pending_command',
        'prompt_sent',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeForNumber($query, string $number)
    {
        return $query->where('remote_number', $number);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Find an active (non-expired) session for a given phone number.
     */
    public static function findActiveForNumber(string $number): ?self
    {
        return static::forNumber($number)
            ->notExpired()
            ->latest()
            ->first();
    }

    /**
     * Clear all sessions for a phone number (after processing or reset).
     */
    public static function clearForNumber(string $number): int
    {
        return static::forNumber($number)->delete();
    }
}
