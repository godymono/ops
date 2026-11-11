<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RcsaTemplate extends Model
{
    protected $fillable = ['category_id', 'item', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function assessments(): HasMany { return $this->hasMany(RcsaAssessment::class); }
}
