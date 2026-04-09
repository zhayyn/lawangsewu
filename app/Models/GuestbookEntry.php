<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestbookEntry extends Model
{
    use HasFactory;

    protected $table = 'guestbook_entries';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'position',
        'institution_category',
        'institution',
        'purpose',
        'checkin',
    ];
    protected $casts = [
        'checkin' => 'datetime',
    ];
}
