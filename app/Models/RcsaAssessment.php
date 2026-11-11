<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RcsaAssessment extends Model
{
    protected $fillable = ['id', 'branch_id', 'rcsa_template_id', 'status', 'remarks', 'action_plan', 'target_date'];
    protected $casts = ['target_date' => 'date:Y-m-d'];
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function template(): BelongsTo { return $this->belongsTo(RcsaTemplate::class, 'rcsa_template_id'); }
}
