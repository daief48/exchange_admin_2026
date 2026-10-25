<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Expense Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        yamaha: { 50: '#EEF4FF', 100: '#DCE8FD', 200: '#B9D1FB', 300: '#86B4F8', 400: '#4D8DF4', 500: '#2266EF', 600: '#1149B0', 700: '#0E3A8C', 800: '#0A2A6B', 900: '#061A40', 950: '#041233' },
                        teal: { 50: '#ECFAFF', 100: '#CFF3FE', 200: '#A5E9FD', 300: '#6BDCF9', 400: '#22C9F5', 500: '#00B4E6', 600: '#0093C7', 700: '#0077A5', 800: '#056287', 900: '#0A526F' },
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 font-['Plus_Jakarta_Sans',sans-serif]">

    <div class="max-w-md w-full">
        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-yamaha-600 to-cyan-400 text-white shadow-xl shadow-yamaha-600/25 mb-4">
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
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">EXPENSE ADMIN</h1>
            <p class="text-yamaha-600 font-semibold text-sm tracking-wide mt-1">Distribution &amp; Showroom Expense Management</p>
            <p class="text-slate-400 text-xs mt-1">Enterprise Budget, Daily Logistics &amp; Variance System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
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
                        <input type="text" name="login" value="{{ old('login') }}" required autofocus
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-yamaha-500 text-sm font-medium text-slate-800 bg-slate-50 focus:bg-white transition"
                            placeholder="admin@gmail.com or EMP-101">
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5 flex items-center">
                        <i class="fa-solid fa-circle-info text-yamaha-600 mr-1.5"></i>
                        Showroom &amp; Depot operators can log in using either their <strong class="mx-1">Employee ID</strong> or <strong>Email</strong>.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Access Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-yamaha-500 text-sm font-medium text-slate-800 bg-slate-50 focus:bg-white transition"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-yamaha-600 rounded border-slate-300 focus:ring-yamaha-500">
                        <span class="ml-2">Remember this device</span>
                    </label>
                    <span class="text-yamaha-700 font-semibold hover:underline cursor-pointer">Need access?</span>
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-gradient-to-r from-yamaha-600 to-cyan-500 hover:from-yamaha-500 hover:to-cyan-400 text-white font-bold text-sm rounded-xl shadow-lg shadow-yamaha-600/25 transition transform hover:-translate-y-0.5">
                    Sign In to Portal <i class="fa-solid fa-arrow-right ml-1.5"></i>
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">&copy; {{ date("Y") }} Expense Admin. All rights reserved.</p>
    </div>

</body>
</html>
