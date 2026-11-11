<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_HQ = 'hq';

    public const ROLE_BRANCH = 'branch';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isHq(): bool
    {
        return $this->role === self::ROLE_HQ;
    }

    public function isBranch(): bool
    {
        return $this->role === self::ROLE_BRANCH;
    }

    public function sessionKey(): string
    {
        if ($this->isHq()) {
            return 'HQ';
        }

        return $this->branch?->name ?: 'HQ';
    }

    public function toDashboardAuth(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'isHq' => $this->isHq(),
            'branchId' => $this->branch_id,
            'branch' => $this->branch?->name,
            'session' => $this->sessionKey(),
        ];
    }
}
