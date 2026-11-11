<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Licence extends Model
{
    protected $fillable = ['id', 'branch_id', 'category_id', 'title', 'authority', 'expiry_date', 'status'];
    protected $casts = ['expiry_date' => 'date:Y-m-d'];
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
