<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TdmsAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_code', 'category_id', 'name', 'brand', 'model',
        'serial_number', 'specifications', 'purchase_date', 'purchase_price',
        'location', 'assigned_to', 'notes', 'status', 'qr_token', 'created_by',
    ];

    protected $casts = [
        'specifications' => 'array',
        'purchase_date'  => 'date',
        'purchase_price' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TdmsAsset $asset) {
            if (!$asset->qr_token) {
                $asset->qr_token = Str::random(48);
            }
            if (!$asset->asset_code) {
                $asset->asset_code = self::generateAssetCode($asset->category_id);
            }
        });
    }

    protected static function generateAssetCode(int $categoryId): string
    {
        $category = TdmsCategory::find($categoryId);
        $prefix   = 'TDMS';
        $catCode  = $category ? strtoupper(Str::limit(str_replace(' ', '', $category->name), 3, '')) : 'IT';
        $sequence = self::whereHas('category', fn ($q) => $q->where('id', $categoryId))->withTrashed()->count() + 1;
        return sprintf('%s-%s-%04d', $prefix, $catCode, $sequence);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TdmsCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(TdmsServiceRecord::class, 'asset_id');
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(TdmsMaintenanceSchedule::class, 'asset_id');
    }

    public function replacements(): HasMany
    {
        return $this->hasMany(TdmsReplacement::class, 'asset_id');
    }

    public function pendingSchedules(): HasMany
    {
        return $this->maintenanceSchedules()->whereIn('status', ['pending', 'overdue']);
    }

    public function latestServiceRecord(): HasMany
    {
        return $this->serviceRecords()->latest()->limit(1);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'Aktif',
            'maintenance' => 'Dalam Pemeliharaan',
            'broken'      => 'Rusak',
            'retired'     => 'Pensiun',
            default       => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'emerald',
            'maintenance' => 'amber',
            'broken'      => 'rose',
            'retired'     => 'slate',
            default       => 'slate',
        };
    }
}
