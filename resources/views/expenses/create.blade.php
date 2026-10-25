@extends('layouts.app')

@section('title', 'Log Daily Depot Expense')
@section("page_title", "New Daily Expense Entry")
@section("page_subtitle", "Current-date only entry form (backdate blocked)")

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Breadcrumbs -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-emerald-600 transition">Dashboard</a>
                @if(!auth()->user()->isDepotOperator())
                <span>/</span>
                <a href="{{ route('expenses.index') }}" class="hover:text-emerald-600 transition">Expense Logs</a>
                @endif
                <span>/</span>
                <span class="text-slate-800 font-semibold">New Daily Expense Entry</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Log Daily Depot Expense</h1>
            <p class="text-xs text-slate-500 mt-0.5">Strict Current-Date Financial Logging &bull; Zero Backdating Tolerated</p>
        </div>

        @if(!auth()->user()->isDepotOperator())
        <a href="{{ route('expenses.index') }}" class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Logs
        </a>
        @endif
    </div>

    <!-- Strict Backdating Notice Alert -->
    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200/80 text-amber-900 text-xs flex items-start space-x-3">
        <div class="w-8 h-8 rounded-xl bg-amber-200/60 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
            <i class="fa-solid fa-shield-halved text-sm"></i>
        </div>
        <div>
            <h4 class="font-bold text-amber-950">Strict Compliance Rule: No Backdated Entries Permitted</h4>
            <p class="text-amber-800/90 mt-0.5 leading-relaxed">
                As per corporate finance governance policy, expense logs must be recorded on the current operating date (<strong>{{ \Carbon\Carbon::parse($todayDate)->format('l, d F Y') }}</strong>). Any attempt to submit backdated expenditures will be blocked by system validation rules.
            </p>
        </div>
    </div>

    <!-- Form Container Card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="expenseEntryForm">
            @csrf

            <!-- Row 1: Date, Month & Year (Auto-synced) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 p-4 rounded-xl bg-slate-50 border border-slate-200">
                <!-- Date -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Expense Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="date" id="expenseDate" value="{{ old('date', $todayDate) }}"
                        min="{{ $todayDate }}" max="{{ $todayDate }}" required
                        class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                    <span class="block text-[10px] text-slate-400 mt-1 font-medium">Locked to current date: {{ $todayDate }}</span>
                </div>

                <!-- Auto Month -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Billing Month (Auto)
                    </label>
                    <input type="text" id="autoMonthDisplay" readonly value="{{ strtoupper(\Carbon\Carbon::parse($todayDate)->format('M')) }}"
                        class="w-full text-xs font-bold px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-700 cursor-not-allowed">
                    <span class="block text-[10px] text-slate-400 mt-1">Calculated from Date</span>
                </div>

                <!-- Auto Year -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Fiscal Year (Auto)
                    </label>
                    <input type="text" id="autoYearDisplay" readonly value="{{ \Carbon\Carbon::parse($todayDate)->year }}"
                        class="w-full text-xs font-bold px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-700 cursor-not-allowed">
                    <span class="block text-[10px] text-slate-400 mt-1">Calculated from Date</span>
                </div>
            </div>

            <!-- Row 2: Depot, Business Unit, Expense Head -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <!-- Depot -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Depot / Showroom <span class="text-rose-500">*</span>
                    </label>
                    @if(auth()->user()->isDepotOperator())
                        <input type="hidden" name="depot_id" value="{{ auth()->user()->depot_id }}">
                        <div class="px-3 py-2.5 rounded-xl border border-emerald-300 bg-emerald-50/70 text-emerald-900 text-xs font-bold flex items-center justify-between">
                            <span>{{ auth()->user()->depot?->name }}</span>
                            <span class="text-[10px] bg-emerald-200 text-emerald-800 px-2 py-0.5 rounded-full font-semibold">Assigned Depot</span>
                        </div>
                    @else
                        <select name="depot_id" id="depot_id" required class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            <option value="">-- Select Depot (21 Locations) --</option>
                            @foreach($depots as $depot)
                                <option value="{{ $depot->id }}" {{ old('depot_id') == $depot->id ? 'selected' : '' }}>
                                    {{ $depot->name }} ({{ $depot->code }})
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <!-- Business Unit -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Business Unit <span class="text-rose-500">*</span>
                    </label>
                    <select name="business_id" id="business_id" required class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">-- Select Business (27 Categories) --</option>
                        @foreach($businesses as $bu)
                            <option value="{{ $bu->id }}" {{ old('business_id') == $bu->id ? 'selected' : '' }}>
                                {{ $bu->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Expense Head -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Expense Head <span class="text-rose-500">*</span>
                    </label>
                    <select name="expense_head_id" id="expense_head_id" required class="w-full text-xs font-semibold px-3 py-2.5 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <option value="">-- Select Expense Head (39 Items) --</option>
                        @foreach($expenseHeads as $head)
                            <option value="{{ $head->id }}" {{ old('expense_head_id') == $head->id ? 'selected' : '' }}>
                                {{ $head->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Row 3: Amount -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Amount -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Amount (BDT) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold text-sm">৳</span>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount') }}" required placeholder="0.00"
                            class="w-full pl-8 pr-4 py-2.5 text-sm font-bold rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    </div>
                    <span class="block text-[10px] text-slate-400 mt-1">Minimum acceptable value: 0.01 BDT</span>
                </div>
            </div>

            <!-- Row 4: Remarks & Voucher Attachment -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Remarks -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Remarks / Purpose Details
                    </label>
                    <textarea name="remarks" id="remarks" rows="3" placeholder="e.g., Freezer Delivery, Cheque Clearing Charge, Generator Diesel, Emergency Spares Dispatch"
                        class="w-full text-xs px-3 py-2 rounded-xl border border-slate-300 bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none">{{ old('remarks') }}</textarea>
                </div>

                <!-- Voucher Attachment -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Voucher Attachment (Optional)
                    </label>
                    <div class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl p-4 text-center bg-slate-50/50 hover:bg-emerald-50/20 transition cursor-pointer relative">
                        <input type="file" name="voucher_attachment" id="voucher_attachment" accept=".jpg,.jpeg,.png,.pdf"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-slate-400 mb-1"></i>
                        <p class="text-xs font-semibold text-slate-700" id="fileUploadPrompt">Click to select receipt or drop file here</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">PDF or Image (JPG, PNG) up to 10 MB</p>
                    </div>
                </div>
            </div>

            <!-- Submission Button -->
            <div class="pt-5 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ auth()->user()->isDepotOperator() ? route('dashboard') : route('expenses.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-7 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5">
                    <i class="fa-solid fa-check-circle mr-1.5"></i> Register Daily Expense Entry
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const fileInput = document.getElementById('voucher_attachment');
        const uploadPrompt = document.getElementById('fileUploadPrompt');

        // Voucher file feedback
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                uploadPrompt.innerHTML = `<span class="text-emerald-700 font-bold"><i class="fa-solid fa-file-check mr-1"></i> ${this.files[0].name}</span>`;
            }
        });
    });
</script>
@endpush
