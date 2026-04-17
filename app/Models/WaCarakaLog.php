<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaCarakaLog extends Model
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
