<?php
namespace App\Traits;

use Illuminate\Support\Facades\Log;

/**
 * Trait Auditable
 * Telemetri modern untuk mencatat perubahan data secara otomatis.
 */
trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            Log::info('Activity [CREATE]: ' . class_basename($model) . ' (ID: ' . $model->id . ')');
        });
        static::updated(function ($model) {
            Log::info('Activity [UPDATE]: ' . class_basename($model) . ' (ID: ' . $model->id . ')');
        });
        static::deleted(function ($model) {
            Log::info('Activity [DELETE]: ' . class_basename($model) . ' (ID: ' . $model->id . ')');
        });
    }
}
