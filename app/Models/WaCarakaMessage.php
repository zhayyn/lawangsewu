<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Set globally by WaCarakaController or WaCarakaPersonalController
     * to automatically filter all queries by source ('office' or 'personal').
     */
    public static ?string $activeSource = null;

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
        'source',
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
    // Booted
    // ──────────────────────────────────────────────

    protected static function booted()
    {
        static::addGlobalScope('source_filter', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (static::$activeSource) {
                $builder->where('source', static::$activeSource);
            }
        });

        static::creating(function (WaCarakaMessage $message) {
            if (static::$activeSource && empty($message->source)) {
                $message->source = static::$activeSource;
            }
        });
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
    public static function conversationIdFor(string $remoteNumber, ?string $source = null): string
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

        $activeSrc = $source ?: static::$activeSource ?: 'office';

        // Keep only compact digits for readability, plus hash for uniqueness.
        $digits = preg_replace('/\D/', '', $normalized) ?: '0';
        $hash = substr(sha1($normalized . '_' . $activeSrc), 0, 10);

        return sprintf('wa_%s_%s_%s_%s', $activeSrc, $kind, $digits, $hash);
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

    /**
     * Resolve a recipient phone number to its LID JID if it has an LID mapping.
     * Keep group JIDs and already LID JIDs untouched.
     */
    public static function resolveRecipientJid(string $to, ?string $source = null): string
    {
        $normalized = static::normalizeRemoteNumber($to);
        
        if ($normalized === '') {
            return '';
        }
        
        $activeSrc = $source ?: static::$activeSource ?: 'office';
        if ($activeSrc !== 'personal') {
            return $normalized;
        }
        
        if (str_ends_with($normalized, '@lid') || str_ends_with($normalized, '@g.us')) {
            return $normalized;
        }
        
        // Remove @s.whatsapp.net or other suffix to get phone number digits
        $phone = preg_replace('/@[^@]+$/', '', $normalized);
        $phoneDigits = preg_replace('/\D/', '', $phone) ?: $phone;
        
        // Check database mappings
        // Case A: remote_number is phone, resolved_number is LID
        $lidJid = \App\Models\WaCarakaConversation::withoutGlobalScopes()
            ->where('remote_number', $phoneDigits)
            ->where('resolved_number', 'like', '%@lid')
            ->value('resolved_number');
            
        if (!$lidJid) {
            // Case B: remote_number is LID, resolved_number is phone
            $lidJid = \App\Models\WaCarakaConversation::withoutGlobalScopes()
                ->where('resolved_number', $phoneDigits)
                ->where('remote_number', 'like', '%@lid')
                ->value('remote_number');
        }
            
        if ($lidJid) {
            return $lidJid;
        }
        
        // Fallback static map for hayyudin
        if ($phoneDigits === '6281317361689') {
            return '243138182570075@lid';
        }
        
        return $normalized;
    }
}
