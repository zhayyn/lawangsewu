<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleAccessAllowlist extends Model
{
    protected $table = 'google_access_allowlist';

    protected $fillable = [
        'email',
        'note',
        'auto_activate',
        'created_by',
    ];
}
