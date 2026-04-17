<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_date',
        'ticket_number',
        'ticket_code',
        'service_id',
        'counter_id',
        'channel',
        'status',
        'customer_name',
        'case_number',
        'case_id_sipp',
        'hearing_id_sipp',
        'room_id_sipp',
        'payload_json',
        'source_type',
        'source_id',
        'created_by',
        'called_by',
        'served_by',
        'called_at',
        'service_started_at',
        'completed_at',
        'cancelled_at',
        'notes',
    ];

    protected $casts = [
        'ticket_date' => 'date',
        'payload_json' => 'array',
        'called_at' => 'datetime',
        'service_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(QueueService::class, 'service_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(ServiceCounter::class, 'counter_id');
    }
}
