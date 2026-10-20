@extends('layouts.app')

@section('title', 'Executive Analytics Dashboard')
@section('page_title', 'Executive Analytics Dashboard')
@section('page_subtitle', 'KPI cards, budget speedometer & multi-depot rankings')

@section('content')
<div class="space-y-6">

    <!-- Page Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">
                    Executive Portal
                </span>
                <span class="text-xs text-slate-400">&bull; Live Data</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1 tracking-tight">Distribution & Showroom Expense Analytics</h1>
            <p class="text-sm text-slate-500 mt-0.5">Real-time financial visibility, budget consumption speedometers, and multi-depot rankings.</p>
        </div>

        <div class="flex items-center space-x-3">
            @can('export-raw-data')
            <div class="relative inline-block text-left" id="exportDropdownContainer">
                <a href="{{ route('expenses.export', ['format' => 'xlsx'] + request()->all()) }}" class="inline-flex items-center px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition">
                    <i class="fa-solid fa-file-excel mr-2 text-emerald-300"></i> Export Raw Excel (.xlsx)
                </a>
                <a href="{{ route('expenses.export', ['format' => 'csv'] + request()->all()) }}" class="inline-flex items-center px-3 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl shadow-md transition ml-1">
                    <i class="fa-solid fa-file-csv mr-1.5 text-slate-400"></i> CSV
                </a>
            </div>
            @else
            <div class="text-xs bg-slate-100 text-slate-500 px-3 py-1.5 rounded-xl border border-slate-200 flex items-center">
                <i class="fa-solid fa-lock text-slate-400 mr-2"></i> Raw Excel Export Restricted (HO Only)
            </div>
            @endcan

            @can('create-expense')
            <a href="{{ route('expenses.create') }}" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl shadow-md shadow-emerald-600/30 transition">
                <i class="fa-solid fa-plus-circle mr-2"></i> New Daily Expense Entry
            </a>
            @endcan
        </div>
    </div>

    <!-- Module 4: Multi-Dimensional Filter Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-filter text-emerald-600 text-sm"></i>
                <h2 class="text-sm font-bold text-slate-800 tracking-tight">Multi-Dimensional Filter Bar (Dynamic AJAX Sync)</h2>
            </div>
            <button type="button" id="resetFiltersBtn" class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center transition">
                <i class="fa-solid fa-rotate-left mr-1.5"></i> Reset All Filters
            </button>
        </div>

        <form id="filterForm" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <!-- Year Range -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Year Range</label>
                <div class="grid grid-cols-2 gap-1">
                    <select name="from_year" id="filterFromYear" data-ts-grid="1" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none" title="From Year">
                        @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (string) request('from_year', now()->year) == (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                    <select name="to_year" id="filterToYear" data-ts-grid="1" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none" title="To Year">
                        @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ (string) request('to_year', now()->year) == (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Month Range -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Month Range</label>
                <div class="grid grid-cols-2 gap-1">
                    <select name="from_month" id="filterFromMonth" data-ts-grid="1" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none" title="From Month">
                        <option value="">Start (Jan)</option>
                        @foreach(['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $m)
                        <option value="{{ $m }}" {{ request('from_month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                    <select name="to_month" id="filterToMonth" data-ts-grid="1" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none" title="To Month">
                        <option value="">End (Dec)</option>
                        @foreach(['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $m)
                        <option value="{{ $m }}" {{ request('to_month') == $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Depot -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Depot / Showroom</label>
                <select name="depot_id" id="filterDepot" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @if(auth()->user()->isDepotOperator())
                        <option value="{{ auth()->user()->depot_id }}">{{ auth()->user()->depot?->name }} (Assigned)</option>
                    @else
                        <option value="">All ({{ $depots->count() }}) Depots</option>
                        @foreach($depots as $depot)
                            <option value="{{ $depot->id }}" {{ request('depot_id') == $depot->id ? 'selected' : '' }}>{{ $depot->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <!-- Business Unit -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Business Unit</label>
                <select name="business_id" id="filterBusiness" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All ({{ $businesses->count() }}) Businesses</option>
                    @foreach($businesses as $bu)
                        <option value="{{ $bu->id }}" {{ request('business_id') == $bu->id ? 'selected' : '' }}>{{ $bu->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Expense Head -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Expense Head</label>
                <select name="expense_head_id" id="filterExpenseHead" class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">All ({{ $expenseHeads->count() }}) Expense Heads</option>
                    @foreach($expenseHeads as $head)
                        <option value="{{ $head->id }}" {{ request('expense_head_id') == $head->id ? 'selected' : '' }}>{{ $head->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Date Range (From - To) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Date Range</label>
                <div class="grid grid-cols-2 gap-1">
                    <input type="date" name="from_date" id="filterFromDate" value="{{ request('from_date') }}" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none" title="From Date">
                    <input type="date" name="to_date" id="filterToDate" value="{{ request('to_date') }}" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none" title="To Date">
                </div>
            </div>
        </form>
    </div>

    <!-- Module 3: Top 4 KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- KPI 1: Total Expense -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Expense (Filtered)</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1" id="kpiTotalExpense">
                        ৳ {{ number_format($totalExpenseMillion, 2) }} M
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Raw Total:</span>
                <span class="font-bold text-slate-800" id="kpiTotalRaw">৳ {{ number_format($totalExpenseRaw, 2) }}</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-500"></div>
        </div>

        <!-- KPI 2: Total Expense This Month -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Expense This Month</p>
                    <h3 class="text-2xl font-black text-sky-600 mt-1" id="kpiThisMonthExpense">
                        ৳ {{ number_format($thisMonthMillion, 2) }} M
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Month: <strong class="text-slate-800">{{ $currentMonthLabel }}</strong></span>
                <span class="font-bold text-sky-700" id="kpiThisMonthRaw">৳ {{ number_format($thisMonthExpenseRaw, 2) }}</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-yamaha-600 to-cyan-400"></div>
        </div>

        <!-- KPI 3: Depot Most Spend -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Depot Most Spend</p>
                    <h3 class="text-xl font-black text-amber-600 mt-1 truncate max-w-[170px]" id="kpiDepotMostSpend" title="{{ $depotMostSpend }}">
                        {{ $depotMostSpend }}
                    </h3>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-warehouse"></i>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Depot Spend:</span>
                <span class="font-bold text-amber-700" id="kpiDepotSpendAmount">৳ {{ number_format($topDepotSpendMillion, 2) }} M</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-amber-500"></div>
        </div>

        <!-- KPI 4: % Budget Used Speedometer (Half-Circle Gauge) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">% Budget Consumed</p>
                    <div class="flex items-baseline space-x-2 mt-0.5">
                        <span class="text-2xl font-black text-slate-900" id="gaugeValueText">{{ $budgetConsumedPercentage }}%</span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full" id="gaugeStatusBadge" style="background-color: {{ $gaugeStatusColor }}20; color: {{ $gaugeStatusColor }};">
                            {{ $gaugeStatusLabel }}
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-gauge"></i>
                </div>
            </div>

            <!-- Mini Speedometer Chart Container -->
            <div class="relative flex items-center justify-center -my-3">
                <div id="speedometerGauge" class="w-full flex justify-center"></div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
                <span class="text-[10px] font-semibold text-slate-400">Green &lt;75% | Red &gt;95%</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1" id="gaugeFooterBar" style="background-color: {{ $gaugeStatusColor }}"></div>
        </div>
    </div>

    <!-- Interactive Visualizations Grid (Charts) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Chart 1: Depot Ranking Bar Chart (Ranked from highest to lowest spender) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-chart-bar text-emerald-600 mr-2"></i> Depot Ranking (Highest to Lowest Spender)
                    </h3>
                    <p class="text-xs text-slate-400">Monthly expenditure comparison across distribution hubs</p>
                </div>
                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-lg font-semibold">Million BDT</span>
            </div>
            <div id="depotRankingChart" class="w-full min-h-[340px]"></div>
        </div>

        <!-- Chart 2: Daily/Monthly Expense Trend (Time-Series) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-chart-line text-blue-600 mr-2"></i> Daily Expense Trend Line
                    </h3>
                    <p class="text-xs text-slate-400">Daily logistics disbursement pattern over time</p>
                </div>
                <span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-lg font-semibold">BDT Timeline</span>
            </div>
            <div id="expenseTrendChart" class="w-full min-h-[340px]"></div>
        </div>

        <!-- Chart 3: Expense Head Distribution (Donut Chart) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-chart-pie text-indigo-600 mr-2"></i> Top Expense Head Distribution
                    </h3>
                    <p class="text-xs text-slate-400">Share of carriage, rent, utility, and labor overheads</p>
                </div>
                <span class="text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded-lg font-semibold">Category Share</span>
            </div>
            <div id="expenseHeadChart" class="w-full min-h-[340px] flex items-center justify-center"></div>
        </div>

        <!-- Chart 4: Business Unit Spend Breakdown (Horizontal Bar) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center">
                        <i class="fa-solid fa-cubes text-teal-600 mr-2"></i> Business Unit Spend Breakdown
                    </h3>
                    <p class="text-xs text-slate-400">Expenditures attributed to product lines & spares</p>
                </div>
                <span class="text-xs bg-teal-50 text-teal-700 px-2 py-1 rounded-lg font-semibold">Top Business Units</span>
            </div>
            <div id="businessSpendChart" class="w-full min-h-[340px]"></div>
        </div>
    </div>

    <!-- Recent Expense Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center">
                    <i class="fa-solid fa-receipt text-slate-600 mr-2"></i> Recent Daily Expense Incurrences
                </h3>
                <p class="text-xs text-slate-400">Latest expense entries recorded from depot operations</p>
            </div>
            <a href="{{ route('expenses.index') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center">
                View Full Expense Audit Log <i class="fa-solid fa-arrow-right ml-1.5"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Depot</th>
                        <th class="py-3 px-4">Business Unit</th>
                        <th class="py-3 px-4">Expense Head</th>
                        <th class="py-3 px-4">Transport Mode</th>
                        <th class="py-3 px-4 text-right">Amount (BDT)</th>
                        <th class="py-3 px-4">Entered By</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700" id="recentExpensesTableBody">
                    @forelse($recentExpenses as $exp)
                    <tr class="hover:bg-slate-50/70 transition">
                        <td class="py-3 px-4 font-semibold text-slate-900 whitespace-nowrap">
                            {{ $exp->date->format('d M Y') }}
                        </td>
                        <td class="py-3 px-4 font-semibold text-emerald-800 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200">
                                {{ $exp->depot?->name }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-800 whitespace-nowrap">
                            {{ $exp->business?->name }}
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="text-slate-800 font-semibold">{{ $exp->expenseHead?->name }}</span>
                            @if($exp->remarks)
                                <span class="block text-[11px] text-slate-400 truncate max-w-xs">{{ $exp->remarks }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                            {{ $exp->transport_mode ?? '—' }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900 whitespace-nowrap">
                            ৳ {{ number_format($exp->amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                            {{ $exp->user?->name ?? 'System' }}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            <a href="{{ route('expenses.show', $exp->id) }}" class="text-emerald-600 hover:text-emerald-800 font-bold hover:underline">
                                Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            No expense records found matching the active filter criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Raw seed data from server
        let initialDepotRanking = @json($depotSpendData);
        let initialExpenseHead = @json($expenseHeadData);
        let initialBusinessSpend = @json($businessSpendData);
        let initialTrend = @json($trendData);
        let initialPercentage = {{ $budgetConsumedPercentage }};
        let initialGaugeColor = '{{ $gaugeStatusColor }}';

        // 1. Initialize Speedometer Half-Circle Gauge
        const gaugeOptions = {
            series: [initialPercentage > 100 ? 100 : initialPercentage],
            chart: {
                type: 'radialBar',
                height: 190,
                offsetY: -10,
                sparkline: { enabled: true }
            },
            plotOptions: {
                radialBar: {
                    startAngle: -90,
                    endAngle: 90,
                    hollow: {
                        margin: 0,
                        size: '70%',
                    },
                    track: {
                        background: '#e2e8f0',
                        strokeWidth: '97%',
                        margin: 5,
                    },
                    dataLabels: {
                        name: { show: false },
                        value: {
                            offsetY: -5,
                            fontSize: '18px',
                            fontWeight: '800',
                            formatter: function (val) {
                                return initialPercentage + "%";
                            }
                        }
                    }
                }
            },
            fill: {
                colors: [initialGaugeColor]
            },
            stroke: {
                lineCap: 'round'
            },
            labels: ['Budget Used'],
        };
        const gaugeChart = new ApexCharts(document.querySelector("#speedometerGauge"), gaugeOptions);
        gaugeChart.render();

        // 2. Initialize Depot Ranking Bar Chart
        const rankingOptions = {
            series: [{
                name: 'Total Spend (Million BDT)',
                data: initialDepotRanking.map(d => d.total_m)
            }],
            chart: {
                type: 'bar',
                height: 330,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    horizontal: false,
                    columnWidth: '55%',
                    distributed: false,
                }
            },
            dataLabels: { enabled: false },
            colors: ['#1149B0'],
            xaxis: {
                categories: initialDepotRanking.map(d => d.name),
                labels: {
                    rotate: -45,
                    style: { fontSize: '11px', fontWeight: 600 }
                }
            },
            yaxis: {
                title: { text: 'Million ৳' },
                labels: {
                    formatter: function(val) { return val.toFixed(2); }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) { return "৳ " + val.toFixed(2) + " M"; }
                }
            }
        };
        const rankingChart = new ApexCharts(document.querySelector("#depotRankingChart"), rankingOptions);
        rankingChart.render();

        // 3. Initialize Daily Trend Line Chart
        const trendOptions = {
            series: [{
                name: 'Daily Incurred Expense',
                data: initialTrend.map(t => t.total)
            }],
            chart: {
                type: 'area',
                height: 330,
                toolbar: { show: false }
            },
            colors: ['#2266EF'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2.5 },
            xaxis: {
                categories: initialTrend.map(t => t.date),
                labels: { style: { fontSize: '10px' } }
            },
            yaxis: {
                labels: {
                    formatter: function(val) { return "৳ " + (val / 1000).toFixed(0) + "k"; }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) { return "৳ " + val.toLocaleString(); }
                }
            }
        };
        const trendChart = new ApexCharts(document.querySelector("#expenseTrendChart"), trendOptions);
        trendChart.render();

        // 4. Initialize Expense Head Distribution (Donut)
        const headOptions = {
            series: initialExpenseHead.map(h => h.total),
            labels: initialExpenseHead.map(h => h.name),
            chart: {
                type: 'donut',
                height: 320,
            },
            colors: ['#2266EF', '#00B4E6', '#4D8DF4', '#06B6D4', '#818CF8', '#38BDF8', '#0E3A8C', '#67E8F9'],
            legend: {
                position: 'bottom',
                fontSize: '11px',
                formatter: function(seriesName, opts) {
                    return seriesName;
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) { return "৳ " + val.toLocaleString(); }
                }
            },
            dataLabels: { enabled: false }
        };
        const headChart = new ApexCharts(document.querySelector("#expenseHeadChart"), headOptions);
        headChart.render();

        // 5. Initialize Business Unit Horizontal Bar Chart
        const businessOptions = {
            series: [{
                name: 'Business Spend (BDT)',
                data: initialBusinessSpend.map(b => b.total)
            }],
            chart: {
                type: 'bar',
                height: 330,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '65%'
                }
            },
            colors: ['#00B4E6'],
            dataLabels: { enabled: false },
            xaxis: {
                categories: initialBusinessSpend.map(b => b.name),
                labels: {
                    formatter: function(val) { return (val / 1000000).toFixed(1) + "M"; }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) { return "৳ " + val.toLocaleString(); }
                }
            }
        };
        const businessChart = new ApexCharts(document.querySelector("#businessSpendChart"), businessOptions);
        businessChart.render();

        // 6. Dynamic AJAX Filter Bar Sync without page reload
        const filterForm = document.getElementById('filterForm');
        const filterInputs = filterForm.querySelectorAll('select, input');

        function fetchFilteredData() {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);

            fetch(`{{ route('dashboard.data') }}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                // Update KPI Cards
                document.getElementById('kpiTotalExpense').innerText = "৳ " + data.totalExpenseMillion.toFixed(2) + " M";
                document.getElementById('kpiTotalRaw').innerText = "৳ " + data.totalExpenseRaw.toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('kpiThisMonthExpense').innerText = "৳ " + data.thisMonthMillion.toFixed(2) + " M";
                document.getElementById('kpiThisMonthRaw').innerText = "৳ " + data.thisMonthExpenseRaw.toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('kpiDepotMostSpend').innerText = data.depotMostSpend;
                document.getElementById('kpiDepotSpendAmount').innerText = "৳ " + data.topDepotSpendMillion.toFixed(2) + " M";

                // Update Speedometer Gauge
                document.getElementById('gaugeValueText').innerText = data.budgetConsumedPercentage + "%";
                const badge = document.getElementById('gaugeStatusBadge');
                badge.innerText = data.gaugeStatusLabel;
                badge.style.backgroundColor = data.gaugeStatusColor + "20";
                badge.style.color = data.gaugeStatusColor;
                document.getElementById('gaugeFooterBar').style.backgroundColor = data.gaugeStatusColor;

                gaugeChart.updateOptions({
                    fill: { colors: [data.gaugeStatusColor] },
                    plotOptions: {
                        radialBar: {
                            dataLabels: {
                                value: {
                                    formatter: function() { return data.budgetConsumedPercentage + "%"; }
                                }
                            }
                        }
                    }
                });
                gaugeChart.updateSeries([data.budgetConsumedPercentage > 100 ? 100 : data.budgetConsumedPercentage]);

                // Update Charts
                rankingChart.updateOptions({
                    xaxis: { categories: data.depotSpendData.map(d => d.name) }
                });
                rankingChart.updateSeries([{
                    name: 'Total Spend (Million BDT)',
                    data: data.depotSpendData.map(d => d.total_m)
                }]);

                trendChart.updateOptions({
                    xaxis: { categories: data.trendData.map(t => t.date) }
                });
                trendChart.updateSeries([{
                    name: 'Daily Incurred Expense',
                    data: data.trendData.map(t => t.total)
                }]);

                headChart.updateOptions({
                    labels: data.expenseHeadData.map(h => h.name)
                });
                headChart.updateSeries(data.expenseHeadData.map(h => h.total));

                businessChart.updateOptions({
                    xaxis: { categories: data.businessSpendData.map(b => b.name) }
                });
                businessChart.updateSeries([{
                    name: 'Business Spend (BDT)',
                    data: data.businessSpendData.map(b => b.total)
                }]);

                // Update Table
                const tbody = document.getElementById('recentExpensesTableBody');
                if (data.recentExpenses && data.recentExpenses.length > 0) {
                    tbody.innerHTML = data.recentExpenses.map(exp => `
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-semibold text-slate-900 whitespace-nowrap">${new Date(exp.date).toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'})}</td>
                            <td class="py-3 px-4 font-semibold text-emerald-800 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 border border-emerald-200">${exp.depot ? exp.depot.name : ''}</span>
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-800 whitespace-nowrap">${exp.business ? exp.business.name : ''}</td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="text-slate-800 font-semibold">${exp.expense_head ? exp.expense_head.name : ''}</span>
                                ${exp.remarks ? `<span class="block text-[11px] text-slate-400 truncate max-w-xs">${exp.remarks}</span>` : ''}
                            </td>
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">${exp.transport_mode ? exp.transport_mode : '—'}</td>
                            <td class="py-3 px-4 text-right font-bold text-slate-900 whitespace-nowrap">৳ ${Number(exp.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">${exp.user ? exp.user.name : 'System'}</td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <a href="/expenses/${exp.id}" class="text-emerald-600 hover:text-emerald-800 font-bold hover:underline">Details</a>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-slate-400">No expense records found matching the active filter criteria.</td></tr>`;
                }
            })
            .catch(err => console.error("Error refreshing analytics:", err));
        }

        filterInputs.forEach(input => {
            input.addEventListener('change', fetchFilteredData);
        });

        document.getElementById('resetFiltersBtn').addEventListener('click', function () {
            filterForm.reset();
            fetchFilteredData();
        });
    });
</script>
@endpush
