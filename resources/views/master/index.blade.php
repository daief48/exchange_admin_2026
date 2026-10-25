@extends('layouts.app')

@section('title', 'Master Data & RBAC Administration')
@section("page_title", "Master Data & RBAC")
@section("page_subtitle", "Depots, businesses, expense heads, users & alert logs")

@section('content')
<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition">Dashboard</a>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Master Data Management</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Enterprise Master Data & RBAC Control</h1>
            <p class="text-xs text-slate-500 mt-0.5">Corporate configuration for Depots, Business Units, Expense Heads, and Access Governance</p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="px-3 py-1 bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold rounded-xl">
                <i class="fa-solid fa-lock mr-1"></i> Head Office Admin Privilege
            </span>
        </div>
    </div>

    <!-- Single Record Success Banner -->
    @if(session('success'))
    <div class="flex items-center gap-3 p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/5 shadow-sm">
        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-700 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <p class="text-xs font-bold text-emerald-900">{{ session('success') }}</p>
    </div>
    @endif

    <!-- Bulk Import Result Banner -->
    @if(session('bulk_report'))
    @php $report = session('bulk_report'); @endphp
    <div class="p-5 rounded-2xl border shadow-sm {{ $report['failed'] > 0 ? 'bg-amber-50 border-amber-200' : 'bg-yamaha-50 border-yamaha-200' }}">
        <div class="flex items-start gap-3">
            <i class="fa-solid {{ $report['failed'] > 0 ? 'fa-triangle-exclamation text-amber-600' : 'fa-circle-check text-yamaha-600' }} text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="text-sm font-bold {{ $report['failed'] > 0 ? 'text-amber-900' : 'text-yamaha-900' }}">{{ $report['message'] }}</p>
                <div class="flex flex-wrap gap-2 mt-2 text-[11px] font-bold">
                    <span class="px-2 py-1 rounded-lg bg-white border border-yamaha-200 text-yamaha-700">Imported: {{ $report['inserted'] }}</span>
                    <span class="px-2 py-1 rounded-lg bg-white border border-slate-200 text-slate-600">Skipped (existing): {{ $report['skipped'] }}</span>
                    <span class="px-2 py-1 rounded-lg bg-white border {{ $report['failed'] > 0 ? 'border-rose-200 text-rose-600' : 'border-slate-200 text-slate-400' }}">Failed: {{ $report['failed'] }}</span>
                </div>
                @if(count($report['errors']) > 0)
                <ul class="mt-3 space-y-1 text-[11px] text-rose-700 bg-white/70 border border-rose-100 rounded-xl p-3 list-disc list-inside">
                    @foreach($report['errors'] as $error)
                    <li class="font-mono">{{ $error }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Master Data Navigation Tabs -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden" x-data="{ tab: 'depots' }">
        <div class="flex border-b border-slate-200 px-6 pt-4 space-x-6 overflow-x-auto text-xs font-bold" id="masterTabs">
            <button onclick="switchTab('depots')" id="tabBtn-depots" class="pb-3 border-b-2 border-emerald-600 text-emerald-700 flex items-center space-x-2">
                <i class="fa-solid fa-warehouse"></i>
                <span>Depots ({{ $depots->count() }})</span>
            </button>
            <button onclick="switchTab('businesses')" id="tabBtn-businesses" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
                <i class="fa-solid fa-cubes"></i>
                <span>Business Units ({{ $businesses->count() }})</span>
            </button>
            <button onclick="switchTab('heads')" id="tabBtn-heads" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
                <i class="fa-solid fa-list-check"></i>
                <span>Expense Heads ({{ $expenseHeads->count() }})</span>
            </button>
            <button onclick="switchTab('users')" id="tabBtn-users" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
                <i class="fa-solid fa-users"></i>
                <span>User Roles & RBAC ({{ $users->count() }})</span>
            </button>
            <button onclick="switchTab('missing')" id="tabBtn-missing" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
                <i class="fa-solid fa-bell text-rose-500"></i>
                <span>Missing Entry Logs ({{ $missingLogs->count() }})</span>
            </button>
        </div>

        <!-- 1. Depots Panel -->
        <div id="panel-depots" class="p-6 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-[11px] text-slate-400">Register a new location instantly, or bulk import from Excel below.</p>
                <button type="button" onclick="openMasterModal('depots')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-yamaha-600 hover:bg-yamaha-500 text-white text-xs font-bold rounded-xl shadow-md shadow-yamaha-600/30 transition">
                    <i class="fa-solid fa-plus"></i> Add Depot
                </button>
            </div>

            <!-- Bulk Upload: Depots -->
            <div class="p-4 rounded-xl bg-yamaha-50/60 border border-dashed border-yamaha-200">
                <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-1">
                            <i class="fa-solid fa-file-import text-yamaha-600 mr-1"></i> Bulk Upload Depots (Excel / CSV)
                        </h4>
                        <p class="text-[11px] text-slate-500">Columns: <span class="font-mono">name*, code*, contact_email, status</span> — first row = header. Existing names/codes are skipped automatically.</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('master.template', 'depots') }}" class="px-3 py-2 bg-white border border-yamaha-200 text-yamaha-700 text-xs font-bold rounded-xl hover:bg-yamaha-100 transition">
                            <i class="fa-solid fa-download mr-1"></i> Template (.xlsx)
                        </a>
                        <form action="{{ route('master.bulk-import', 'depots') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <label for="bulkFile-depots" class="cursor-pointer px-3 py-2 bg-white border border-slate-300 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-50 transition max-w-[180px] truncate">
                                <i class="fa-solid fa-folder-open mr-1"></i> <span id="bulkFileName-depots">Choose File</span>
                            </label>
                            <input type="file" id="bulkFile-depots" name="file" accept=".csv,.xlsx,.xls" class="hidden" onchange="updateBulkFileName(this)">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-yamaha-600 to-cyan-500 hover:from-yamaha-500 hover:to-cyan-400 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                <i class="fa-solid fa-upload mr-1"></i> Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Depot Location</th>
                            <th class="py-3 px-4">Code</th>
                            <th class="py-3 px-4">Contact Email</th>
                            <th class="py-3 px-4 text-center">Expenses Count</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($depots as $index => $d)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 text-slate-400 font-mono">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $d->name }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-emerald-800">{{ $d->code }}</td>
                            <td class="py-3 px-4 text-slate-500">{{ $d->contact_email }}</td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800">{{ $d->expenses_count }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Active</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Business Units Panel -->
        <div id="panel-businesses" class="p-6 space-y-6 hidden">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-[11px] text-slate-400">Register a new business category instantly, or bulk import from Excel below.</p>
                <button type="button" onclick="openMasterModal('businesses')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-yamaha-600 hover:bg-yamaha-500 text-white text-xs font-bold rounded-xl shadow-md shadow-yamaha-600/30 transition">
                    <i class="fa-solid fa-plus"></i> Add Business Unit
                </button>
            </div>

            <!-- Bulk Upload: Businesses -->
            <div class="p-4 rounded-xl bg-yamaha-50/60 border border-dashed border-yamaha-200">
                <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-1">
                            <i class="fa-solid fa-file-import text-yamaha-600 mr-1"></i> Bulk Upload Business Units (Excel / CSV)
                        </h4>
                        <p class="text-[11px] text-slate-500">Columns: <span class="font-mono">name*, code*, status</span> — first row = header. Existing names/codes are skipped automatically.</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('master.template', 'businesses') }}" class="px-3 py-2 bg-white border border-yamaha-200 text-yamaha-700 text-xs font-bold rounded-xl hover:bg-yamaha-100 transition">
                            <i class="fa-solid fa-download mr-1"></i> Template (.xlsx)
                        </a>
                        <form action="{{ route('master.bulk-import', 'businesses') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <label for="bulkFile-businesses" class="cursor-pointer px-3 py-2 bg-white border border-slate-300 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-50 transition max-w-[180px] truncate">
                                <i class="fa-solid fa-folder-open mr-1"></i> <span id="bulkFileName-businesses">Choose File</span>
                            </label>
                            <input type="file" id="bulkFile-businesses" name="file" accept=".csv,.xlsx,.xls" class="hidden" onchange="updateBulkFileName(this)">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-yamaha-600 to-cyan-500 hover:from-yamaha-500 hover:to-cyan-400 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                <i class="fa-solid fa-upload mr-1"></i> Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Business Unit Name</th>
                            <th class="py-3 px-4">Code</th>
                            <th class="py-3 px-4 text-center">Expenses Count</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($businesses as $index => $b)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 text-slate-400 font-mono">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $b->name }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-teal-800">{{ $b->code }}</td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800">{{ $b->expenses_count }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Active</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. Expense Heads Panel -->
        <div id="panel-heads" class="p-6 space-y-6 hidden">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-[11px] text-slate-400">Register a new expense head instantly, or bulk import from Excel below.</p>
                <button type="button" onclick="openMasterModal('heads')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-yamaha-600 hover:bg-yamaha-500 text-white text-xs font-bold rounded-xl shadow-md shadow-yamaha-600/30 transition">
                    <i class="fa-solid fa-plus"></i> Add Expense Head
                </button>
            </div>

            <!-- Bulk Upload: Expense Heads -->
            <div class="p-4 rounded-xl bg-yamaha-50/60 border border-dashed border-yamaha-200">
                <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-1">
                            <i class="fa-solid fa-file-import text-yamaha-600 mr-1"></i> Bulk Upload Expense Heads (Excel / CSV)
                        </h4>
                        <p class="text-[11px] text-slate-500">Columns: <span class="font-mono">name*, code*, is_transport_related (yes/no), status</span> — first row = header. Existing names/codes are skipped automatically.</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('master.template', 'expense-heads') }}" class="px-3 py-2 bg-white border border-yamaha-200 text-yamaha-700 text-xs font-bold rounded-xl hover:bg-yamaha-100 transition">
                            <i class="fa-solid fa-download mr-1"></i> Template (.xlsx)
                        </a>
                        <form action="{{ route('master.bulk-import', 'expense-heads') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <label for="bulkFile-heads" class="cursor-pointer px-3 py-2 bg-white border border-slate-300 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-50 transition max-w-[180px] truncate">
                                <i class="fa-solid fa-folder-open mr-1"></i> <span id="bulkFileName-heads">Choose File</span>
                            </label>
                            <input type="file" id="bulkFile-heads" name="file" accept=".csv,.xlsx,.xls" class="hidden" onchange="updateBulkFileName(this)">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-yamaha-600 to-cyan-500 hover:from-yamaha-500 hover:to-cyan-400 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                <i class="fa-solid fa-upload mr-1"></i> Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Expense Head Title</th>
                            <th class="py-3 px-4">Code</th>
                            <th class="py-3 px-4 text-center">Transport Related</th>
                            <th class="py-3 px-4 text-center">Expenses Count</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($expenseHeads as $index => $h)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 text-slate-400 font-mono">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $h->name }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-indigo-800">{{ $h->code }}</td>
                            <td class="py-3 px-4 text-center">
                                @if($h->is_transport_related)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Yes (Logistics)</span>
                                @else
                                    <span class="text-slate-400 text-[10px]">No</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800">{{ $h->expenses_count }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Active</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Users & RBAC Panel -->
        <div id="panel-users" class="p-6 space-y-6 hidden">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-[11px] text-slate-400">Provision a depot operator account (Employee ID &amp; depot assignment required).</p>
                <button type="button" onclick="openMasterModal('users')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-yamaha-600 hover:bg-yamaha-500 text-white text-xs font-bold rounded-xl shadow-md shadow-yamaha-600/30 transition">
                    <i class="fa-solid fa-user-plus"></i> Add Depot Operator
                </button>
            </div>

            <!-- Bulk Upload: Users -->
            <div class="p-4 rounded-xl bg-yamaha-50/60 border border-dashed border-yamaha-200">
                <div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:justify-between">
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-1">
                            <i class="fa-solid fa-file-import text-yamaha-600 mr-1"></i> Bulk Upload Depot Operators (Excel / CSV)
                        </h4>
                        <p class="text-[11px] text-slate-500">Columns: <span class="font-mono">name*, employee_id*, email*, password*, depot_code*</span> — every uploaded user is created as a <strong>Depot Operator</strong>. Showroom depot operators can log in using either their Corporate Email or Employee ID.</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('master.template', 'users') }}" class="px-3 py-2 bg-white border border-yamaha-200 text-yamaha-700 text-xs font-bold rounded-xl hover:bg-yamaha-100 transition">
                            <i class="fa-solid fa-download mr-1"></i> Template (.xlsx)
                        </a>
                        <form action="{{ route('master.bulk-import', 'users') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <label for="bulkFile-users" class="cursor-pointer px-3 py-2 bg-white border border-slate-300 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-50 transition max-w-[180px] truncate">
                                <i class="fa-solid fa-folder-open mr-1"></i> <span id="bulkFileName-users">Choose File</span>
                            </label>
                            <input type="file" id="bulkFile-users" name="file" accept=".csv,.xlsx,.xls" class="hidden" onchange="updateBulkFileName(this)">
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-yamaha-600 to-cyan-500 hover:from-yamaha-500 hover:to-cyan-400 text-white text-xs font-bold rounded-xl shadow-sm transition">
                                <i class="fa-solid fa-upload mr-1"></i> Upload
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Name</th>
                            <th class="py-3 px-4">Employee ID</th>
                            <th class="py-3 px-4">Corporate Email</th>
                            <th class="py-3 px-4">Assigned Role</th>
                            <th class="py-3 px-4">Assigned Depot / Showroom</th>
                            <th class="py-3 px-4">Raw Export Privilege</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($users as $index => $u)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 text-slate-400 font-mono">{{ $index + 1 }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $u->name }}</td>
                            <td class="py-3 px-4 font-mono font-semibold text-indigo-700">
                                @if($u->employee_id)
                                    <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-200">{{ $u->employee_id }}</span>
                                @else
                                    <span class="text-slate-400">&mdash;</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 font-medium">{{ $u->email }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $u->isHeadOffice() ? 'bg-indigo-100 text-indigo-800' : ($u->isDepotOperator() ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $u->roles->pluck('name')->first() ?? $u->role }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-700">
                                {{ $u->depot?->name ?? 'All Depots (HQ)' }}
                            </td>
                            <td class="py-3 px-4">
                                @if($u->can('export-raw-data') || $u->isHeadOffice())
                                    <span class="text-emerald-700 font-bold"><i class="fa-solid fa-circle-check mr-1"></i> Authorized</span>
                                @else
                                    <span class="text-rose-500 font-medium"><i class="fa-solid fa-ban mr-1"></i> Restricted (403)</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. Missing Entry Logs Panel -->
        <div id="panel-missing" class="p-6 space-y-6 hidden">
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs">
                <h4 class="font-bold flex items-center">
                    <i class="fa-solid fa-triangle-exclamation mr-2 text-rose-600"></i> Automated Daily Cut-off Audit Engine
                </h4>
                <p class="mt-1 text-rose-800/90 leading-relaxed">
                    Triggered automatically every evening at 20:00 (8:00 PM). Depots with zero recorded disbursements for the day receive automated email warnings dispatched to the depot in-charge and the Head Office Monitoring Desk.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase tracking-wider font-semibold border-b border-slate-100">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Non-Compliant Depot</th>
                            <th class="py-3 px-4">Dispatched Notification Recipients</th>
                            <th class="py-3 px-4">Audit Trigger Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($missingLogs as $log)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-bold text-slate-900 whitespace-nowrap">{{ $log->date->format('d M Y') }}</td>
                            <td class="py-3 px-4 font-bold text-rose-700 whitespace-nowrap">{{ $log->depot?->name }} ({{ $log->depot?->code }})</td>
                            <td class="py-3 px-4 text-slate-500 font-mono text-[11px]">{{ $log->recipients }}</td>
                            <td class="py-3 px-4 text-slate-400 whitespace-nowrap">{{ $log->sent_at ? $log->sent_at->format('Y-m-d H:i:s') : 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">No missing entry alerts recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ===== Data Entry Modals (opened by the Add buttons in each tab) ===== -->

<!-- Modal: Add Depot -->
<div id="masterModal-depots" class="fixed inset-0 z-50 {{ old('form') === 'depots' ? 'flex' : 'hidden' }} items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeMasterModal('depots')"></div>
    <div class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden">
        <div class="bg-gradient-to-r from-yamaha-600 to-cyan-500 h-1"></div>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-yamaha-600 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-yamaha-600/30">
                    <i class="fa-solid fa-warehouse text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Add New Depot</h3>
                    <p class="text-[10px] text-slate-400">Warehouse / showroom location</p>
                </div>
            </div>
            <button type="button" onclick="closeMasterModal('depots')" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('master.depots.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="form" value="depots">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Depot Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Depot Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="e.g. BOGRA-01" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('code')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">In-Charge Email</label>
                <input type="email" name="contact_email" value="{{ old('contact_email') }}" placeholder="optional" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                @error('contact_email')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeMasterModal('depots')" class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-yamaha-600 hover:bg-yamaha-500 text-white text-xs font-bold rounded-xl shadow-md shadow-yamaha-600/30 transition"><i class="fa-solid fa-plus mr-1"></i> Register Depot</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Business Unit -->
<div id="masterModal-businesses" class="fixed inset-0 z-50 {{ old('form') === 'businesses' ? 'flex' : 'hidden' }} items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeMasterModal('businesses')"></div>
    <div class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden">
        <div class="bg-gradient-to-r from-yamaha-600 to-cyan-500 h-1"></div>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-yamaha-600 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-yamaha-600/30">
                    <i class="fa-solid fa-cubes text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Add New Business Unit</h3>
                    <p class="text-[10px] text-slate-400">Business category / product line</p>
                </div>
            </div>
            <button type="button" onclick="closeMasterModal('businesses')" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('master.businesses.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="form" value="businesses">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Business Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Heavy Excavator" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="e.g. BU-HE" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('code')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeMasterModal('businesses')" class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-yamaha-600 hover:bg-yamaha-500 text-white text-xs font-bold rounded-xl shadow-md shadow-yamaha-600/30 transition"><i class="fa-solid fa-plus mr-1"></i> Register Business Unit</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Expense Head -->
<div id="masterModal-heads" class="fixed inset-0 z-50 {{ old('form') === 'heads' ? 'flex' : 'hidden' }} items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeMasterModal('heads')"></div>
    <div class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden">
        <div class="bg-gradient-to-r from-yamaha-600 to-cyan-500 h-1"></div>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-yamaha-600 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-yamaha-600/30">
                    <i class="fa-solid fa-list-check text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Add New Expense Head</h3>
                    <p class="text-[10px] text-slate-400">Expense category head</p>
                </div>
            </div>
            <button type="button" onclick="closeMasterModal('heads')" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('master.expense-heads.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="form" value="heads">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Head Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Forklift Maintenance" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Code <span class="text-rose-500">*</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" placeholder="e.g. EH-040" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('code')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
            </div>
            <label class="flex items-center gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer">
                <input type="checkbox" name="is_transport_related" value="1" {{ old('is_transport_related') ? 'checked' : '' }} class="w-4 h-4 rounded text-yamaha-600 focus:ring-yamaha-500">
                <span class="text-xs font-semibold text-slate-700">Transport / Carriage Related <span class="text-slate-400 font-normal">(logistics categorisation)</span></span>
            </label>
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeMasterModal('heads')" class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-yamaha-600 hover:bg-yamaha-500 text-white text-xs font-bold rounded-xl shadow-md shadow-yamaha-600/30 transition"><i class="fa-solid fa-plus mr-1"></i> Register Expense Head</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Depot Operator (User & RBAC) -->
<div id="masterModal-users" class="fixed inset-0 z-50 {{ old('form') === 'users' ? 'flex' : 'hidden' }} items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeMasterModal('users')"></div>
    <div class="relative bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200/80 overflow-hidden">
        <div class="bg-gradient-to-r from-yamaha-600 to-cyan-500 h-1"></div>
        <div class="flex items-center justify-between p-5 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-yamaha-600 to-cyan-500 text-white flex items-center justify-center shadow-md shadow-yamaha-600/30">
                    <i class="fa-solid fa-user-plus text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900">Add Depot Operator</h3>
                    <p class="text-[10px] text-slate-400">Employee ID &amp; depot assignment required</p>
                </div>
            </div>
            <button type="button" onclick="closeMasterModal('users')" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('master.users.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="form" value="users">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Full Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('name')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Employee ID <span class="text-rose-500">*</span></label>
                    <input type="text" name="employee_id" value="{{ old('employee_id') }}" placeholder="e.g. EMP-105" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono uppercase focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('employee_id')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('email')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Password <span class="text-rose-500">*</span></label>
                    <input type="text" name="password" value="{{ old('password', '123456') }}" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    @error('password')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Assign Depot / Showroom <span class="text-rose-500">*</span></label>
                <select name="depot_id" required class="w-full text-xs font-semibold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <option value="">Select Depot / Showroom *</option>
                    @foreach($depots->where('status', true) as $depotOption)
                    <option value="{{ $depotOption->id }}" {{ old('depot_id') == $depotOption->id ? 'selected' : '' }}>{{ $depotOption->name }} ({{ $depotOption->code }})</option>
                    @endforeach
                </select>
                @error('depot_id')<p class="text-[10px] text-rose-500 mt-1 font-semibold">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeMasterModal('users')" class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-bold rounded-xl hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-yamaha-600 hover:bg-yamaha-500 text-white text-xs font-bold rounded-xl shadow-md shadow-yamaha-600/30 transition"><i class="fa-solid fa-user-plus mr-1"></i> Add Depot Operator</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchTab(tabName) {
        const panels = ['depots', 'businesses', 'heads', 'users', 'missing'];
        panels.forEach(p => {
            document.getElementById('panel-' + p).classList.add('hidden');
            const btn = document.getElementById('tabBtn-' + p);
            btn.classList.remove('border-emerald-600', 'text-emerald-700');
            btn.classList.add('border-transparent', 'text-slate-500');
        });

        document.getElementById('panel-' + tabName).classList.remove('hidden');
        const activeBtn = document.getElementById('tabBtn-' + tabName);
        activeBtn.classList.add('border-emerald-600', 'text-emerald-700');
        activeBtn.classList.remove('border-transparent', 'text-slate-500');
    }

    function updateBulkFileName(input) {
        const label = document.getElementById('bulkFileName-' + input.id.split('-')[1]);
        if (label) {
            label.textContent = input.files.length ? input.files[0].name : 'Choose File';
        }
    }

    function openMasterModal(key) {
        const modal = document.getElementById('masterModal-' + key);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
        const first = modal.querySelector('input:not([type="hidden"]):not([type="checkbox"]), select');
        if (first) {
            setTimeout(() => first.focus(), 60);
        }
    }

    function closeMasterModal(key) {
        const modal = document.getElementById('masterModal-' + key);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('[id^="masterModal-"]').forEach(function (modal) {
            if (!modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
        document.body.style.overflow = '';
    });

    // After a failed submit the modal re-opens (old form key) — make sure its
    // tab is the visible one too.
    document.addEventListener('DOMContentLoaded', function () {
        const reopen = @json(old('form'));
        if (reopen && document.getElementById('panel-' + reopen) && reopen !== 'missing') {
            switchTab(reopen);
        }
    });
</script>
@endsection
