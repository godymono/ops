<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RcsaPeriod extends Model
{
    protected $fillable = [
        'year', 'month', 'label', 'starts_on', 'ends_on', 'deadline_extended_to', 'is_open',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'starts_on' => 'date:Y-m-d',
        'ends_on' => 'date:Y-m-d',
        'deadline_extended_to' => 'date:Y-m-d',
        'is_open' => 'boolean',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(RcsaSubmission::class);
    }
}
