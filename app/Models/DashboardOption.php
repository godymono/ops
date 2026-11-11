<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardOption extends Model
{
    protected $fillable = ['group_key', 'option_value', 'label', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
