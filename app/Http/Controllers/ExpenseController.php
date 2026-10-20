<?php

namespace App\Http\Controllers;

use App\Exports\ExpensesExport;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Business;
use App\Models\Depot;
use App\Models\Expense;
use App\Models\ExpenseHead;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Expense logs are reserved for Head Office / audit roles; depot
        // operators only create entries, they no longer browse the log.
        if ($user->isDepotOperator()) {
            abort(403, 'Expense logs are not available to depot operators.');
        }

        $query = Expense::query()->with(['depot', 'business', 'expenseHead', 'user']);

        if ($request->filled('depot_id')) {
            $query->where('depot_id', $request->depot_id);
        }

        if ($request->filled('business_id')) {
            $query->where('business_id', $request->business_id);
        }

        if ($request->filled('expense_head_id')) {
            $query->where('expense_head_id', $request->expense_head_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        if ($request->filled('month') && strtoupper($request->month) !== 'ALL') {
            $query->where('month', strtoupper($request->month));
        } elseif ($request->filled('from_month') || $request->filled('to_month')) {
            $query->monthRange($request->from_month, $request->to_month);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }
        if ($request->filled('from_year') || $request->filled('to_year')) {
            $query->yearRange($request->from_year, $request->to_year);
        }

        $expenses = $query->latest('date')->latest('id')->paginate(15)->withQueryString();

        $depots = $user->isDepotOperator() ? Depot::where('id', $user->depot_id)->get() : Depot::where('status', true)->orderBy('name')->get();
        $businesses = Business::where('status', true)->orderBy('name')->get();
        $expenseHeads = ExpenseHead::where('status', true)->orderBy('name')->get();

        return view('expenses.index', compact('expenses', 'depots', 'businesses', 'expenseHeads'));
    }

    public function create()
    {
        $user = auth()->user();

        $depots = $user->isDepotOperator()
            ? Depot::where('id', $user->depot_id)->get()
            : Depot::where('status', true)->orderBy('name')->get();

        $businesses = Business::where('status', true)->orderBy('name')->get();
        $expenseHeads = ExpenseHead::where('status', true)->orderBy('name')->get();

        $todayDate = Carbon::today()->toDateString();

        return view('expenses.create', compact('depots', 'businesses', 'expenseHeads', 'todayDate', 'user'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Enforce depot for depot operators
        if ($user->isDepotOperator() && $user->depot_id) {
            $validated['depot_id'] = $user->depot_id;
        }

        // Handle voucher file upload
        $voucherPath = null;
        if ($request->hasFile('voucher_attachment')) {
            $voucherPath = $request->file('voucher_attachment')->store('vouchers', 'public');
        }

        $dateCarbon = Carbon::parse($validated['date']);

        DB::beginTransaction();
        try {
            $expense = Expense::create([
                'date' => $dateCarbon->toDateString(),
                'month' => strtoupper($dateCarbon->format('M')),
                'year' => $dateCarbon->year,
                'depot_id' => $validated['depot_id'],
                'business_id' => $validated['business_id'],
                'expense_head_id' => $validated['expense_head_id'],
                'amount' => $validated['amount'],
                'transport_mode' => $validated['transport_mode'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'voucher_path' => $voucherPath,
                'user_id' => $user->id,
            ]);

            DB::commit();

            // Depot operators are blocked from the expense log, so send them
            // back to the entry form; everyone else lands on the log.
            $redirectRoute = $user->isDepotOperator() ? 'expenses.create' : 'expenses.index';

            return redirect()->route($redirectRoute)
                ->with('success', "Daily expense entry #{$expense->id} (৳ " . number_format($expense->amount, 2) . ") successfully registered!");
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($voucherPath) {
                Storage::disk('public')->delete($voucherPath);
            }

            return back()->withInput()->with('error', 'Failed to save expense entry: ' . $e->getMessage());
        }
    }

    public function show(Expense $expense)
    {
        $user = auth()->user();
        if ($user->isDepotOperator() && $expense->depot_id !== $user->depot_id) {
            abort(403, 'Unauthorized access to other depot expenses.');
        }

        $expense->load(['depot', 'business', 'expenseHead', 'user']);

        return view('expenses.show', compact('expense'));
    }

    /**
     * Downloadable PDF voucher of a single expense (same RBAC as show()).
     */
    public function pdf(Expense $expense)
    {
        $user = auth()->user();
        if ($user->isDepotOperator() && $expense->depot_id !== $user->depot_id) {
            abort(403, 'Unauthorized access to other depot expenses.');
        }

        $expense->load(['depot', 'business', 'expenseHead', 'user']);

        $pdf = Pdf::loadView('expenses.pdf', compact('expense'));
        $pdf->setPaper('a4');

        return $pdf->download('expense-voucher-' . str_pad($expense->id, 4, '0', STR_PAD_LEFT) . '.pdf');
    }

    public function export(Request $request)
    {
        // Enforce RBAC: Strictly only Head Office users can export raw dumps
        $user = auth()->user();
        if (!$user || (!$user->can('export-raw-data') && !$user->isHeadOffice())) {
            abort(403, 'Unauthorized. Raw data export privilege is strictly restricted to Head Office Finance Admins.');
        }

        $format = $request->get('format', 'xlsx');
        $fileName = 'ACI_Expenses_' . date('Y_m_d_His') . '.' . $format;

        $filters = $request->only([
            'depot_id', 'business_id', 'expense_head_id',
            'from_date', 'to_date', 'month', 'year',
            'from_month', 'to_month', 'from_year', 'to_year',
        ]);

        if ($format === 'csv') {
            return Excel::download(new ExpensesExport($filters), $fileName, \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download(new ExpensesExport($filters), $fileName, \Maatwebsite\Excel\Excel::XLSX);
    }
}
