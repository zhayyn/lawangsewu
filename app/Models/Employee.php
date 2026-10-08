<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nip', 'external_uid', 'name', 'email', 'phone', 
        'position', 'rank', 'satker_code', 'satker_name', 
        'employment_status', 'source_system', 'merged_sources', 
        'source_payload', 'last_synced_at'
    ];

    protected $casts = [
        'merged_sources' => 'array',
        'source_payload' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
