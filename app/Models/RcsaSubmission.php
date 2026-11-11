<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RcsaSubmission extends Model
{
    protected $fillable = [
        'rcsa_period_id', 'branch_id', 'status', 'submitted_at', 'submitted_by',
        'total_count', 'yes_count', 'no_count', 'na_count', 'ne_count', 'notes',
        'deadline_extended_to',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'deadline_extended_to' => 'date:Y-m-d',
        'total_count' => 'integer',
        'yes_count' => 'integer',
        'no_count' => 'integer',
        'na_count' => 'integer',
        'ne_count' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(RcsaPeriod::class, 'rcsa_period_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RcsaSubmissionItem::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }
}
