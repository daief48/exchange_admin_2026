@extends('layouts.app')

@section('title', 'Expense Record #' . $expense->id)
@section("page_title", "Expense Details")
@section("page_subtitle", "Voucher & audit details")

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition">Dashboard</a>
                @if(!auth()->user()->isDepotOperator())
                <span>/</span>
                <a href="{{ route('expenses.index') }}" class="hover:text-emerald-600 transition">Expense Logs</a>
                @endif
                <span>/</span>
                <span class="text-slate-800 font-semibold">Expense #{{ $expense->id }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Expense Audit Details</h1>
            <p class="text-xs text-slate-500 mt-0.5">Recorded on {{ $expense->date->format('l, d F Y') }}</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('expenses.pdf', $expense) }}" target="_blank" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 border border-rose-700 rounded-xl shadow-md shadow-rose-600/30 transition">
                <i class="fa-solid fa-file-pdf mr-1.5"></i> Download PDF
            </a>
            @if(!auth()->user()->isDepotOperator())
            <a href="{{ route('expenses.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Logs
            </a>
            @endif
        </div>
    </div>

    <!-- Detail Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8 space-y-6">
        <!-- Top Amount Banner -->
        <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-md">
            <div>
                <span class="text-xs font-semibold text-emerald-400 uppercase tracking-widest block">Incurred Amount</span>
                <h2 class="text-3xl font-black tracking-tight text-white mt-1">৳ {{ number_format($expense->amount, 2) }}</h2>
                <span class="text-xs text-slate-400 mt-0.5 block">Bangladeshi Taka (BDT)</span>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    Compliant & Verified
                </span>
                <span class="block text-xs text-slate-400 mt-1 font-mono">Tx ID: #{{ $expense->id }}</span>
            </div>
        </div>

        <!-- Metadata Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-1">Depot / Location</span>
                <span class="text-sm font-bold text-slate-900">{{ $expense->depot?->name }} ({{ $expense->depot?->code }})</span>
                <span class="block text-slate-500 text-[11px] mt-0.5">{{ $expense->depot?->contact_email }}</span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-1">Business Unit</span>
                <span class="text-sm font-bold text-slate-900">{{ $expense->business?->name }}</span>
                <span class="block text-slate-500 text-[11px] mt-0.5">Code: {{ $expense->business?->code }}</span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-1">Expense Head</span>
                <span class="text-sm font-bold text-slate-900">{{ $expense->expenseHead?->name }}</span>
                <span class="block text-slate-500 text-[11px] mt-0.5">Category Code: {{ $expense->expenseHead?->code }}</span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-1">Financial Billing Period</span>
                <span class="text-sm font-bold text-slate-900">{{ $expense->month }} {{ $expense->year }}</span>
                <span class="block text-slate-500 text-[11px] mt-0.5">Logged Date: {{ $expense->date->format('Y-m-d') }}</span>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-bold uppercase tracking-wider block mb-1">Entered By</span>
                <span class="text-sm font-bold text-slate-900">{{ $expense->user?->name ?? 'System' }}</span>
                <span class="block text-slate-500 text-[11px] mt-0.5">Timestamp: {{ $expense->created_at->format('Y-m-d H:i:s') }}</span>
            </div>
        </div>

        <!-- Remarks -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs">
            <span class="text-slate-400 font-bold uppercase tracking-wider block mb-1">Remarks & Details</span>
            <p class="text-slate-800 text-sm font-medium leading-relaxed">
                {{ $expense->remarks ?? 'No additional remarks provided.' }}
            </p>
        </div>

        <!-- Voucher Attachment Preview -->
        @if($expense->voucher_path)
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs">
            <span class="text-slate-400 font-bold uppercase tracking-wider block mb-2">Voucher Receipt Attachment</span>
            <div class="flex items-center space-x-3">
                <a href="{{ asset('storage/' . $expense->voucher_path) }}" target="_blank" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold flex items-center transition">
                    <i class="fa-solid fa-arrow-up-right-from-square mr-2"></i> Open Uploaded Voucher
                </a>
                <span class="text-slate-400">File stored securely in storage/app/public</span>
            </div>
        </div>
        @endif
    </div>

</div>
@endsection
