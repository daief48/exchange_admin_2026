<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ACI Distribution Expense Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        yamaha: { 50: '#EEF4FF', 100: '#DCE8FD', 200: '#B9D1FB', 300: '#86B4F8', 400: '#4D8DF4', 500: '#2266EF', 600: '#1149B0', 700: '#0E3A8C', 800: '#0A2A6B', 900: '#061A40', 950: '#041233' },
                        emerald: { 50: '#EEF4FF', 100: '#DCE8FD', 200: '#B9D1FB', 300: '#86B4F8', 400: '#4D8DF4', 500: '#2266EF', 600: '#1149B0', 700: '#0E3A8C', 800: '#0A2A6B', 900: '#061A40' },
                        teal: { 50: '#ECFAFF', 100: '#CFF3FE', 200: '#A5E9FD', 300: '#6BDCF9', 400: '#22C9F5', 500: '#00B4E6', 600: '#0093C7', 700: '#0077A5', 800: '#056287', 900: '#0A526F' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#061A40] via-[#041233] to-indigo-950 min-h-screen flex items-center justify-center p-4 font-['Plus_Jakarta_Sans',sans-serif]">

    <div class="max-w-md w-full">
        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-xl shadow-emerald-500/30 mb-4">
                <svg viewBox="0 0 64 64" fill="none" class="w-9 h-9 text-white" aria-hidden="true">
                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="3.4"/>
                    <g stroke="currentColor" stroke-width="3.2" stroke-linecap="round" fill="none">
                        <g id="yfork-login">
                            <path d="M32 31 V17"/>
                            <path d="M26.5 17 V9.5 A5.5 5.5 0 0 1 37.5 9.5 V17"/>
                        </g>
                    </g>
                    <use href="#yfork-login" transform="rotate(120 32 32)" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" fill="none"/>
                    <use href="#yfork-login" transform="rotate(240 32 32)" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" fill="none"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">ACI MOTORS LIMITED</h1>
            <p class="text-emerald-400 font-semibold text-sm tracking-wide mt-1">Distribution & Showroom Expense Management</p>
            <p class="text-slate-400 text-xs mt-1">Enterprise Budget, Daily Logistics & Variance System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/20 p-8">
            @if ($errors->any())
                <div class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-center">
                    <i class="fa-solid fa-circle-exclamation mr-2 text-rose-500 text-base"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Corporate Email or Employee ID</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-regular fa-id-badge"></i>
                        </span>
                        <input type="text" name="login" value="{{ old('login', 'admin@gmail.com') }}" required autofocus
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm font-medium text-slate-800 bg-slate-50 focus:bg-white transition"
                            placeholder="admin@gmail.com or EMP-101">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5 flex items-center">
                        <i class="fa-solid fa-circle-info text-emerald-600 mr-1.5"></i>
                        Showroom &amp; Depot operators can log in using either their <strong>Employee ID</strong> or <strong>Email</strong>.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Access Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" value="123456" required
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 text-sm font-medium text-slate-800 bg-slate-50 focus:bg-white transition"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <span class="ml-2">Remember this device</span>
                    </label>
                    <span class="text-emerald-700 font-semibold hover:underline cursor-pointer">Need access?</span>
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm rounded-xl shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5">
                    Sign In to Portal <i class="fa-solid fa-arrow-right ml-1.5"></i>
                </button>
            </form>

            <!-- 1-Click Fast Persona Switcher for Quick Demo (only rendered when demo mode is enabled) -->
            @if(app()->isLocal() || config('app.demo_switcher'))
            <div class="mt-8 pt-6 border-t border-slate-200">
                <p class="text-center text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">
                    Fast Demo 1-Click Login
                </p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    @if(isset($demoUsers['head_office']))
                    <form action="{{ route('switch.role') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $demoUsers['head_office']->id }}">
                        <button type="submit" class="w-full p-2.5 rounded-xl border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-900 font-semibold text-left transition flex flex-col">
                            <span class="text-[11px] font-bold">HO Finance Admin</span>
                            <span class="text-[10px] text-indigo-600 font-mono">admin@gmail.com (EMP-001)</span>
                        </button>
                    </form>
                    @endif

                    @if(isset($demoUsers['dhaka_operator']))
                    <form action="{{ route('switch.role') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $demoUsers['dhaka_operator']->id }}">
                        <button type="submit" class="w-full p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100 text-emerald-900 font-semibold text-left transition flex flex-col">
                            <span class="text-[11px] font-bold">Dhaka Depot In-Charge</span>
                            <span class="text-[10px] text-emerald-600 font-mono">dhaka.depot@aci-bd.com / EMP-101</span>
                        </button>
                    </form>
                    @endif

                    @if(isset($demoUsers['jashore_operator']))
                    <form action="{{ route('switch.role') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $demoUsers['jashore_operator']->id }}">
                        <button type="submit" class="w-full p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100 text-emerald-900 font-semibold text-left transition flex flex-col">
                            <span class="text-[11px] font-bold">Jashore Depot</span>
                            <span class="text-[10px] text-emerald-600 font-mono">jashore.depot@aci-bd.com / EMP-102</span>
                        </button>
                    </form>
                    @endif

                    @if(isset($demoUsers['auditor']))
                    <form action="{{ route('switch.role') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $demoUsers['auditor']->id }}">
                        <button type="submit" class="w-full p-2.5 rounded-xl border border-amber-200 bg-amber-50/70 hover:bg-amber-100 text-amber-900 font-semibold text-left transition flex flex-col">
                            <span class="text-[11px] font-bold">Internal Auditor</span>
                            <span class="text-[10px] text-amber-600 font-mono">auditor@aci-bd.com / EMP-002</span>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">&copy; {{ date('Y') }} ACI Motors Ltd. All rights reserved.</p>
    </div>

</body>
</html>
