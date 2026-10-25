<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ACI Expense Management') - Distribution & Showroom</title>

    <!-- Google Fonts & Tailwind CDN & FontAwesome & ApexCharts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        // Yamaha-inspired racing palette
                        yamaha: {
                            50: '#EEF4FF', 100: '#DCE8FD', 200: '#B9D1FB', 300: '#86B4F8',
                            400: '#4D8DF4', 500: '#2266EF', 600: '#1149B0', 700: '#0E3A8C',
                            800: '#0A2A6B', 900: '#061A40', 950: '#041233',
                        },
                        // Legacy green/teal accents are remapped to the Yamaha
                        // blue / speed-cyan identity so every view re-themes at once.
                        emerald: {
                            50: '#EEF4FF', 100: '#DCE8FD', 200: '#B9D1FB', 300: '#86B4F8',
                            400: '#4D8DF4', 500: '#2266EF', 600: '#1149B0', 700: '#0E3A8C',
                            800: '#0A2A6B', 900: '#061A40',
                        },
                        teal: {
                            50: '#ECFAFF', 100: '#CFF3FE', 200: '#A5E9FD', 300: '#6BDCF9',
                            400: '#22C9F5', 500: '#00B4E6', 600: '#0093C7', 700: '#0077A5',
                            800: '#056287', 900: '#0A526F',
                        },
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(1200px 600px at 85% -10%, #DCE8FD 0%, transparent 55%),
                        radial-gradient(900px 500px at -10% 110%, #ECFAFF 0%, transparent 50%),
                        #F4F7FC;
            background-attachment: fixed;
        }
        .gauge-meter svg {
            overflow: visible !important;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(185, 209, 251, 0.6);
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(134, 180, 248, 0.35);
            border-radius: 9999px;
        }
        .nav-link {
            position: relative;
        }
        .nav-link-active {
            box-shadow: 0 10px 24px -8px rgba(17, 73, 176, 0.65);
        }
        .nav-link-active::before {
            content: '';
            position: absolute;
            left: -14px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            border-radius: 9999px;
            background: linear-gradient(180deg, #22C9F5, #1149B0);
        }
        /* ===== Tom Select: searchable dropdowns (Yamaha-blue theme) =====
           Sized to match native text-xs / px-3 py-2 inputs exactly. */
        .ts-wrapper { width: 100%; }
        .ts-wrapper.single .ts-control,
        .ts-wrapper.single.input-active .ts-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #f8fafc;
            padding: 0.5rem 1.9rem 0.5rem 0.75rem;
            font-size: 12px;
            font-weight: 500;
            color: #334155;
            min-height: 0;
            line-height: 1.35;
            box-shadow: none;
        }
        .ts-wrapper.single .ts-control:after {
            border-width: 5px 5px 0 5px;
            border-color: #94a3b8 transparent transparent transparent;
            margin-top: -2px;
        }
        .ts-wrapper.single.input-active .ts-control { background: #ffffff; }
        .ts-wrapper.focus .ts-control {
            border-color: #2266EF;
            box-shadow: 0 0 0 2px rgba(34, 102, 239, 0.2);
        }
        .ts-wrapper .ts-control input { font-size: 12px; font-weight: 500; color: #334155; }
        /* Multi-select controls (filter chips with × remove buttons): same
           look as singles, but capped so a fully-selected list stays compact. */
        .ts-wrapper.multi .ts-control {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #f8fafc;
            padding: 0.375rem 0.625rem;
            font-size: 12px;
            font-weight: 500;
            color: #334155;
            min-height: 34px;
            max-height: 92px;
            overflow-y: auto;
        }
        .ts-wrapper.multi .ts-control > .item {
            background: #1149B0;
            color: #ffffff;
            border-radius: 0.5rem;
            padding: 0.125rem 0.5rem;
            margin: 2px;
            font-size: 11px;
            font-weight: 600;
        }
        .ts-wrapper.multi .ts-control > .item .remove {
            border-left: 1px solid rgba(255, 255, 255, 0.35);
            padding-left: 0.4rem;
            margin-left: 0.4rem;
            border-radius: 0;
        }
        .ts-wrapper.multi .ts-control > .item.active {
            background: #0f3d94;
        }
        .ts-wrapper.single .ts-control > .item {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        .ts-dropdown {
            border: 1px solid #dbe8fd;
            border-radius: 0.75rem;
            box-shadow: 0 14px 30px -10px rgba(6, 26, 64, 0.35);
            margin-top: 4px;
            font-size: 12px;
            font-weight: 500;
            overflow: hidden;
        }
        .ts-dropdown .ts-dropdown-content { padding: 5px; }
        .ts-dropdown .option { padding: 8px 12px; color: #334155; border-radius: 0.55rem; }
        .ts-dropdown .option.active { background: #EEF4FF; color: #0E3A8C; }
        .ts-dropdown .option.selected { background: #1149B0; color: #ffffff; }
        .ts-dropdown .create:hover { background: #EEF4FF; color: #0E3A8C; }
        /* Calendar-style grid panel for month / year pickers.
           Fixed comfortable width regardless of the small trigger control. */
        .ts-dropdown.ts-grid {
            min-width: 268px;
            background: #ffffff;
            padding: 4px;
        }
        .ts-dropdown.ts-grid .ts-dropdown-content {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 6px;
            padding: 4px;
        }
        .ts-dropdown.ts-grid .option {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 38px;
            text-align: center;
            font-weight: 700;
            letter-spacing: 0.03em;
            color: #334155;
            background: #ffffff;
            border: 1px solid #dbe3ee;
            border-radius: 0.6rem;
            transition: all 0.12s ease;
            /* Tom Select dims options lacking data-selectable to 0.5 opacity —
               custom calendar tiles must stay fully opaque. */
            opacity: 1;
        }
        .ts-dropdown.ts-grid .option.active {
            background: #eef4ff;
            border-color: #86b4f8;
            color: #0e3a8c;
            transform: translateY(-1px);
        }
        .ts-dropdown.ts-grid .option.selected {
            background: linear-gradient(135deg, #1149b0, #0093c7);
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 6px 14px -6px rgba(17, 73, 176, 0.6);
        }
        /* Full-width rows for the "no filter" tile (empty value) and the
           whole-period tile ("ALL" = Entire Year), so their long labels
           never get crushed into a narrow grid tile. */
        .ts-dropdown.ts-grid .option[data-value=""],
        .ts-dropdown.ts-grid .option[data-value="ALL"] {
            grid-column: 1 / -1;
            height: 34px;
            background: #ffffff;
            border-style: dashed;
            color: #64748b;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ts-dropdown.ts-grid .option[data-value="ALL"].selected,
        .ts-dropdown.ts-grid .option[data-value=""].selected {
            background: linear-gradient(135deg, #1149b0, #0093c7);
            border-style: solid;
            border-color: transparent;
            color: #ffffff;
        }
        /* Navigable year picker header (‹ range ›) inside grid dropdowns */
        .ts-year-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 2px 4px 8px 4px;
            border-bottom: 1px solid #eef2f7;
            margin-bottom: 6px;
        }
        .ts-year-range {
            font-size: 12px;
            font-weight: 800;
            color: #0e3a8c;
            letter-spacing: 0.04em;
        }
        .ts-year-nav {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #1149b0;
            border-radius: 0.55rem;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.12s ease;
        }
        .ts-year-nav:hover {
            background: #eef4ff;
            border-color: #b9d1fb;
            transform: translateY(-1px);
        }
        /* Flip alignment when the panel would overflow the viewport's right edge */
        .ts-dropdown.ts-dropdown-right { left: auto; right: 0; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen text-slate-800">

<div class="flex min-h-screen">

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebarOverlay" class="fixed inset-0 z-30 bg-yamaha-950/70 backdrop-blur-sm hidden lg:hidden"></div>

    <!-- ===== Left Sidebar (Yamaha racing navy) ===== -->
    <aside id="appSidebar"
           class="fixed lg:sticky top-0 left-0 z-40 h-screen w-72 shrink-0 flex flex-col
                  -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out
                  bg-gradient-to-b from-yamaha-900 via-yamaha-950 to-[#020C24] text-white shadow-2xl shadow-yamaha-950/40">

        <!-- Glow accents -->
        <div class="pointer-events-none absolute -top-24 -right-24 w-64 h-64 rounded-full bg-yamaha-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute bottom-10 -left-20 w-56 h-56 rounded-full bg-cyan-400/10 blur-3xl"></div>

        <!-- Brand: tuning-fork inspired emblem -->
        <div class="relative px-5 pt-6 pb-5 border-b border-white/10">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-yamaha-600 to-cyan-400 flex items-center justify-center text-white shadow-lg shadow-yamaha-500/40 group-hover:scale-105 transition-transform">
                    <svg viewBox="0 0 64 64" fill="none" class="w-7 h-7 text-white" aria-hidden="true">
                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="3.4"/>
                        <g stroke="currentColor" stroke-width="3.2" stroke-linecap="round" fill="none">
                            <g id="yfork">
                                <path d="M32 31 V17"/>
                                <path d="M26.5 17 V9.5 A5.5 5.5 0 0 1 37.5 9.5 V17"/>
                            </g>
                        </g>
                        <use href="#yfork" transform="rotate(120 32 32)" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" fill="none"/>
                        <use href="#yfork" transform="rotate(240 32 32)" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" fill="none"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <span class="text-lg font-extrabold tracking-tight text-white block leading-tight">ACI MOTORS</span>
                    <span class="text-[10px] font-semibold text-cyan-300 uppercase tracking-[0.22em] block">Distribution Expense</span>
                </div>
            </a>
        </div>

        <!-- Quick Entry Action -->
        @can('create-expense')
        <div class="relative px-4 pt-4">
            <a href="{{ route('expenses.create') }}" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-white rounded-xl text-xs font-bold shadow-lg shadow-amber-500/40 transition">
                <i class="fa-solid fa-plus-circle"></i> New Daily Expense Entry
            </a>
        </div>
        @endcan

        <!-- Navigation -->
        <nav class="relative flex-1 overflow-y-auto sidebar-scroll px-4 py-5 space-y-6">

            <div>
                <p class="px-3 mb-2 text-[10px] font-bold text-yamaha-300/70 uppercase tracking-[0.18em]">Operations</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('dashboard') ? 'nav-link-active bg-gradient-to-r from-yamaha-600/90 to-yamaha-500/70 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 hover:translate-x-0.5' }}">
                        <i class="fa-solid fa-gauge-high w-5 text-center text-xs"></i>
                        <span>Analytics Dashboard</span>
                    </a>
                    @if(!auth()->user()->isDepotOperator())
                    <a href="{{ route('expenses.index') }}"
                       class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('expenses.index') ? 'nav-link-active bg-gradient-to-r from-yamaha-600/90 to-yamaha-500/70 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 hover:translate-x-0.5' }}">
                        <i class="fa-solid fa-receipt w-5 text-center text-xs"></i>
                        <span>Expense Logs</span>
                    </a>
                    @else
                    <a href="{{ route('my-depot.expenses') }}"
                       class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('my-depot.*') ? 'nav-link-active bg-gradient-to-r from-yamaha-600/90 to-yamaha-500/70 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 hover:translate-x-0.5' }}">
                        <i class="fa-solid fa-warehouse w-5 text-center text-xs"></i>
                        <span>My Depot Expenses</span>
                    </a>
                    @endif
                    <a href="{{ route('budgets.index') }}"
                       class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('budgets.*') ? 'nav-link-active bg-gradient-to-r from-yamaha-600/90 to-yamaha-500/70 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 hover:translate-x-0.5' }}">
                        <i class="fa-solid fa-scale-balanced w-5 text-center text-xs"></i>
                        <span>Budget &amp; Variance</span>
                    </a>
                </div>
            </div>

            @can('manage-master-data')
            <div>
                <p class="px-3 mb-2 text-[10px] font-bold text-yamaha-300/70 uppercase tracking-[0.18em]">Administration</p>
                <div class="space-y-1">
                    <a href="{{ route('master.index') }}"
                       class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition {{ request()->routeIs('master.*') ? 'nav-link-active bg-gradient-to-r from-yamaha-600/90 to-yamaha-500/70 text-white' : 'text-slate-400 hover:text-white hover:bg-white/5 hover:translate-x-0.5' }}">
                        <i class="fa-solid fa-sitemap w-5 text-center text-xs"></i>
                        <span>Master Data &amp; RBAC</span>
                    </a>
                </div>
            </div>
            @endcan
        </nav>

        <!-- Logged-in User Card & Logout -->
        <div class="relative border-t border-white/10 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 shrink-0 rounded-xl bg-gradient-to-tr from-yamaha-600 to-cyan-500 flex items-center justify-center font-black text-white shadow-lg shadow-yamaha-600/40 ring-2 ring-white/20">
                    {{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-100 truncate">{{ auth()->user()->name }}</p>
                    <span class="inline-flex items-center text-[10px] px-2 py-0.5 mt-1 rounded-full font-semibold border
                        {{ auth()->user()->isHeadOffice() ? 'bg-indigo-400/15 text-indigo-300 border-indigo-400/30' : (auth()->user()->isDepotOperator() ? 'bg-cyan-400/15 text-cyan-300 border-cyan-400/30' : 'bg-amber-400/15 text-amber-300 border-amber-400/30') }}">
                        {{ auth()->user()->roles->pluck('name')->first() ?? auth()->user()->role }}
                        @if(auth()->user()->depot) &middot; {{ auth()->user()->depot->name }} @endif
                    </span>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout" class="p-2 text-slate-500 hover:text-rose-400 hover:bg-white/5 rounded-lg transition">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- ===== Main Column ===== -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Top Bar -->
        <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-xl border-b border-yamaha-100 shadow-sm shadow-yamaha-100/60">
            <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" id="sidebarToggle" class="lg:hidden p-2 -ml-2 text-slate-500 hover:text-yamaha-700 hover:bg-yamaha-50 rounded-lg transition">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <div class="min-w-0">
                        <h1 class="text-sm font-extrabold text-yamaha-900 tracking-tight truncate">@yield('page_title', 'Executive Analytics Dashboard')</h1>
                        <p class="text-[11px] text-slate-400 truncate">@yield('page_subtitle', 'Distribution &amp; Showroom Expense Management System')</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <span class="hidden sm:inline-flex items-center gap-2 text-[11px] font-semibold text-yamaha-700 bg-yamaha-50 border border-yamaha-100 rounded-full px-3 py-1.5">
                        <i class="fa-regular fa-clock text-cyan-500"></i> {{ now()->format('D, d M Y') }}
                    </span>
                    <span class="inline-flex items-center gap-2 text-[11px] font-semibold px-3 py-1.5 rounded-full border
                        {{ auth()->user()->isHeadOffice() ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : (auth()->user()->isDepotOperator() ? 'bg-cyan-50 text-cyan-700 border-cyan-200' : 'bg-amber-50 text-amber-700 border-amber-200') }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                        {{ auth()->user()->roles->pluck('name')->first() ?? auth()->user()->role }}
                    </span>
                </div>
            </div>
        </header>

        <!-- Global Toast / Alerts -->
        <div class="w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            @if(session('success'))
            <div class="flex items-center p-4 mb-4 text-sm text-yamaha-800 rounded-xl bg-yamaha-50 border border-yamaha-200 shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check text-yamaha-600 text-lg mr-3"></i>
                <div>
                    <span class="font-bold">Success!</span> {{ session('success') }}
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="flex items-center p-4 mb-4 text-sm text-rose-800 rounded-xl bg-rose-50 border border-rose-200 shadow-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-lg mr-3"></i>
                <div>
                    <span class="font-bold">Error!</span> {{ session('error') }}
                </div>
            </div>
            @endif

            @if($errors->any())
            <div class="p-4 mb-4 text-sm text-rose-800 rounded-xl bg-rose-50 border border-rose-200 shadow-sm">
                <div class="flex items-center mb-2">
                    <i class="fa-solid fa-circle-xmark text-rose-600 text-lg mr-2"></i>
                    <span class="font-bold">Please resolve the following submission errors:</span>
                </div>
                <ul class="list-disc list-inside ml-5 space-y-1 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        <!-- Main Content -->
        <main class="flex-grow w-full max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="mt-auto bg-white/70 backdrop-blur border-t border-yamaha-100 py-4">
            <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} ACI Motors Ltd. Distribution &amp; Showroom Expense Management System.</p>
                <p class="font-medium text-yamaha-400">Strict Backdate Protection &bull; Multi-Tier RBAC &bull; Automated Cut-off Alerts</p>
            </div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>
<script>
    // Sidebar toggle
    (function () {
        const sidebar = document.getElementById('appSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggle = document.getElementById('sidebarToggle');

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }

        if (toggle) {
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
            });
        }
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
    })();

    // Searchable select boxes: type to filter, click or Enter to pick.
    // Applies to every <select> in the app; the native select stays in sync
    // and keeps firing native "change" events, so existing AJAX filters,
    // conditional fields, and form submissions continue to work unchanged.

    // Export any table to a real .xlsx file. Options:
    //   strip: selector list removed from the clone (helper cells, letters…)
    // Exports exactly what is on screen — hidden cells, recomputed totals,
    // and filters all carry over.
    window.exportTableToExcel = function (tableId, fileName, opts) {
        opts = opts || {};
        const src = document.getElementById(tableId);
        if (!src) return;
        if (typeof XLSX === 'undefined') {
            console.error('SheetJS (XLSX) is not loaded — cannot export.');
            return;
        }

        const clone = src.cloneNode(true);

        // Drop helper cells / deselected columns
        if (opts.strip) {
            clone.querySelectorAll(opts.strip).forEach(el => el.remove());
        }
        // Anything hidden on screen is not part of the export
        clone.querySelectorAll('[style*="display:none"], [style*="display: none"]').forEach(el => el.remove());
        // Interactive controls become plain text
        clone.querySelectorAll('input, select, button').forEach(el => el.remove());
        // Empty placeholder rows (e.g. the letter strip after stripping)
        clone.querySelectorAll('tr').forEach(tr => {
            if (!tr.querySelector('td, th')) tr.remove();
        });

        const wb = XLSX.utils.table_to_book(clone, { sheet: 'Sheet1' });
        XLSX.writeFile(wb, (fileName || 'export') + '.xlsx');
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof TomSelect === 'undefined') {
            return;
        }

        document.querySelectorAll('select').forEach(function (el) {
            if (el.dataset.tsSkip === '1' || el.tomselect) {
                return;
            }

            const emptyOption = el.querySelector('option[value=""]');
            const isGrid = el.dataset.tsGrid === '1';
            // Capture the select's own utility classes so we can remove exactly
            // these from Tom Select's wrapper afterwards — they stack with the
            // control's own padding and make boxes look oversized. Tom Select's
            // own state classes (single, full, focus…) must be preserved.
            const originalClasses = Array.from(el.classList);

            const tsConfig = {
                allowEmptyOption: true,
                maxOptions: 500,
                sortField: [{ field: '$order' }],
                placeholder: emptyOption ? emptyOption.textContent.trim() : 'Type to search & select…',
                // Multi-selects (depot/expense-head filters) get an × on each
                // selected chip so columns/rows can be unselected in place.
                plugins: el.multiple ? ['remove_button'] : [],
                render: {
                    no_results: function () {
                        return '<div class="no-results px-3 py-2 text-slate-400">No match found — keep typing…</div>';
                    },
                },
            };
            // Month / year grids search nothing — drop the control input. Only
            // set the key for grids: an explicit `undefined` would override the
            // library default via object merge, no input would be created, and
            // the control (e.g. Matrix Controls multi-selects) would render
            // empty with no placeholder.
            if (isGrid) {
                tsConfig.controlInput = null;
            }

            const ts = new TomSelect(el, tsConfig);

            // Year grids get a calendar-style pager: ‹ range › with 12 tiles per
            // page, so ANY year can be selected even when the <select> only
            // ships a handful of options. Detected by all-numeric option values.
            const optionValues = Array.from(el.options)
                .map(o => o.value)
                .filter(v => v !== '');
            const isYearGrid = isGrid && optionValues.length > 0 && optionValues.every(v => /^\d{4}$/.test(v) && +v > 1900);

            if (isYearGrid) {
                const picker = { start: null };

                const ensureYearOption = (year) => {
                    const val = String(year);
                    if (!el.querySelector('option[value="' + val + '"]')) {
                        const opt = document.createElement('option');
                        opt.value = val;
                        opt.textContent = val;
                        el.appendChild(opt);
                    }
                    if (!ts.options[val]) {
                        ts.addOption({ value: val, text: val });
                    }
                };

                const renderYearPicker = () => {
                    const dd = ts.dropdown;
                    const selectedYear = parseInt(ts.getValue(), 10) || new Date().getFullYear();
                    if (picker.start === null) {
                        picker.start = selectedYear - 2;
                    }

                    const head = document.createElement('div');
                    head.className = 'ts-year-head';
                    head.innerHTML =
                        '<button type="button" class="ts-year-nav" data-dir="-12" tabindex="-1">&lsaquo;</button>' +
                        '<span class="ts-year-range">' + picker.start + ' &ndash; ' + (picker.start + 11) + '</span>' +
                        '<button type="button" class="ts-year-nav" data-dir="12" tabindex="-1">&rsaquo;</button>';

                    const grid = document.createElement('div');
                    grid.className = 'ts-dropdown-content';
                    for (let y = picker.start; y < picker.start + 12; y++) {
                        const tile = document.createElement('div');
                        tile.className = 'option' + (y === selectedYear ? ' selected' : '');
                        tile.textContent = y;
                        tile.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            ensureYearOption(y);
                            ts.setValue(String(y));
                            ts.close();
                        });
                        grid.appendChild(tile);
                    }

                    dd.innerHTML = '';
                    dd.appendChild(head);
                    dd.appendChild(grid);

                    head.querySelectorAll('.ts-year-nav').forEach(btn => {
                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            e.stopPropagation();
                            picker.start += parseInt(this.dataset.dir, 10);
                            renderYearPicker();
                        });
                    });
                };

                ts.on('dropdown_open', function () {
                    // Tom Select populates its option list while opening; redraw
                    // after that so the calendar pager wins the race.
                    requestAnimationFrame(renderYearPicker);
                });
            }

            // Remove only the utility classes that were copied from the <select>;
            // sizing comes from the theme CSS so selects match plain inputs.
            // Width utilities (w-*) are kept so flex-row layouts can size the
            // wrapper explicitly — otherwise it shrinks to its text content.
            originalClasses.forEach(c => {
                if (!/^w-/.test(c)) {
                    ts.wrapper.classList.remove(c);
                }
            });

            // Month / year fields use a calendar-style grid dropdown instead of
            // a typeable list (no search input).
            if (isGrid) {
                ts.dropdown.classList.add('ts-grid');
            }

            // The grid panel is wider than its small trigger — if opening it
            // would overflow the right edge of the viewport, right-align it.
            ts.on('dropdown_open', function () {
                const dd = ts.dropdown;
                dd.classList.remove('ts-dropdown-right');
                requestAnimationFrame(function () {
                    const rect = dd.getBoundingClientRect();
                    if (rect.right > window.innerWidth - 8) {
                        dd.classList.add('ts-dropdown-right');
                    }
                });
            });
        });
    });
</script>

@stack('scripts')
</body>
</html>
