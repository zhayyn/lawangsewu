<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionAuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'target_user_id',
        'scope',
        'action',
        'role',
        'feature_key',
        'old_enabled',
        'new_enabled',
        'ip_address',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'old_enabled' => 'boolean',
        'new_enabled' => 'boolean',
        'meta' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
