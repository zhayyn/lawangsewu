<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdmsMaintenanceSchedule extends Model
{
    protected $fillable = [
        'asset_id', 'maintenance_type', 'title', 'description',
        'due_date', 'completed_date', 'status', 'interval_days',
        'assigned_to_user', 'completion_notes',
    ];

    protected $casts = [
        'due_date'       => 'date',
        'completed_date' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (TdmsMaintenanceSchedule $schedule) {
            if ($schedule->status === 'pending' && $schedule->due_date->isPast()) {
                $schedule->status = 'overdue';
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(TdmsAsset::class, 'asset_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(fn ($q) => $q->where('status', 'pending')->where('due_date', '<', now()->toDateString()));
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())->whereIn('status', ['pending', 'overdue']);
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [today(), today()->addDays(7)])->whereIn('status', ['pending', 'overdue']);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Menunggu',
            'overdue'   => 'Terlambat',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default     => $this->status,
        };
    }
}
