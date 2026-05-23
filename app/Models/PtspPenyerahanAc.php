<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtspPenyerahanAc extends Model
{
    protected $table = 'ptsp_penyerahan_ac';

    protected $fillable = [
        'nomor_perkara', 'nomor_ac', 'tanggal_bht', 'tanggal_penyerahan',
        'jenis', 'nama_penerima', 'pihak_penerima', 'nik_penerima',
        'foto_path', 'catatan', 'petugas_id', 'antrian_id',
    ];

    protected $casts = [
        'tanggal_bht'        => 'date',
        'tanggal_penyerahan' => 'date',
    ];

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function antrian(): BelongsTo
    {
        return $this->belongsTo(PtspAntrian::class, 'antrian_id');
    }

    public function scopeTanggal($query, $date)
    {
        return $query->whereDate('tanggal_penyerahan', $date);
    }
}
