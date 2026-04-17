<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'queue_service_id',
        'code',
        'name',
        'call_label',
        'display_label',
        'location_type',
        'external_ref',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(QueueService::class, 'queue_service_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'counter_id');
    }
}
