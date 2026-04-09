<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PtspQueueTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'queue_date',
        'service_desk',
        'visitor_name',
        'purpose',
        'status',
        'called_at',
        'served_at',
        'notes',
    ];

    protected $casts = [
        'queue_date' => 'date',
        'called_at' => 'datetime',
        'served_at' => 'datetime',
    ];

    public function scopeToday($query)
    {
        return $query->whereDate('queue_date', today());
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }
}
