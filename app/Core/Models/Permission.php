<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'description', 'module'];

    public static function byName(string $name): ?self
    {
        return self::where('name', $name)->first();
    }

    public static function forRole(string $role)
    {
        return self::whereHas('rolePermissions', fn($q) => $q->where('role', $role))->get();
    }

    public static function byModule(string $module)
    {
        return self::where('module', $module)->get();
    }

    public function rolePermissions()
    {
        return $this->hasMany(\App\Models\RolePermission::class);
    }
}
