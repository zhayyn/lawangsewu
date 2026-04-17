<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuestbookSetting extends Model
{
    protected $table = 'guestbook_settings';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'per_page',
        'require_identity_fields',
        'event_name',
    ];

    protected $casts = [
        'per_page' => 'integer',
        'require_identity_fields' => 'boolean',
    ];
}
