<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TdmsServiceRecord extends Model
{
    protected $fillable = [
        'ticket_number', 'asset_id', 'reporter_id', 'technician_id',
        'title', 'issue_description', 'action_taken', 'parts_replaced',
        'repair_cost', 'priority', 'status', 'sla_deadline', 'date_in', 'date_out',
    ];

    protected $casts = [
        'parts_replaced' => 'array',
        'repair_cost'    => 'decimal:2',
        'sla_deadline'   => 'datetime',
        'date_in'        => 'datetime',
        'date_out'       => 'datetime',
    ];

    /** SLA hours per priority */
    private const SLA_HOURS = [
        'critical' => 4,
        'high'     => 24,
        'medium'   => 72,
        'low'      => 168,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TdmsServiceRecord $record) {
            if (!$record->ticket_number) {
                $record->ticket_number = self::generateTicketNumber();
            }
            if (!$record->sla_deadline) {
                $hours = self::SLA_HOURS[$record->priority ?? 'medium'] ?? 72;
                $record->sla_deadline = now()->addHours($hours);
            }
        });
    }

    protected static function generateTicketNumber(): string
    {
        $year  = now()->format('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('SR-%s-%04d', $year, $count);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(TdmsAsset::class, 'asset_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'in_progress', 'waiting_parts']);
    }

    public function scopeBreached($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed', 'cancelled'])
            ->where('sla_deadline', '<', now());
    }

    public function getIsBreachedAttribute(): bool
    {
        if (in_array($this->status, ['resolved', 'closed', 'cancelled'])) {
            return false;
        }
        return $this->sla_deadline && $this->sla_deadline->isPast();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open'          => 'Buka',
            'in_progress'   => 'Dalam Pengerjaan',
            'waiting_parts' => 'Menunggu Sparepart',
            'resolved'      => 'Selesai',
            'closed'        => 'Ditutup',
            'cancelled'     => 'Dibatalkan',
            default         => $this->status,
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'critical' => 'Kritis',
            'high'     => 'Tinggi',
            'medium'   => 'Sedang',
            'low'      => 'Rendah',
            default    => $this->priority,
        };
    }
}
