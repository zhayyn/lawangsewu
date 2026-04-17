<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission_id'];

    public function permission()
    {
        return $this->belongsTo(\App\Core\Models\Permission::class);
    }
}
