<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyPermitRequest extends Model
{
    protected $fillable = [
        'source_reference_id',
        'nip',
        'employee_name',
        'position',
        'rank',
        'study_type',
        'study_program',
        'status',
        'notes',
        'source_system',
        'source_payload',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'source_payload' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
