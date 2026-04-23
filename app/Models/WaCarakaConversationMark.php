<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaCarakaConversationMark extends WaCarakaModel
{
    protected $fillable = [
        'user_id',
        'wa_caraka_conversation_id',
        'label',
        'tone',
        'note',
        'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaCarakaConversation::class, 'wa_caraka_conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
