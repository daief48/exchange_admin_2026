<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBudgetRequest;
use App\Models\Business;
use App\Models\Budget;
use App\Models\Depot;
use App\Models\Expense;
use App\Models\ExpenseHead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $month = strtoupper($request->get('month', now()->format('M')));

        $user = auth()->user();

        // Multi-select filters for the depot × expense-head matrix: an empty
        // selection means "all" (rows: depots, columns: expense heads).
        $depotFilter = $request->get('depot_ids', []);
        $headFilter = $request->get('expense_head_ids', []);
        $depotFilter = is_array($depotFilter) ? $depotFilter : [$depotFilter];
        $headFilter = is_array($headFilter) ? $headFilter : [$headFilter];
        $depotFilter = array_map('intval', array_filter($depotFilter, fn ($v) => $v !== '' && $v !== null));
        $headFilter = array_map('intval', array_filter($headFilter, fn ($v) => $v !== '' && $v !== null));

        $depots = $user->isDepotOperator()
            ? Depot::where('id', $user->depot_id)->get()
            : Depot::where('status', true)->orderBy('name')->get();

        $businesses = Business::where('status', true)->orderBy('name')->get();
        $expenseHeads = ExpenseHead::where('status', true)->orderBy('name')->get();

        // Matrix scope (operators are locked to their own depot).
        $matrixDepots = $user->isDepotOperator()
            ? $depots
            : ($depotFilter ? $depots->whereIn('id', $depotFilter)->values() : $depots);
        $matrixHeads = $headFilter ? $expenseHeads->whereIn('id', $headFilter)->values() : $expenseHeads;

        // Pivot: actual expenses per depot (rows) × expense head (columns).
        $expenseMatrix = [];
        $headTotals = $matrixHeads->mapWithKeys(fn ($head) => [$head->id => 0.0])->all();
        $grandTotal = 0.0;

        if ($matrixDepots->isNotEmpty() && $matrixHeads->isNotEmpty()) {
            $cells = Expense::where('year', $year)
                ->when($month !== 'ALL', fn ($q) => $q->where('month', $month))
                ->whereIn('depot_id', $matrixDepots->pluck('id'))
                ->whereIn('expense_head_id', $matrixHeads->pluck('id'))
                ->select('depot_id', 'expense_head_id', DB::raw('SUM(amount) as total'))
                ->groupBy('depot_id', 'expense_head_id')
                ->get();

            foreach ($cells as $cell) {
                $expenseMatrix[$cell->depot_id][$cell->expense_head_id] = (float) $cell->total;
            }

            foreach ($matrixDepots as $depot) {
                foreach ($matrixHeads as $head) {
                    $value = $expenseMatrix[$depot->id][$head->id] ?? 0.0;
                    $headTotals[$head->id] += $value;
                    $grandTotal += $value;
                }
            }
        }

        // Biggest spending heads first so the leading columns carry the data
        // (empty columns sink to the right edge).
        $matrixHeads = $matrixHeads->sortByDesc(fn ($head) => $headTotals[$head->id])->values();

        // Raw filter selections for marking the multi-select options; an
        // empty list renders the "All …" placeholder.
        $selectedDepotIds = $user->isDepotOperator() ? [$user->depot_id] : $depotFilter;
        $selectedHeadIds = $headFilter;

        // Variance Engine Calculation: Depot-wise summary
        $allDepots = $matrixDepots;

        $totalBudget = 0.0;
        $varianceTable = $allDepots->map(function ($depot) use ($year, $month, &$totalBudget) {
            $budgetAmount = $month === 'ALL'
                ? Budget::periodTotal(Budget::where('depot_id', $depot->id)->where('year', $year))
                : (float) Budget::where('depot_id', $depot->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->sum('amount');
            $totalBudget += $budgetAmount;

            $expenseAmount = (float) Expense::where('depot_id', $depot->id)
                ->where('year', $year)
                ->when($month !== 'ALL', fn($q) => $q->where('month', $month))
                ->sum('amount');

            $variance = $budgetAmount - $expenseAmount;
            $percentageUsed = $budgetAmount > 0 ? round(($expenseAmount / $budgetAmount) * 100, 1) : 0;

            $status = 'Safe';
            $badgeColor = 'success';
            if ($percentageUsed > 95) {
                $status = 'Critical / Over-budget';
                $badgeColor = 'danger';
            } elseif ($percentageUsed >= 76) {
                $status = 'Warning';
                $badgeColor = 'warning';
            }

            return [
                'depot' => $depot,
                'expense' => $expenseAmount,
                'variance' => $variance,
                'percentage' => $percentageUsed,
                'status' => $status,
                'badge' => $badgeColor,
            ];
        })->sortByDesc('percentage')->values();

        $totalActualExpense = $varianceTable->sum('expense');
        $totalVariance = $totalBudget - $totalActualExpense;
        $overallPercentage = $totalBudget > 0 ? round(($totalActualExpense / $totalBudget) * 100, 1) : 0;

        return view('budgets.index', compact(
            'depots',
            'businesses',
            'expenseHeads',
            'varianceTable',
            'matrixDepots',
            'matrixHeads',
            'expenseMatrix',
            'headTotals',
            'grandTotal',
            'selectedDepotIds',
            'selectedHeadIds',
            'totalActualExpense',
            'totalVariance',
            'overallPercentage',
            'year',
            'month'
        ));
    }

    public function store(StoreBudgetRequest $request)
    {
        $validated = $request->validated();

        Budget::updateOrCreate(
            [
                'depot_id' => $validated['depot_id'],
                'business_id' => $validated['business_id'] ?? null,
                'expense_head_id' => $validated['expense_head_id'] ?? null,
                'year' => $validated['year'],
                'month' => strtoupper($validated['month']),
            ],
            [
                'amount' => $validated['amount'],
            ]
        );

        return back()->with('success', 'Budget allocation saved successfully!');
    }
}
