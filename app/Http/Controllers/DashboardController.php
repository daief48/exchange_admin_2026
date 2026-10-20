<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Budget;
use App\Models\Depot;
use App\Models\Expense;
use App\Models\ExpenseHead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Depots list available for filtering
        $depots = $user->isDepotOperator()
            ? Depot::where('id', $user->depot_id)->get()
            : Depot::where('status', true)->orderBy('name')->get();

        $businesses = Business::where('status', true)->orderBy('name')->get();
        $expenseHeads = ExpenseHead::where('status', true)->orderBy('name')->get();

        $availableYears = Expense::select('year')->distinct()->orderBy('year', 'desc')->pluck('year')->toArray();
        if (empty($availableYears)) {
            $availableYears = [now()->year, now()->year - 1];
        }

        $analytics = $this->computeAnalytics($request);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($analytics);
        }

        return view('dashboard.index', array_merge($analytics, compact('depots', 'businesses', 'expenseHeads', 'availableYears')));
    }

    public function data(Request $request)
    {
        return response()->json($this->computeAnalytics($request));
    }

    private function computeAnalytics(Request $request): array
    {
        $user = auth()->user();

        // Base expense query
        $expenseQuery = Expense::query();
        $budgetQuery = Budget::query();

        // RBAC filtering
        if ($user->isDepotOperator()) {
            $expenseQuery->where('depot_id', $user->depot_id);
            $budgetQuery->where('depot_id', $user->depot_id);
        } elseif ($request->filled('depot_id')) {
            $expenseQuery->where('depot_id', $request->depot_id);
            $budgetQuery->where('depot_id', $request->depot_id);
        }

        if ($request->filled('business_id')) {
            $expenseQuery->where('business_id', $request->business_id);
            $budgetQuery->where(function ($q) use ($request) {
                $q->whereNull('business_id')->orWhere('business_id', $request->business_id);
            });
        }

        if ($request->filled('expense_head_id')) {
            $expenseQuery->where('expense_head_id', $request->expense_head_id);
            $budgetQuery->where(function ($q) use ($request) {
                $q->whereNull('expense_head_id')->orWhere('expense_head_id', $request->expense_head_id);
            });
        }

        if ($request->filled('from_date')) {
            $expenseQuery->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $expenseQuery->whereDate('date', '<=', $request->to_date);
        }

        // Month dimension: range filter (from_month..to_month) wins, falling back
        // to the single-month filter. 'ALL' means no month restriction.
        $monthList = null;
        if ($request->filled('from_month') || $request->filled('to_month')) {
            $monthList = Expense::monthRangeList($request->from_month, $request->to_month);
        } elseif ($request->filled('month') && strtoupper($request->month) !== 'ALL') {
            $monthList = [strtoupper($request->month)];
        }

        if ($monthList !== null) {
            $expenseQuery->whereIn('month', $monthList);
            $budgetQuery->whereIn('month', $monthList);
        }

        // Year dimension: range filter (from_year..to_year) or single year,
        // defaulting to the current year when nothing is supplied.
        if ($request->filled('from_year') || $request->filled('to_year')) {
            $expenseQuery->yearRange($request->from_year, $request->to_year);

            $fromYear = $request->filled('from_year') ? (int) $request->from_year : null;
            $toYear = $request->filled('to_year') ? (int) $request->to_year : null;
            if ($fromYear !== null && $toYear !== null) {
                $budgetQuery->whereBetween('year', [min($fromYear, $toYear), max($fromYear, $toYear)]);
            } elseif ($fromYear !== null) {
                $budgetQuery->where('year', '>=', $fromYear);
            } else {
                $budgetQuery->where('year', '<=', $toYear);
            }
        } elseif ($request->filled('year')) {
            $filterYear = (int) $request->year;
            $expenseQuery->where('year', $filterYear);
            $budgetQuery->where('year', $filterYear);
        } else {
            $expenseQuery->where('year', now()->year);
            $budgetQuery->where('year', now()->year);
        }

        // 1. KPI Metric 1: Total Expense (Filtered)
        $totalExpenseRaw = (float) (clone $expenseQuery)->sum('amount');
        $totalExpenseMillion = round($totalExpenseRaw / 1000000, 2);

        // 2. KPI Metric 2: Total Expense This Month (always the live calendar month)
        $currentMonth = strtoupper(now()->format('M'));
        $currentYear = now()->year;
        $thisMonthQuery = Expense::query()->where('year', $currentYear)->where('month', $currentMonth);
        if ($user->isDepotOperator()) {
            $thisMonthQuery->where('depot_id', $user->depot_id);
        } elseif ($request->filled('depot_id')) {
            $thisMonthQuery->where('depot_id', $request->depot_id);
        }
        $thisMonthExpenseRaw = (float) $thisMonthQuery->sum('amount');
        $thisMonthMillion = round($thisMonthExpenseRaw / 1000000, 2);
        $currentMonthLabel = strtoupper(now()->format('F Y'));

        // 3. KPI Metric 3: Depot Most Spend
        $topDepotRow = (clone $expenseQuery)
            ->select('depot_id', DB::raw('SUM(amount) as total_spend'))
            ->groupBy('depot_id')
            ->orderByDesc('total_spend')
            ->first();

        $depotMostSpend = 'None';
        $topDepotSpendMillion = 0;
        if ($topDepotRow && $topDepotRow->depot) {
            $depotMostSpend = $topDepotRow->depot->name;
            $topDepotSpendMillion = round($topDepotRow->total_spend / 1000000, 2);
        }

        // 4. KPI Metric 4: Budget Variance & Speedometer Gauge
        $totalBudgetRaw = Budget::periodTotal($budgetQuery, $monthList);
        if ($totalBudgetRaw <= 0) {
            $totalBudgetRaw = 10000000; // safe baseline if not configured
        }

        $budgetConsumedPercentage = $totalBudgetRaw > 0 ? round(($totalExpenseRaw / $totalBudgetRaw) * 100, 1) : 0;

        // Dynamic gauge status color: Green (0-75%), Yellow/Amber (76-95%), Red (>95%)
        $gaugeStatusColor = '#10b981'; // green
        $gaugeStatusLabel = 'Safe';
        if ($budgetConsumedPercentage > 95) {
            $gaugeStatusColor = '#ef4444'; // red
            $gaugeStatusLabel = 'Critical / Over-budget';
        } elseif ($budgetConsumedPercentage >= 76) {
            $gaugeStatusColor = '#f59e0b'; // amber/yellow
            $gaugeStatusLabel = 'Warning';
        }

        // 5. Visualizations Data
        // A. Depot Ranking Bar Chart (Ranked from highest to lowest spender)
        $depotSpendData = (clone $expenseQuery)
            ->select('depot_id', DB::raw('SUM(amount) as total'))
            ->groupBy('depot_id')
            ->orderByDesc('total')
            ->with('depot')
            ->take(15)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->depot?->name ?? 'Unknown',
                    'total' => (float) $row->total,
                    'total_m' => round($row->total / 1000000, 3),
                ];
            });

        // B. Expense Head Distribution (Donut / Treemap)
        $expenseHeadData = (clone $expenseQuery)
            ->select('expense_head_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_head_id')
            ->orderByDesc('total')
            ->with('expenseHead')
            ->take(10)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->expenseHead?->name ?? 'Other',
                    'total' => (float) $row->total,
                ];
            });

        // C. Business Unit Spend Breakdown (Horizontal Bar)
        $businessSpendData = (clone $expenseQuery)
            ->select('business_id', DB::raw('SUM(amount) as total'))
            ->groupBy('business_id')
            ->orderByDesc('total')
            ->with('business')
            ->take(10)
            ->get()
            ->map(function ($row) {
                return [
                    'name' => $row->business?->name ?? 'Unknown',
                    'total' => (float) $row->total,
                ];
            });

        // D. Daily/Monthly Expense Trend (Line Chart) — most recent 45 days, oldest first
        $trendData = (clone $expenseQuery)
            ->select('date', DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderByDesc('date')
            ->take(45)
            ->get()
            ->sortBy('date')
            ->values()
            ->map(function ($row) {
                return [
                    'date' => Carbon::parse($row->date)->format('d M'),
                    'total' => (float) $row->total,
                ];
            });

        // Recent 5 entries
        $recentExpenses = (clone $expenseQuery)
            ->with(['depot', 'business', 'expenseHead', 'user'])
            ->latest('date')
            ->latest('id')
            ->take(8)
            ->get();

        return compact(
            'totalExpenseRaw',
            'totalExpenseMillion',
            'thisMonthExpenseRaw',
            'thisMonthMillion',
            'currentMonthLabel',
            'depotMostSpend',
            'topDepotSpendMillion',
            'budgetConsumedPercentage',
            'gaugeStatusColor',
            'gaugeStatusLabel',
            'depotSpendData',
            'expenseHeadData',
            'businessSpendData',
            'trendData',
            'recentExpenses'
        );
    }
}
