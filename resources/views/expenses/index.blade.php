@extends('layouts.app')

@section('title', 'Depot Expense Logs & Audit')
@section("page_title", "Daily Expense Logs")
@section("page_subtitle", "Depot-wise expense register & audit trail")

@section('content')
<div class="space-y-6">

    <!-- Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Expense Logs</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Daily Depot Expense Audit Logs</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                @if(auth()->user()->isDepotOperator())
                    Viewing exclusive daily records for <strong class="text-emerald-700">{{ auth()->user()->depot?->name }} Depot</strong>
                @else
                    Company-wide disbursement audit across all 21 depots and 27 business units
                @endif
            </p>
        </div>

        <div class="flex items-center space-x-3">
            @can('export-raw-data')
            <a href="{{ route('expenses.export', ['format' => 'xlsx'] + request()->all()) }}" class="inline-flex items-center px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition">
                <i class="fa-solid fa-file-excel mr-2 text-emerald-300"></i> Export Excel (.xlsx)
            </a>
            <a href="{{ route('expenses.export', ['format' => 'csv'] + request()->all()) }}" class="inline-flex items-center px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl shadow-md transition">
                <i class="fa-solid fa-file-csv mr-1.5 text-slate-400"></i> CSV
            </a>
            @else
            <span class="text-xs bg-slate-100 text-slate-500 px-3 py-1.5 rounded-xl border border-slate-200 flex items-center" title="Only Head Office users can download raw data">
                <i class="fa-solid fa-lock text-slate-400 mr-2"></i> Export Restricted
            </span>
            @endcan

            @can('create-expense')
            <a href="{{ route('expenses.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/30 transition">
                <i class="fa-solid fa-plus-circle mr-2"></i> New Daily Expense Entry
            </a>
            @endcan
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <form action="{{ route('expenses.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Depot -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Depot</label>
                <select name="depot_id" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @if(auth()->user()->isDepotOperator())
                        <option value="{{ auth()->user()->depot_id }}">{{ auth()->user()->depot?->name }}</option>
                    @else
                        <option value="">All Depots</option>
                        @foreach($depots as $d)
                            <option value="{{ $d->id }}" {{ request('depot_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Business -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Business Unit</label>
                <select name="business_id" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Businesses</option>
                    @foreach($businesses as $b)
                        <option value="{{ $b->id }}" {{ request('business_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Expense Head -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Expense Head</label>
                <select name="expense_head_id" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Heads</option>
                    @foreach($expenseHeads as $h)
                        <option value="{{ $h->id }}" {{ request('expense_head_id') == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Month -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Month</label>
                <select name="month" data-ts-grid="1" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Months</option>
                    @foreach(['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $m)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date Range -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <!-- Actions -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-sm transition">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Filter
                </button>
                <a href="{{ route('expenses.index') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition" title="Clear Filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Expenses Table Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                        <th class="py-3.5 px-4">#ID</th>
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Depot</th>
                        <th class="py-3.5 px-4">Business Unit</th>
                        <th class="py-3.5 px-4">Expense Head</th>
                        <th class="py-3.5 px-4">Transport Mode</th>
                        <th class="py-3.5 px-4 text-right">Amount (BDT)</th>
                        <th class="py-3.5 px-4">Entered By</th>
                        <th class="py-3.5 px-4 text-center">Voucher</th>
                        <th class="py-3.5 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4 font-mono text-slate-400 font-semibold">
                            #{{ $expense->id }}
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                            {{ $expense->date->format('d M Y') }}
                            <span class="block text-[10px] text-slate-400 font-normal">{{ $expense->month }} {{ $expense->year }}</span>
                        </td>
                        <td class="py-3 px-4 font-semibold text-emerald-800 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200">
                                {{ $expense->depot?->name }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-800 whitespace-nowrap">
                            {{ $expense->business?->name }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-slate-900 font-bold">{{ $expense->expenseHead?->name }}</span>
                            @if($expense->remarks)
                                <span class="block text-[11px] text-slate-400 truncate max-w-xs" title="{{ $expense->remarks }}">
                                    {{ $expense->remarks }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                            {{ $expense->transport_mode ?? '—' }}
                        </td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 whitespace-nowrap text-sm">
                            ৳ {{ number_format($expense->amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                            {{ $expense->user?->name ?? 'System' }}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            @if($expense->voucher_path)
                                <a href="{{ asset('storage/' . $expense->voucher_path) }}" target="_blank" class="inline-flex items-center px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-[11px] font-bold hover:bg-indigo-100">
                                    <i class="fa-solid fa-paperclip mr-1"></i> Receipt
                                </a>
                            @else
                                <span class="text-slate-300 text-[11px]">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="{{ route('expenses.show', $expense->id) }}" class="text-emerald-600 hover:text-emerald-800 font-bold hover:underline">
                                Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-folder-open text-3xl mb-2 text-slate-300 block"></i>
                            No expense records found matching the specified parameters.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
