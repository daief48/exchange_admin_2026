@extends('layouts.app')

@section('title', 'My Depot Expenses')
@section("page_title", "My Depot Expenses")
@section("page_subtitle", "Every entry recorded for " . (auth()->user()->depot?->name ?? 'your depot') . " — read-only log")

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-50 text-cyan-700 border border-cyan-200">
                    <i class="fa-solid fa-warehouse mr-1"></i> {{ auth()->user()->depot?->name }} ({{ auth()->user()->depot?->code }})
                </span>
                <span>Depot panel — read-only</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">My Depot Expense Log</h1>
            <p class="text-xs text-slate-500 mt-0.5">Every expense recorded for your depot, newest first. New entries go through the Daily Expense Entry form.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="exportTableToExcel('myDepotTable', 'My_Depot_Expenses_{{ now()->format('Ymd') }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-sm transition" title="Export this table to Excel">
                <i class="fa-solid fa-file-excel"></i> Export to Excel
            </button>
            @can('create-expense')
            <a href="{{ route('expenses.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white text-xs font-bold rounded-xl shadow-sm transition">
                <i class="fa-solid fa-plus"></i> New Daily Entry
            </a>
            @endcan
        </div>
    </div>

    <!-- Mini KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm relative overflow-hidden">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Entries Recorded</p>
            <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $expenses->total() }}</h3>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-yamaha-500"></div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm relative overflow-hidden">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $month }} {{ $year }} Spend</p>
            <h3 class="text-2xl font-black text-yamaha-700 mt-1">৳ {{ number_format($monthTotal, 2) }}</h3>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-cyan-400"></div>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm relative overflow-hidden">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">All-Time Total</p>
            <h3 class="text-2xl font-black text-emerald-700 mt-1">৳ {{ number_format($allTimeTotal, 2) }}</h3>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-500"></div>
        </div>
    </div>

    <!-- Expense log table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-receipt text-slate-600 mr-2"></i> Daily Expense Incurrences
                </h3>
                <p class="text-xs text-slate-400">Your depot's complete expense history</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs" id="myDepotTable">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                        <th class="py-3.5 px-4">Date</th>
                        <th class="py-3.5 px-4">Business Unit</th>
                        <th class="py-3.5 px-4">Expense Head</th>
                        <th class="py-3.5 px-4">Remarks</th>
                        <th class="py-3.5 px-4 text-right">Amount (BDT)</th>
                        <th class="py-3.5 px-4">Entered By</th>
                        <th class="py-3.5 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($expenses as $expense)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                            {{ $expense->date->format('d M Y') }}
                            <span class="block text-[10px] text-slate-400 font-normal">{{ $expense->month }} {{ $expense->year }}</span>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-800 whitespace-nowrap">{{ $expense->business?->name }}</td>
                        <td class="py-3 px-4">
                            <span class="text-slate-900 font-bold">{{ $expense->expenseHead?->name }}</span>
                        </td>
                        <td class="py-3 px-4 text-slate-500 max-w-[220px]">
                            <span class="truncate block" title="{{ $expense->remarks }}">{{ $expense->remarks ?? '—' }}</span>
                        </td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 whitespace-nowrap text-sm">
                            ৳ {{ number_format($expense->amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $expense->user?->name ?? 'System' }}</td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="{{ route('expenses.show', $expense->id) }}" class="text-emerald-600 hover:text-emerald-800 font-bold hover:underline">Details</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-slate-400">
                            No expenses recorded for your depot yet — use <strong>New Daily Entry</strong> to add one.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100">
            {{ $expenses->links() }}
        </div>
    </div>

</div>
@endsection
