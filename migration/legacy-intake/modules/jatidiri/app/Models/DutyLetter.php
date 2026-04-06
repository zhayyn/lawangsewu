<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DutyLetter extends Model
{
    protected $fillable = [
        'source_reference_id',
        'letter_number',
        'letter_date',
        'purpose',
        'destination_agency',
        'destination_city',
        'start_date',
        'end_date',
        'signing_officer',
        'dipa_code',
        'source_system',
        'source_payload',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'source_payload' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }
}
