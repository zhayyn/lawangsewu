<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SidangQueueTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'queue_date',
        'hearing_number',
        'courtroom',
        'parties',
        'hearing_time',
        'status',
        'called_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'queue_date' => 'date',
        'hearing_time' => 'datetime:H:i',
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function scopeToday($query)
    {
        return $query->whereDate('queue_date', today());
    }
}
