<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'external_uid',
        'nip',
        'name',
        'email',
        'phone',
        'position',
        'rank',
        'satker_code',
        'satker_name',
        'employment_status',
        'source_system',
        'merged_sources',
        'source_payload',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'merged_sources' => 'array',
            'source_payload' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
