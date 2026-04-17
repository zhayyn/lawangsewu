<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SippCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'cache_key',
        'data_type',
        'data_content',
        'cached_at',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'data_content' => 'array',
        'cached_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('data_type', $type);
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }
}
