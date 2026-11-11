<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['name', 'code', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
    public function maintenanceIssues(): HasMany { return $this->hasMany(MaintenanceIssue::class); }
    public function licences(): HasMany { return $this->hasMany(Licence::class); }
    public function rcsaAssessments(): HasMany { return $this->hasMany(RcsaAssessment::class); }
}
