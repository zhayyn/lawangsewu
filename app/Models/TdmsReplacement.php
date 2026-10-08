<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdmsReplacement extends Model
{
    protected $fillable = [
        'asset_id', 'submitted_by', 'approved_by',
        'title', 'reason', 'estimated_cost', 'type',
        'approval_status', 'approval_notes', 'approved_at',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'approved_at'    => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(TdmsAsset::class, 'asset_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'submitted');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'repair_vs_replace' => 'Perbaikan vs Penggantian',
            'end_of_life'       => 'End-of-Life',
            'upgrade'           => 'Upgrade',
            'lost'              => 'Hilang/Dicuri',
            default             => $this->type,
        };
    }

    public function getApprovalStatusLabelAttribute(): string
    {
        return match ($this->approval_status) {
            'draft'     => 'Draf',
            'submitted' => 'Diajukan',
            'approved'  => 'Disetujui',
            'rejected'  => 'Ditolak',
            default     => $this->approval_status,
        };
    }
}
