<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'source_reference_id',
        'employee_nip',
        'employee_name',
        'supervisor_nip',
        'supervisor_name',
        'leave_type',
        'leave_category',
        'reason',
        'duration_days',
        'start_date',
        'end_date',
        'address',
        'phone',
        'status',
        'notes',
        'decision_notes',
        'satker',
        'source_system',
        'source_payload',
        'last_synced_at',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'source_payload' => 'array',
            'last_synced_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_nip', 'nip');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_nip', 'nip');
    }

    public function leaveBalance(): BelongsTo
    {
        return $this->belongsTo(LeaveBalance::class, 'employee_nip', 'employee_nip');
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->whereNotNull('rejected_at');
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->whereNull('approved_at')->whereNull('rejected_at');
    }

    /**
     * Calculate working days between dates
     */
    public function getWorkingDaysAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $count = 0;
        $current = $this->start_date->copy();
        
        while ($current <= $this->end_date) {
            if ($current->isWeekday()) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
