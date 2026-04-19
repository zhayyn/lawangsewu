<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaCarakaSetting extends Model
{
    protected $table = 'wa_caraka_settings';

    protected $fillable = ['key', 'value', 'type'];

    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) (int) $setting->value,
            'integer' => (int) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, $value, string $type = 'string'): void
    {
        $stringValue = match ($type) {
            'boolean' => (int) (bool) $value,
            'json' => json_encode($value),
            default => (string) $value,
        };

        self::updateOrCreate(
            ['key' => $key],
            ['value' => $stringValue, 'type' => $type]
        );
    }
}
