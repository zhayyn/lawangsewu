<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * WaCarakaTicket
 *
 * Unified ticket model for pengaduan, konsultasi, umum, and live_konsul.
 * Replaces wamehehe's separate `aduan`, `konsultasi`, `umum`, and `req_konsul` tables.
 *
 * Workflow: open → replied → sent → closed
 */
class WaCarakaTicket extends Model
{
    protected $fillable = [
        'assigned_to',
        'type',
        'remote_number',
        'message',
        'reply',
        'status',
        'attachment_path',
        'replied_at',
        'sent_at',
        'konsul_jenis_id',
        'scheduled_call_at',
        'reject_reason',
    ];

    protected $casts = [
        'replied_at'        => 'datetime',
        'sent_at'           => 'datetime',
        'scheduled_call_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ──────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'replied']);
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['sent', 'closed']);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'replied']);
    }

    /**
     * Build the greeting prefix for WA reply (same format as wamehehe).
     */
    public function replyPrefix(): string
    {
        $typeLabels = [
            'pengaduan'    => 'pengaduan',
            'konsultasi'   => 'permintaan konsultasi',
            'umum'         => 'pesan',
            'live_konsul'  => 'permintaan Live Konsultasi',
        ];

        $typeStr = $typeLabels[$this->type] ?? 'pesan';
        $date = $this->created_at
            ? $this->created_at->setTimezone('Asia/Jakarta')->format('d-m-Y H:i:s')
            : '-';

        return "*SiNofita PA Semarang* _Assalamualaikum wr. wb._ Berdasarkan {$typeStr} Anda pada {$date} berikut dapat kami sampaikan:";
    }

    /**
     * Get recent tickets for dashboard display.
     */
    public static function recent(int $limit = 20, ?string $type = null, ?string $status = null): array
    {
        if (!Schema::hasTable('wa_caraka_tickets')) {
            return [];
        }

        $query = static::query()
            ->with('assignee:id,name,alias')
            ->orderByDesc('created_at');

        if ($type) {
            $query->ofType($type);
        }

        if ($status) {
            $query->ofStatus($status);
        }

        return $query
            ->limit($limit)
            ->get()
            ->map(fn (self $t) => [
                'id'            => $t->id,
                'type'          => $t->type,
                'remoteNumber'  => $t->remote_number,
                'message'       => $t->message,
                'reply'         => $t->reply,
                'status'        => $t->status,
                'assignee'      => $t->assignee ? ($t->assignee->alias ?: $t->assignee->name) : null,
                'createdAt'     => $t->created_at
                    ? $t->created_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                    : null,
                'repliedAt'     => $t->replied_at
                    ? $t->replied_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                    : null,
                'sentAt'        => $t->sent_at
                    ? $t->sent_at->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB'
                    : null,
            ])
            ->all();
    }

    /**
     * Get ticket statistics for dashboard.
     */
    public static function ticketStats(): array
    {
        if (!Schema::hasTable('wa_caraka_tickets')) {
            return [
                'total' => 0, 'open' => 0, 'replied' => 0,
                'sent' => 0, 'closed' => 0,
                'pengaduan' => 0, 'konsultasi' => 0, 'umum' => 0,
                'todayTotal' => 0,
            ];
        }

        return [
            'total'       => static::count(),
            'open'        => static::ofStatus('open')->count(),
            'replied'     => static::ofStatus('replied')->count(),
            'sent'        => static::ofStatus('sent')->count(),
            'closed'      => static::ofStatus('closed')->count(),
            'pengaduan'   => static::ofType('pengaduan')->count(),
            'konsultasi'  => static::ofType('konsultasi')->count(),
            'umum'        => static::ofType('umum')->count(),
            'todayTotal'  => static::today()->count(),
        ];
    }
}
