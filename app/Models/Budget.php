<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    /**
     * Budget rows storing a whole-year allocation use this month value,
     * while per-month allocations use JAN..DEC.
     */
    public const MONTH_ANNUAL = 'ALL';

    protected $fillable = [
        'depot_id',
        'business_id',
        'expense_head_id',
        'year',
        'month',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function expenseHead(): BelongsTo
    {
        return $this->belongsTo(ExpenseHead::class);
    }

    /**
     * Total allocated budget for a pre-filtered budget query.
     *
     * When a specific month list applies, only those months' rows are summed
     * (annual 'ALL' rows are never mixed in, to avoid double counting). When
     * no month dimension is filtered, the annual 'ALL' allocation is preferred
     * and monthly rows are used as a fallback for legacy data.
     */
    public static function periodTotal($query, ?array $months = null): float
    {
        if ($months !== null) {
            return (float) (clone $query)->whereIn('month', $months)->where('month', '!=', self::MONTH_ANNUAL)->sum('amount');
        }

        $annual = (float) (clone $query)->where('month', self::MONTH_ANNUAL)->sum('amount');
        if ($annual > 0) {
            return $annual;
        }

        return (float) (clone $query)->where('month', '!=', self::MONTH_ANNUAL)->sum('amount');
    }
}
