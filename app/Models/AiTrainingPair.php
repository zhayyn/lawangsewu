<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiTrainingPair extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'conversation_id',
        'user_prompt',
        'human_response',
        'source',
        'status',
        'is_sensitive'
    ];
}
