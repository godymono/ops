<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceIssue extends Model
{
    protected $fillable = ['id', 'branch_id', 'category_id', 'title', 'priority', 'officer_id', 'target_date', 'status'];
    protected $casts = ['target_date' => 'date:Y-m-d'];
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function officer(): BelongsTo { return $this->belongsTo(Officer::class); }
}
