<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WidgetVisitor extends Model
{
    protected $table = 'widget_visitors';
    protected $fillable = ['widget_name', 'ip_address', 'user_agent', 'visit_date'];
}
