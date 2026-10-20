@extends('layouts.app')

@section('title', 'Budgeting & Variance Engine')
@section("page_title", "Budget & Variance Engine")
@section("page_subtitle", "Actual consumption, variance & threshold status per depot")

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Budgeting & Variance</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Depot Budgeting & Variance Engine</h1>
            <p class="text-xs text-slate-500 mt-0.5">Real-time financial variance calculation, threshold speedometers, and over-budget prevention</p>
        </div>
    </div>

    <!-- Summary KPI Cards for Variance -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <!-- Actual Expense -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Actual Expense</p>
            <h3 class="text-2xl font-black text-emerald-600 mt-1">৳ {{ number_format($totalActualExpense / 1000000, 2) }} M</h3>
            <p class="text-xs text-slate-400 mt-1">Raw: ৳ {{ number_format($totalActualExpense, 2) }}</p>
        </div>

        <!-- Net Variance (Remaining) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Net Variance (Remaining)</p>
            <h3 class="text-2xl font-black {{ $totalVariance >= 0 ? 'text-blue-600' : 'text-rose-600' }} mt-1">
                ৳ {{ number_format($totalVariance / 1000000, 2) }} M
            </h3>
            <p class="text-xs text-slate-400 mt-1">{{ $totalVariance >= 0 ? 'Positive Budget Buffer' : 'Budget Deficit / Over-expended' }}</p>
        </div>

        <!-- Overall % Consumed -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Overall % Consumed</p>
            <div class="flex items-center space-x-2 mt-1">
                <span class="text-2xl font-black text-slate-900">{{ $overallPercentage }}%</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $overallPercentage > 95 ? 'bg-rose-100 text-rose-700' : ($overallPercentage >= 76 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                    {{ $overallPercentage > 95 ? 'Critical' : ($overallPercentage >= 76 ? 'Warning' : 'Safe') }}
                </span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2">
                <div class="h-1.5 rounded-full {{ $overallPercentage > 95 ? 'bg-rose-500' : ($overallPercentage >= 76 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($overallPercentage, 100) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Set / Update Budget Allocation Drawer/Form (For HO Finance Admins) -->
    @if(auth()->user()->isHeadOffice())
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
        <div class="flex items-center space-x-2 mb-4">
            <i class="fa-solid fa-pen-to-square text-emerald-600 text-sm"></i>
            <h3 class="text-sm font-bold text-slate-900">Configure Monthly / Annual Budget Allocation</h3>
        </div>

        <form action="{{ route('budgets.store') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            @csrf
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Depot <span class="text-rose-500">*</span></label>
                <select name="depot_id" required class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @foreach($depots as $depot)
                        <option value="{{ $depot->id }}">{{ $depot->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Year <span class="text-rose-500">*</span></label>
                <input type="number" name="year" value="{{ $year }}" required class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Month <span class="text-rose-500">*</span></label>
                <select name="month" required data-ts-grid="1" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @foreach(['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Business Unit (Optional)</label>
                <select name="business_id" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Business Units</option>
                    @foreach($businesses as $bu)
                        <option value="{{ $bu->id }}">{{ $bu->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Amount (BDT) <span class="text-rose-500">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" placeholder="e.g. 1500000" required class="w-full text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-sm transition">
                    <i class="fa-solid fa-floppy-disk mr-1"></i> Save Budget
                </button>
            </div>
        </form>
    </div>
    @endif

    <!-- Matrix Controls (directly above the table it drives) -->
    @php
        $quickThis = $month === strtoupper(now()->format('M'));
        $quickLast = $month === strtoupper(now()->subMonthNoOverflow()->format('M'));
        $quickAll = $month === 'ALL';
    @endphp
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-yamaha-600 to-cyan-500 h-1"></div>
        <div class="p-4">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-yamaha-600 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-yamaha-600/30">
                        <i class="fa-solid fa-sliders text-sm"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-black text-slate-900 uppercase tracking-[0.14em] leading-none">Matrix Controls</p>
                        <p class="text-[10px] text-slate-400 mt-1 leading-none">Depots = rows &bull; Expense heads = columns</p>
                    </div>
                </div>

                <!-- Quick period pills -->
                <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl" id="quickPeriodPills">
                    <button type="button" data-month="{{ strtoupper(now()->format('M')) }}" class="quick-pill px-3 py-1.5 rounded-lg text-[11px] font-bold transition {{ $quickThis ? 'bg-white text-yamaha-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">This Month</button>
                    <button type="button" data-month="{{ strtoupper(now()->subMonthNoOverflow()->format('M')) }}" class="quick-pill px-3 py-1.5 rounded-lg text-[11px] font-bold transition {{ $quickLast ? 'bg-white text-yamaha-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">Last Month</button>
                    <button type="button" data-month="ALL" class="quick-pill px-3 py-1.5 rounded-lg text-[11px] font-bold transition {{ $quickAll ? 'bg-white text-yamaha-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">Entire Year</button>
                </div>

                <div class="ml-auto flex items-center gap-2">
                    <!-- Live scope summary -->
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500 bg-slate-100 rounded-xl px-3 py-1.5">
                        <i class="fa-solid fa-eye text-[10px] text-yamaha-600"></i>
                        Showing {{ $matrixDepots->count() }} Depots × {{ $matrixHeads->count() }} Heads
                        <span class="text-slate-300">|</span>
                        <span class="text-emerald-700">৳ {{ number_format($grandTotal / 1000000, 1) }} M</span>
                    </span>
                    <button type="button" id="matrixResetBtn" title="Clear depot & expense head selections" class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-400 hover:text-rose-500 bg-slate-50 hover:bg-rose-50 border border-slate-200 rounded-xl px-3 py-1.5 transition">
                        <i class="fa-solid fa-rotate-left text-[10px]"></i> Reset
                    </button>
                </div>
            </div>

            <form action="{{ route('budgets.index') }}" method="GET" id="matrixFilterForm" class="flex flex-wrap items-center gap-2">
                <select name="year" data-ts-grid="1" class="w-28 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @foreach([now()->year, now()->year - 1, now()->year - 2] as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>FY {{ $y }}</option>
                    @endforeach
                </select>

                <select name="month" data-ts-grid="1" class="w-40 text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="ALL" {{ $month == 'ALL' ? 'selected' : '' }}>Entire Year (ALL)</option>
                    @foreach(['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>

                <select name="depot_ids[]" multiple class="flex-1 min-w-[190px] max-w-[260px] text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Depots (rows)</option>
                    @foreach($depots as $depot)
                        <option value="{{ $depot->id }}" @selected(in_array($depot->id, $selectedDepotIds))>{{ $depot->name }}</option>
                    @endforeach
                </select>

                <select name="expense_head_ids[]" multiple class="flex-1 min-w-[230px] max-w-[340px] text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All Expense Heads (columns)</option>
                    @foreach($expenseHeads as $head)
                        <option value="{{ $head->id }}" @selected(in_array($head->id, $selectedHeadIds))>{{ $head->name }}</option>
                    @endforeach
                </select>

                <button type="submit" id="matrixApplyBtn" class="px-5 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shrink-0">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Apply
                </button>
            </form>
            <p class="mt-2 text-[11px] text-slate-400 flex items-center gap-1.5">
                <i class="fa-solid fa-lightbulb text-amber-400"></i>
                Year &amp; month apply instantly — pick specific depots or expense heads (or remove their chips) and hit Apply to reshape the matrix.
            </p>
        </div>
    </div>

    <!-- Depot × Expense Head Expense Matrix (pivot: rows = depots, columns = heads) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-table-cells text-emerald-600 mr-2"></i> Depot × Expense Head Expense Summary
                </h3>
                <p class="text-xs text-slate-400">Actual incurred expenses — biggest spending heads first (Period: {{ $month }} {{ $year }})</p>
            </div>
        </div>

        <div class="overflow-auto max-h-[70vh]">
            <table class="w-full text-left border-collapse text-xs min-w-max">
                <thead>
                    <tr class="text-slate-500 uppercase tracking-wider font-semibold">
                        <th class="sticky top-0 left-0 z-30 bg-slate-50 border-b border-r border-slate-200 py-3.5 px-4 whitespace-nowrap">Depot / Showroom</th>
                        @foreach($matrixHeads as $head)
                            <th class="sticky top-0 z-20 bg-slate-50 border-b border-slate-200 py-3.5 px-4 text-right whitespace-nowrap min-w-[115px]" title="{{ $head->name }}">{{ \Illuminate\Support\Str::limit($head->name, 20) }}</th>
                        @endforeach
                        <th class="sticky top-0 z-20 bg-slate-100 border-b border-l border-slate-200 py-3.5 px-4 text-right whitespace-nowrap">Total (BDT)</th>
                    </tr>
                </thead>
                <tbody class="text-slate-700">
                    @if($matrixDepots->isEmpty() || $matrixHeads->isEmpty())
                    <tr>
                        <td colspan="2" class="py-8 text-center text-slate-400">
                            Select at least one depot and one expense head in the filters above to build the matrix.
                        </td>
                    </tr>
                    @else
                    @foreach($matrixDepots as $depot)
                    @php
                        $rowTotal = 0;
                    @endphp
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="sticky left-0 z-10 bg-white border-r border-slate-100 py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                            <span class="text-emerald-800">{{ $depot->name }}</span>
                            <span class="text-[10px] text-slate-400 block font-normal">{{ $depot->code }}</span>
                        </td>
                        @foreach($matrixHeads as $head)
                        @php
                            $cell = $expenseMatrix[$depot->id][$head->id] ?? 0;
                            $rowTotal += $cell;
                        @endphp
                        <td class="py-3 px-4 text-right whitespace-nowrap {{ $cell > 0 ? 'text-slate-700' : 'text-slate-300' }}" title="{{ $cell > 0 ? '৳ ' . number_format($cell, 2) : '' }}">
                            {{ $cell > 0 ? number_format($cell, 0) : '—' }}
                        </td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-black text-slate-900 whitespace-nowrap border-l border-slate-200 bg-slate-50" title="৳ {{ number_format($rowTotal, 2) }}">
                            ৳ {{ number_format($rowTotal, 0) }}
                        </td>
                    </tr>
                    @endforeach
                    <!-- Column totals (pinned to the bottom while scrolling) -->
                    <tr class="font-black text-slate-900">
                        <td class="sticky bottom-0 left-0 z-40 bg-slate-100 border-t-2 border-r border-slate-300 py-3 px-4 whitespace-nowrap">Total</td>
                        @foreach($matrixHeads as $head)
                        <td class="sticky bottom-0 z-20 bg-slate-100 border-t-2 border-slate-300 py-3 px-4 text-right whitespace-nowrap" title="{{ $headTotals[$head->id] > 0 ? '৳ ' . number_format($headTotals[$head->id], 2) : '' }}">
                            {{ $headTotals[$head->id] > 0 ? '৳ ' . number_format($headTotals[$head->id], 0) : '—' }}
                        </td>
                        @endforeach
                        <td class="sticky bottom-0 z-30 bg-slate-100 border-t-2 border-l border-slate-300 py-3 px-4 text-right whitespace-nowrap text-emerald-700" title="৳ {{ number_format($grandTotal, 2) }}">
                            ৳ {{ number_format($grandTotal, 0) }}
                        </td>
                    </tr>
                    <!-- Each head's share of the grand total -->
                    <tr class="text-slate-500 italic text-[11px] border-t border-slate-100">
                        <td class="py-2 px-4 whitespace-nowrap not-italic font-semibold">% of Grand Total</td>
                        @foreach($matrixHeads as $head)
                        <td class="py-2 px-4 text-right whitespace-nowrap">
                            {{ $grandTotal > 0 ? number_format(($headTotals[$head->id] / $grandTotal) * 100, 1) . '%' : '—' }}
                        </td>
                        @endforeach
                        <td class="py-2 px-4 text-right whitespace-nowrap border-l border-slate-200">{{ $grandTotal > 0 ? '100%' : '—' }}</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Depot Variance Ranking Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-ranking-star text-amber-500 mr-2"></i> Depot Consumption Ranking & Variance Engine
                </h3>
                <p class="text-xs text-slate-400">Ranked from highest budget consumption % to lowest (Period: {{ $month }} {{ $year }})</p>
            </div>
            <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-xl font-semibold">
                {{ $varianceTable->count() }} Depots Tracked
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                        <th class="py-3.5 px-4 text-center">Rank</th>
                        <th class="py-3.5 px-4">Depot / Showroom</th>
                        <th class="py-3.5 px-4 text-right">Actual Incurred (BDT)</th>
                        <th class="py-3.5 px-4 text-right">Remaining Variance (BDT)</th>
                        <th class="py-3.5 px-4 w-48">% Budget Consumed</th>
                        <th class="py-3.5 px-4 text-center">Threshold Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($varianceTable as $index => $row)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4 text-center font-bold text-slate-400">
                            #{{ $index + 1 }}
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">
                            <span class="text-emerald-800">{{ $row['depot']->name }}</span>
                            <span class="text-[10px] text-slate-400 block font-normal">{{ $row['depot']->code }}</span>
                        </td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 whitespace-nowrap">
                            ৳ {{ number_format($row['expense'], 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold {{ $row['variance'] >= 0 ? 'text-blue-600' : 'text-rose-600 font-black' }} whitespace-nowrap">
                            ৳ {{ number_format($row['variance'], 2) }}
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-full bg-slate-100 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $row['percentage'] > 95 ? 'bg-rose-500' : ($row['percentage'] >= 76 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($row['percentage'], 100) }}%"></div>
                                </div>
                                <span class="font-bold text-slate-800 w-12 text-right">{{ $row['percentage'] }}%</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            @if($row['percentage'] > 95)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600 mr-1.5 animate-ping"></span> Critical
                                </span>
                            @elseif($row['percentage'] >= 76)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600 mr-1.5"></span> Warning
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1.5"></span> Safe
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            No depot budget entries found for this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('matrixFilterForm');
        if (!form) return;

        const yearSel = form.querySelector('select[name="year"]');
        const monthSel = form.querySelector('select[name="month"]');
        const applyBtn = document.getElementById('matrixApplyBtn');

        // Year / month changes apply instantly.
        [yearSel, monthSel].forEach(sel => sel.addEventListener('change', () => form.submit()));

        // Chip edits just flag Apply as pending — no auto-submit, so several
        // chips can be removed before the matrix reloads.
        form.querySelectorAll('select[multiple]').forEach(sel => {
            sel.addEventListener('change', () => {
                applyBtn.classList.add('ring-2', 'ring-amber-400', 'animate-pulse', 'bg-amber-500', 'hover:bg-amber-400');
                applyBtn.innerHTML = '<i class="fa-solid fa-arrows-rotate mr-1"></i> Apply Changes';
            });
        });

        // Quick period pills jump straight to that month.
        document.querySelectorAll('#quickPeriodPills .quick-pill').forEach(btn => {
            btn.addEventListener('click', () => {
                monthSel.value = btn.dataset.month;
                form.submit();
            });
        });

        // Reset keeps the year/month, drops depot & head selections.
        document.getElementById('matrixResetBtn').addEventListener('click', () => {
            const url = new URL(form.action);
            url.searchParams.set('year', yearSel.value);
            url.searchParams.set('month', monthSel.value);
            window.location.href = url.toString();
        });
    });
</script>
@endpush
@endsection
