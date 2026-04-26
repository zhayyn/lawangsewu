<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WaCarakaDailyMetric extends WaCarakaModel
{
    use HasFactory;

    protected $fillable = [
        'scope_key',
        'metric_date',
        'total_messages',
        'inbound_messages',
        'outbound_messages',
        'history_inbound_messages',
        'history_outbound_messages',
        'realtime_inbound_messages',
        'realtime_outbound_messages',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'total_messages' => 'integer',
        'inbound_messages' => 'integer',
        'outbound_messages' => 'integer',
        'history_inbound_messages' => 'integer',
        'history_outbound_messages' => 'integer',
        'realtime_inbound_messages' => 'integer',
        'realtime_outbound_messages' => 'integer',
    ];
}
