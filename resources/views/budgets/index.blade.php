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
                <select name="year" required data-ts-grid="1" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @php $yearOptions = collect(range(now()->year - 4, now()->year + 1))->push((int) $year)->unique()->sort()->values(); @endphp
                    @foreach($yearOptions as $y)
                        <option value="{{ $y }}" {{ (int) $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
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
                    @php $yearFilterOptions = collect([now()->year, now()->year - 1, now()->year - 2])->push((int) $year)->unique()->sortDesc()->values(); @endphp
                    @foreach($yearFilterOptions as $y)
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

    <!-- Depot × Expense Head Expense Matrix (Excel-style spreadsheet) -->
    @php
        $xlsCol = function ($i) {
            $s = '';
            $i = (int) $i + 1;
            while ($i > 0) {
                $m = ($i - 1) % 26;
                $s = chr(65 + $m) . $s;
                $i = intdiv($i - 1, 26);
            }
            return $s;
        };
        $headColCount = $matrixHeads->count();
        // Head columns are labelled A, B, C… (the depot panel has no letter —
        // it sits under the frozen pane, so numbering from it made every
        // letter appear shifted one column left vs its header).
        $totalColLetter = $xlsCol($headColCount);
    @endphp
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-table-cells text-emerald-600 mr-2"></i> Depot × Expense Head Expense Summary
                </h3>
                <p class="text-xs text-slate-400">Spreadsheet-style pivot — hover any cell to inspect it (Period: {{ $month }} {{ $year }})</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="exportTableToExcel('xlsxMatrix', 'Depot_Expense_Matrix_{{ $month }}_{{ $year }}', { strip: '.xlsx-letter, .xlsx-corner-top, .xlsx-rownum, .xlsx-col-off' })"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-sm transition" title="Export the current matrix (selected columns & totals) to Excel">
                    <i class="fa-solid fa-file-excel"></i> Export to Excel
                </button>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.18em]">Freeze Panes: On</span>
            </div>
        </div>

        <!-- Formula bar -->
        <div class="xlsx-bar">
            <span class="xlsx-namebox" id="xlsxCellRef">Σ1</span>
            <span class="xlsx-fx">fx</span>
            <span class="xlsx-formula-text truncate" id="xlsxFormulaText">Grand Total</span>
            <span class="xlsx-bar-value" id="xlsxFormulaValue">৳ {{ number_format($grandTotal, 0) }}</span>
        </div>

        <div class="overflow-auto max-h-[70vh]">
            <table class="xlsx-table" id="xlsxMatrix">
                <thead>
                    <!-- Spreadsheet column-letter strip (depot panel is letter-less;
                         head columns start at A so letters match their headers) -->
                    <tr>
                        <th class="xlsx-corner-top"><i class="fa-solid fa-table-cells text-[10px]"></i></th>
                        <th class="xlsx-letter" title="Depot / Showroom (frozen)"><i class="fa-solid fa-snowflake text-[9px] text-slate-300"></i></th>
                        @foreach($matrixHeads as $h)
                            <th class="xlsx-letter">{{ $xlsCol($loop->index) }}</th>
                        @endforeach
                        <th class="xlsx-letter xlsx-letter-total">{{ $totalColLetter }}</th>
                    </tr>
                    <!-- Column headers -->
                    <tr>
                        <th class="xlsx-head-gutter"></th>
                        <th class="xlsx-head xlsx-corner-left">
                            <span class="text-slate-700">Depot / Showroom</span>
                            <span class="block text-[9px] font-semibold text-slate-400 normal-case tracking-normal mt-0.5">{{ $month }} {{ $year }}</span>
                        </th>
                        @foreach($matrixHeads as $head)
                            <th class="xlsx-head text-right" title="{{ $head->name }} — uncheck to hide this column">
                                <label class="xlsx-colcheck-label" title="Show / hide this column">
                                    <input type="checkbox" class="xlsx-colcheck" checked data-col="{{ $loop->index + 2 }}">
                                    <span>{{ \Illuminate\Support\Str::limit($head->name, 16) }}</span>
                                </label>
                            </th>
                        @endforeach
                        <th class="xlsx-head xlsx-head-total text-right">Total (BDT)</th>
                    </tr>
                </thead>
                <tbody>
                    @if($matrixDepots->isEmpty() || $matrixHeads->isEmpty())
                    <tr>
                        <td colspan="2" class="py-10 text-center text-slate-400 bg-white">
                            Select at least one depot and one expense head in the filters above to build the matrix.
                        </td>
                    </tr>
                    @else
                    @foreach($matrixDepots as $depot)
                    @php
                        $rowNum = $loop->iteration;
                        $rowTotal = 0;
                    @endphp
                    <tr>
                        <th class="xlsx-rownum">{{ $rowNum }}</th>
                        <td class="xlsx-depotcell">
                            <span class="text-yamaha-800 font-bold">{{ $depot->name }}</span>
                            <span class="text-[10px] text-slate-400 block font-mono">{{ $depot->code }}</span>
                        </td>
                        @foreach($matrixHeads as $head)
                        @php
                            $cell = $expenseMatrix[$depot->id][$head->id] ?? 0;
                            $rowTotal += $cell;
                        @endphp
                        <td class="xlsx-cell text-right {{ $cell > 0 ? 'xlsx-num' : 'xlsx-empty' }}"
                            data-ref="{{ $xlsCol($loop->index + 1) }}{{ $rowNum }}"
                            data-label="{{ $depot->name }} × {{ $head->name }}"
                            data-val="৳ {{ number_format($cell, 2) }}"
                            title="{{ $cell > 0 ? '৳ ' . number_format($cell, 2) : '' }}">
                            {{ $cell > 0 ? number_format($cell, 0) : '—' }}
                        </td>
                        @endforeach
                        <td class="xlsx-cell xlsx-rowtotal text-right"
                            data-ref="{{ $totalColLetter }}{{ $rowNum }}"
                            data-label="{{ $depot->name }} — Row Total"
                            data-val="৳ {{ number_format($rowTotal, 2) }}"
                            title="৳ {{ number_format($rowTotal, 2) }}">
                            {{ number_format($rowTotal, 0) }}
                        </td>
                    </tr>
                    @endforeach

                    <!-- Column totals (pinned bottom) -->
                    <tr class="xlsx-totalrow">
                        <th class="xlsx-rownum xlsx-pin-bottom-left">Σ</th>
                        <td class="xlsx-depotcell xlsx-pin-bottom-depot font-black text-slate-900"
                            data-ref="A{{ $matrixDepots->count() + 1 }}" data-label="Column Totals" data-val="৳ {{ number_format($grandTotal, 2) }}">Total</td>
                        @foreach($matrixHeads as $head)
                        <td class="xlsx-cell xlsx-pin-bottom text-right"
                            data-ref="{{ $xlsCol($loop->index + 1) }}{{ $matrixDepots->count() + 1 }}"
                            data-label="Σ {{ $head->name }}"
                            data-val="৳ {{ number_format($headTotals[$head->id], 2) }}"
                            title="{{ $headTotals[$head->id] > 0 ? '৳ ' . number_format($headTotals[$head->id], 2) : '' }}">
                            {{ $headTotals[$head->id] > 0 ? number_format($headTotals[$head->id], 0) : '—' }}
                        </td>
                        @endforeach
                        <td class="xlsx-cell xlsx-pin-bottom xlsx-grand text-right"
                            data-ref="{{ $totalColLetter }}{{ $matrixDepots->count() + 1 }}" data-label="Grand Total" data-val="৳ {{ number_format($grandTotal, 2) }}">
                            {{ number_format($grandTotal, 0) }}
                        </td>
                    </tr>

                    <!-- Share of grand total -->
                    <tr class="xlsx-pctrow">
                        <th class="xlsx-rownum">%</th>
                        <td class="xlsx-depotcell font-semibold text-slate-500">% of Grand Total</td>
                        @foreach($matrixHeads as $head)
                        <td class="text-right whitespace-nowrap text-slate-500 italic">
                            {{ $grandTotal > 0 ? number_format(($headTotals[$head->id] / $grandTotal) * 100, 1) . '%' : '—' }}
                        </td>
                        @endforeach
                        <td class="text-right whitespace-nowrap text-slate-500 italic font-bold">{{ $grandTotal > 0 ? '100%' : '—' }}</td>
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
            <div class="flex items-center gap-2">
                <button type="button" onclick="exportTableToExcel('varianceRankingTable', 'Depot_Variance_Ranking_{{ $month }}_{{ $year }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-sm transition" title="Export this ranking table to Excel">
                    <i class="fa-solid fa-file-excel"></i> Export to Excel
                </button>
                <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1 rounded-xl font-semibold">
                    {{ $varianceTable->count() }} Depots Tracked
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs" id="varianceRankingTable">
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

@push('styles')
<style>
    /* ===== Excel-style spreadsheet matrix ===== */
    .xlsx-bar {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.4rem 0.7rem;
        background: #f8fafc;
        border-bottom: 1px solid #cbd5e1;
        font-size: 11px;
    }
    .xlsx-namebox {
        min-width: 58px;
        text-align: center;
        border: 1px solid #cbd5e1;
        background: #fff;
        border-radius: 0.375rem;
        padding: 0.2rem 0.45rem;
        font-weight: 800;
        color: #0e3a8c;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 11px;
    }
    .xlsx-fx {
        font-style: italic;
        font-family: Georgia, 'Times New Roman', serif;
        font-weight: 700;
        color: #64748b;
        padding-right: 0.55rem;
        border-right: 1px solid #cbd5e1;
    }
    .xlsx-formula-text { color: #0f172a; font-weight: 600; flex: 1; min-width: 0; }
    .xlsx-bar-value { font-weight: 800; color: #0e3a8c; font-variant-numeric: tabular-nums; }

    .xlsx-table {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content;
        min-width: 100%;
        font-size: 11px;
    }
    .xlsx-table th, .xlsx-table td {
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.55rem 0.85rem;
        white-space: nowrap;
        background: #fff;
    }
    /* Column-letter strip */
    .xlsx-letter {
        position: sticky;
        top: 0;
        z-index: 20;
        background: #f1f5f9;
        color: #64748b;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-weight: 700;
        font-size: 10px;
        text-align: center;
        height: 26px;
        padding: 0.2rem 0.85rem;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    .xlsx-letter-total { color: #0e3a8c; background: #e8eefc; }
    .xlsx-corner-top {
        position: sticky;
        top: 0;
        left: 0;
        z-index: 40;
        background: #334155;
        color: #94a3b8;
        text-align: center;
        width: 36px;
        min-width: 36px;
        height: 26px;
        padding: 0 !important;
        border-right: 2px solid #cbd5e1 !important;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    /* Column headers */
    .xlsx-head {
        position: sticky;
        top: 26px;
        z-index: 20;
        background: #f8fafc;
        color: #475569;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-size: 10px;
        min-width: 115px;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    .xlsx-head-total { background: #e8eefc; color: #0e3a8c; border-left: 2px solid #cbd5e1; }
    /* Narrow gutter cell above the row-number column (keeps the header row's
       grid slots aligned with the body rows — without it every header sits
       one column left of its data). */
    .xlsx-head-gutter {
        position: sticky;
        top: 26px;
        left: 0;
        z-index: 30;
        background: #f8fafc;
        width: 36px;
        min-width: 36px;
        padding: 0 !important;
        border-right: 2px solid #cbd5e1 !important;
        border-bottom: 2px solid #cbd5e1 !important;
    }
    .xlsx-corner-left {
        left: 36px;
        z-index: 30;
        text-align: left;
        min-width: 170px;
        text-transform: none;
        letter-spacing: 0;
        font-size: 11px;
        border-right: 2px solid #cbd5e1 !important;
    }
    /* Row-number gutter + depot column */
    .xlsx-rownum {
        position: sticky;
        left: 0;
        z-index: 10;
        background: #f1f5f9;
        color: #64748b;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-weight: 700;
        font-size: 10px;
        text-align: center;
        width: 36px;
        min-width: 36px;
        border-right: 2px solid #cbd5e1 !important;
    }
    .xlsx-depotcell {
        position: sticky;
        left: 36px;
        z-index: 10;
        background: #fff;
        min-width: 170px;
        border-right: 2px solid #cbd5e1 !important;
    }
    .xlsx-num { font-weight: 700; color: #1e293b; font-variant-numeric: tabular-nums; }
    .xlsx-empty { color: #cbd5e1; text-align: center; }
    .xlsx-rowtotal { font-weight: 800; color: #0e3a8c; background: #f8fafc; font-variant-numeric: tabular-nums; }

    /* Pinned totals row */
    .xlsx-totalrow th, .xlsx-totalrow td {
        border-top: 2px solid #94a3b8;
        background: #eef2f7;
        font-weight: 800;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
    }
    .xlsx-pin-bottom { position: sticky; bottom: 0; z-index: 20; }
    .xlsx-pin-bottom-left { position: sticky; bottom: 0; left: 0; z-index: 40; }
    .xlsx-pin-bottom-depot { position: sticky; bottom: 0; left: 36px; z-index: 30; }
    .xlsx-grand { color: #047857 !important; font-weight: 900; }

    /* % of grand total row */
    .xlsx-pctrow th, .xlsx-pctrow td {
        background: #fbfcfe;
        color: #94a3b8;
        font-size: 10px;
        padding-top: 0.3rem;
        padding-bottom: 0.3rem;
    }
    .xlsx-pctrow .xlsx-rownum { background: #f8fafc; }

    /* Column show/hide checkboxes in the header */
    .xlsx-colcheck-label {
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        cursor: pointer;
    }
    .xlsx-colcheck {
        width: 12px;
        height: 12px;
        accent-color: #1149b0;
        cursor: pointer;
        flex-shrink: 0;
    }
    .xlsx-colcheck-label:hover span { color: #0e3a8c; }
    /* Unchecked (deselected) column: stays in place, dimmed + excluded from totals */
    .xlsx-table td.xlsx-col-off,
    .xlsx-table th.xlsx-col-off {
        background: #f1f5f9 !important;
        color: #94a3b8 !important;
    }
    .xlsx-table td.xlsx-col-off { font-weight: 500; }

    /* Crosshair hover: cell + row + column highlight */
    .xlsx-table td.x-hot, .xlsx-table th.x-hot {
        background: #dbeafe !important;
        box-shadow: inset 0 0 0 1.5px #2266ef;
        position: relative;
    }
    .xlsx-table tr.x-rowh td, .xlsx-table tr.x-rowh th { background-color: #f4f8ff; }
    .xlsx-table tr.x-rowh td.xlsx-depotcell, .xlsx-table tr.x-rowh th.xlsx-rownum { background-color: #eef4ff; }
    .xlsx-table td.x-colh, .xlsx-table th.x-colh { background-color: #f4f8ff !important; }
    .xlsx-table td.x-colh.x-hot, .xlsx-table tr.x-rowh td.x-hot { background: #dbeafe !important; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // ===== Excel-style crosshair hover + formula bar =====
        const sheet = document.getElementById('xlsxMatrix');
        if (sheet) {
            const refBox = document.getElementById('xlsxCellRef');
            const formulaText = document.getElementById('xlsxFormulaText');
            const formulaValue = document.getElementById('xlsxFormulaValue');
            let defaultRef = refBox.textContent;
            let defaultText = formulaText.textContent;
            let defaultValue = formulaValue.textContent;

            let lastRow = null, lastColIndex = -1, lastCells = [];

            const clearCross = function () {
                lastCells.forEach(c => c.classList.remove('x-hot', 'x-colh'));
                if (lastRow) lastRow.classList.remove('x-rowh');
                lastCells = [];
                lastRow = null;
                lastColIndex = -1;
            };

            const showFormula = function (cell) {
                if (cell && cell.dataset.ref) {
                    refBox.textContent = cell.dataset.ref;
                    formulaText.textContent = cell.dataset.label || '';
                    formulaValue.textContent = cell.dataset.val || '';
                } else {
                    refBox.textContent = defaultRef;
                    formulaText.textContent = defaultText;
                    formulaValue.textContent = defaultValue;
                }
            };

            sheet.addEventListener('mouseover', function (e) {
                const cell = e.target.closest('td, th');
                // Only body cells get the selection ring. Hovering the letter
                // strip, column headers, or leaving the sheet entirely resets
                // the crosshair — otherwise the ring stays stuck on the last
                // cell (e.g. a column header) after the mouse moves away.
                if (!cell || !cell.closest('tbody')) {
                    clearCross();
                    showFormula(null);
                    return;
                }

                const rowIndex = cell.parentElement.rowIndex;
                const colIndex = cell.cellIndex;
                // Same cell as before? nothing to redo.
                if (lastRow && lastColIndex === colIndex && lastRow.rowIndex === rowIndex) return;

                clearCross();

                // highlight the cell, its whole row, and its whole column
                cell.classList.add('x-hot');
                lastRow = cell.parentElement;
                lastRow.classList.add('x-rowh');
                lastColIndex = colIndex;
                Array.from(sheet.rows).forEach(tr => {
                    const c = cellAt(tr, colIndex);
                    if (c && c !== cell) {
                        c.classList.add('x-colh');
                        lastCells.push(c);
                    }
                });
                lastCells.push(cell);
                showFormula(cell);
            });

            sheet.addEventListener('mouseleave', function () {
                clearCross();
                showFormula(null);
            });

            // ===== Column show/hide checkboxes =====
            // Unchecking a head column hides it and recomputes every visible
            // total (row totals, grand total, % of grand total) client-side.
            const fmt = n => n.toLocaleString('en-US');
            const cellNum = c => parseFloat((c.textContent || '').replace(/[^\d.-]/g, '')) || 0;
            // Every row now shares the same grid: k=0 gutter, k=1 depot,
            // k=2… heads — so a column's cells all share one index.
            const cellAt = (tr, col) => tr.cells[col];
            const headerRow = Array.from(sheet.rows).find(r => r.querySelector('.xlsx-head-total'));
            const headCount = headerRow.cells.length - 2; // corner + heads + total
            const totalColIndex = 2 + headCount;          // 0 = gutter, 1 = depot, heads start at 2

            const recomputeTotals = function () {
                const bodyRows = Array.from(sheet.tBodies[0].rows);
                const depotRows = bodyRows.filter(tr => !tr.classList.contains('xlsx-totalrow') && !tr.classList.contains('xlsx-pctrow'));
                const totalRow = bodyRows.find(tr => tr.classList.contains('xlsx-totalrow'));
                const pctRow = bodyRows.find(tr => tr.classList.contains('xlsx-pctrow'));

                // Row totals: sum only SELECTED (non-dimmed) head cells —
                // unchecked columns stay visible but are excluded.
                depotRows.forEach(tr => {
                    let sum = 0;
                    for (let i = 2; i < totalColIndex; i++) {
                        const c = tr.cells[i];
                        if (c && !c.classList.contains('xlsx-col-off')) sum += cellNum(c);
                    }
                    const totalCell = tr.cells[totalColIndex];
                    totalCell.textContent = fmt(sum);
                    totalCell.dataset.val = '৳ ' + sum.toFixed(2);
                    totalCell.title = '৳ ' + sum.toFixed(2);
                });

                // Column totals are per-column — recompute grand total from the
                // SELECTED columns' Σ cells, then refresh each % cell.
                let grand = 0;
                for (let i = 2; i < totalColIndex; i++) {
                    const sigmaCell = totalRow.cells[i];
                    if (!sigmaCell.classList.contains('xlsx-col-off')) grand += cellNum(sigmaCell);
                }
                const grandCell = totalRow.cells[totalColIndex];
                grandCell.textContent = fmt(grand);
                grandCell.dataset.val = '৳ ' + grand.toFixed(2);
                grandCell.title = '৳ ' + grand.toFixed(2);

                if (pctRow) {
                    for (let i = 2; i < totalColIndex; i++) {
                        const c = pctRow.cells[i];
                        if (!c.classList.contains('xlsx-col-off')) {
                            const colSum = cellNum(totalRow.cells[i]);
                            c.textContent = grand > 0 ? (colSum / grand * 100).toFixed(1) + '%' : '—';
                        }
                    }
                    const pctAll = pctRow.cells[totalColIndex];
                    pctAll.textContent = grand > 0 ? '100%' : '—';
                }

                // Formula bar default reflects the visible grand total
                defaultValue = '৳ ' + grand.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (!document.querySelector('#xlsxMatrix .x-hot')) {
                    formulaValue.textContent = defaultValue;
                }
            };

            sheet.addEventListener('change', function (e) {
                if (!e.target.matches('.xlsx-colcheck')) return;
                const col = parseInt(e.target.dataset.col, 10);
                // Unchecked columns are NOT removed — they stay in place,
                // dimmed, and are simply excluded from every total.
                Array.from(sheet.rows).forEach(tr => {
                    const c = cellAt(tr, col);
                    if (c) c.classList.toggle('xlsx-col-off', !e.target.checked);
                });

                recomputeTotals();
            });
        }

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
