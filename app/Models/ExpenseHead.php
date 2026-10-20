<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseHead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_transport_related',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_transport_related' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }
}
