<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PtspLoket extends Model
{
    protected $table = 'ptsp_lokets';

    protected $fillable = ['kode', 'nama', 'prefix_antrian', 'is_active', 'urutan'];

    protected $casts = ['is_active' => 'boolean'];

    public function antrian(): HasMany
    {
        return $this->hasMany(PtspAntrian::class, 'loket_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('is_active', true)->orderBy('urutan');
    }
}
