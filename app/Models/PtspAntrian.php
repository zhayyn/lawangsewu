<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PtspAntrian extends Model
{
    protected $table = 'ptsp_antrian';

    protected $fillable = [
        'tanggal_antrian', 'nomor_antrian', 'loket_id',
        'nama_pemohon', 'nomor_perkara', 'keperluan',
        'status', 'dipanggil_oleh', 'dilayani_oleh',
        'dipanggil_at', 'dilayani_at', 'selesai_at', 'catatan',
    ];

    protected $casts = [
        'tanggal_antrian' => 'date',
        'dipanggil_at'    => 'datetime',
        'dilayani_at'     => 'datetime',
        'selesai_at'      => 'datetime',
    ];

    public function loket(): BelongsTo
    {
        return $this->belongsTo(PtspLoket::class, 'loket_id');
    }

    public function penyerahanAc(): HasOne
    {
        return $this->hasOne(PtspPenyerahanAc::class, 'antrian_id');
    }

    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal_antrian', today());
    }

    public function scopeTanggal($query, $date)
    {
        return $query->whereDate('tanggal_antrian', $date);
    }

    /** Generate nomor antrian berikutnya untuk loket + tanggal tertentu */
    public static function generateNomor(PtspLoket $loket, \Carbon\Carbon $date): string
    {
        $count = self::whereDate('tanggal_antrian', $date)
            ->where('loket_id', $loket->id)
            ->count() + 1;

        return $loket->prefix_antrian . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
