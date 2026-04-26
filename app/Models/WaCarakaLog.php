<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WaCarakaLog extends WaCarakaModel
{
    use HasFactory;

    protected $fillable = [
        'sender',
        'receiver',
        'message',
        'type',
        'status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
