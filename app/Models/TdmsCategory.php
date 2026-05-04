<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TdmsCategory extends Model
{
    protected $fillable = ['name', 'icon', 'color', 'description', 'sort_order'];

    public function assets(): HasMany
    {
        return $this->hasMany(TdmsAsset::class, 'category_id');
    }

    public function activeAssetsCount(): int
    {
        return $this->assets()->where('status', 'active')->count();
    }
}
