<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ProKeeper')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|jetbrains-mono:400,500,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 flex items-center justify-center p-4 selection:bg-blue-600 selection:text-white">
    <div class="max-w-md w-full text-center space-y-6">
        <div class="inline-block mx-auto mb-3">
            <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-14 w-auto mx-auto">
        </div>

        <div class="space-y-2">
            <div class="font-mono text-sm font-black uppercase tracking-widest text-blue-400">@yield('code', 'ProKeeper')</div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">@yield('heading', 'Notice')</h1>
            <p class="text-sm text-slate-400 leading-relaxed">@yield('message', 'An unexpected error occurred.')</p>
        </div>

        <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url('/') }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold transition shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Return to ProKeeper</span>
            </a>
            <a href="javascript:history.back()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">
                Go Back
            </a>
        </div>

        <div class="text-[11px] text-slate-600 font-mono pt-4 border-t border-slate-900">
            PROKEEPER ATHLETIC PLATFORM &bull; SYSTEM STATUS NORMAL
        </div>
    </div>
</body>
</html>
