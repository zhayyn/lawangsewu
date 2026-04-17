<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QueueService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_group_id',
        'code',
        'name',
        'queue_prefix',
        'numbering_scope',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class, 'service_group_id');
    }

    public function counters(): HasMany
    {
        return $this->hasMany(ServiceCounter::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class, 'service_id');
    }
}
