<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class WaCarakaSyncRun extends WaCarakaModel
{
    use HasFactory;

    protected $fillable = [
        'run_key',
        'source',
        'status',
        'connection_jid',
        'device_label',
        'sync_type',
        'progress',
        'batches_count',
        'chats_count',
        'contacts_count',
        'messages_received',
        'messages_imported',
        'messages_duplicate',
        'messages_failed',
        'started_at',
        'last_event_at',
        'finished_at',
        'meta',
    ];

    protected $casts = [
        'progress' => 'integer',
        'batches_count' => 'integer',
        'chats_count' => 'integer',
        'contacts_count' => 'integer',
        'messages_received' => 'integer',
        'messages_imported' => 'integer',
        'messages_duplicate' => 'integer',
        'messages_failed' => 'integer',
        'started_at' => 'datetime',
        'last_event_at' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];
}
