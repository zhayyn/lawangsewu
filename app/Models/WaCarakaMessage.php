<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WaCarakaMessage
 *
 * Two-way message model for the WA Caraka module.
 * Supports inbound (received from WA) and outbound (sent from Lawangsewu).
 * Linked to the operator who sent or is assigned to handle the message.
 */
class WaCarakaMessage extends WaCarakaModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'direction',
        'remote_number',
        'local_number',
        'message_text',
        'message_type',
        'wa_message_id',
        'status',
        'conversation_id',
        'metadata',
        'replied_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'replied_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForConversation($query, string $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeFromNumber($query, string $number)
    {
        return $query->where('remote_number', $number);
    }

    public function scopeUnreplied($query)
    {
        return $query->where('direction', 'inbound')
            ->whereNull('replied_at');
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    /**
     * Generate a stable conversation ID from a remote identifier.
     * Keep chat type (group/personal/lid) in the key to avoid collisions.
     */
    public static function conversationIdFor(string $remoteNumber): string
    {
        $normalized = static::normalizeRemoteNumber($remoteNumber);

        if ($normalized === '') {
            return 'wa_unknown';
        }

        $kind = 'personal';
        if (str_ends_with($normalized, '@g.us')) {
            $kind = 'group';
        } elseif (str_ends_with($normalized, '@lid')) {
            $kind = 'lid';
        }

        // Keep only compact digits for readability, plus hash for uniqueness.
        $digits = preg_replace('/\D/', '', $normalized) ?: '0';
        $hash = substr(sha1($normalized), 0, 10);

        return sprintf('wa_%s_%s_%s', $kind, $digits, $hash);
    }

    public static function normalizeRemoteNumber(?string $remoteNumber): string
    {
        $normalized = strtolower(trim((string) $remoteNumber));

        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/@llid$/i', '@lid', $normalized) ?: $normalized;

        if (str_ends_with($normalized, '@g.us')) {
            return $normalized;
        }

        if (str_ends_with($normalized, '@lid')) {
            $base = preg_replace('/@lid$/i', '', $normalized) ?: '';
            $digits = preg_replace('/\D/', '', $base) ?: $base;

            return trim($digits) !== '' ? $digits . '@lid' : $normalized;
        }

        $base = preg_replace('/@(s\.whatsapp\.net|c\.us|pn)$/i', '', $normalized) ?: $normalized;
        $digits = preg_replace('/\D/', '', $base) ?: '';

        if ($digits === '') {
            return $base;
        }

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }
}
