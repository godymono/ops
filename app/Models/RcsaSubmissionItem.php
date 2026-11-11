<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RcsaSubmissionItem extends Model
{
    protected $fillable = [
        'rcsa_submission_id', 'category', 'item', 'status',
        'remarks', 'action_plan', 'target_date', 'sort_order',
    ];

    protected $casts = [
        'target_date' => 'date:Y-m-d',
        'sort_order' => 'integer',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(RcsaSubmission::class, 'rcsa_submission_id');
    }
}
