<?php

namespace App\Models;

use App\Support\WaCarakaDatabase;
use Illuminate\Database\Eloquent\Model;

abstract class WaCarakaModel extends Model
{
    public function getConnectionName()
    {
        return WaCarakaDatabase::connectionName();
    }
}
