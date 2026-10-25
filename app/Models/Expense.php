<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    public const MONTHS = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];

    protected $fillable = [
        'date',
        'month',
        'year',
        'depot_id',
        'business_id',
        'expense_head_id',
        'amount',
        'remarks',
        'voucher_path',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'year' => 'integer',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve a chronological list of 3-letter month codes between two bounds.
     * Missing bounds default to JAN / DEC; an inverted range is normalised.
     */
    public static function monthRangeList(?string $from = null, ?string $to = null): array
    {
        $fromIndex = $from !== null ? array_search(strtoupper(substr(trim($from), 0, 3)), self::MONTHS, true) : 0;
        $toIndex = $to !== null ? array_search(strtoupper(substr(trim($to), 0, 3)), self::MONTHS, true) : 11;

        if ($fromIndex === false) {
            $fromIndex = 0;
        }
        if ($toIndex === false) {
            $toIndex = 11;
        }
        if ($fromIndex > $toIndex) {
            [$fromIndex, $toIndex] = [$toIndex, $fromIndex];
        }

        return array_slice(self::MONTHS, $fromIndex, $toIndex - $fromIndex + 1);
    }

    public function scopeMonthRange($query, ?string $from = null, ?string $to = null)
    {
        if (!$from && !$to) {
            return $query;
        }

        return $query->whereIn('month', self::monthRangeList($from, $to));
    }

    public function scopeYearRange($query, $fromYear = null, $toYear = null)
    {
        $fromYear = $fromYear !== null && $fromYear !== '' ? (int) $fromYear : null;
        $toYear = $toYear !== null && $toYear !== '' ? (int) $toYear : null;

        if ($fromYear !== null && $toYear !== null) {
            return $query->whereBetween('year', [min($fromYear, $toYear), max($fromYear, $toYear)]);
        }
        if ($fromYear !== null) {
            return $query->where('year', '>=', $fromYear);
        }
        if ($toYear !== null) {
            return $query->where('year', '<=', $toYear);
        }

        return $query;
    }
}
