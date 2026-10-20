<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'employee_id',
        'email',
        'password',
        'depot_id',
        'role',
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

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function isHeadOffice(): bool
    {
        return $this->hasRole('Head Office Admin') || $this->role === 'head_office';
    }

    public function isDepotOperator(): bool
    {
        return $this->hasRole('Depot Operator') || $this->role === 'depot_operator';
    }

    public function isAuditor(): bool
    {
        return $this->hasRole('Management / Auditor') || $this->role === 'auditor';
    }
}
