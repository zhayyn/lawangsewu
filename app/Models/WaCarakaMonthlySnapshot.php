<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WaCarakaMonthlySnapshot extends WaCarakaModel
{
    use HasFactory;

    protected $fillable = [
        'scope_key',
        'month_key',
        'month_start',
        'month_end',
        'total_messages',
        'inbound_messages',
        'outbound_messages',
        'history_messages',
        'realtime_messages',
        'active_conversations',
        'unique_contacts',
        'top_operator_name',
        'payload',
        'snapshot_taken_at',
    ];

    protected $casts = [
        'month_start' => 'date',
        'month_end' => 'date',
        'total_messages' => 'integer',
        'inbound_messages' => 'integer',
        'outbound_messages' => 'integer',
        'history_messages' => 'integer',
        'realtime_messages' => 'integer',
        'active_conversations' => 'integer',
        'unique_contacts' => 'integer',
        'payload' => 'array',
        'snapshot_taken_at' => 'datetime',
    ];
}
