<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ProKeeper - Live Game Experience')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <!-- PWA & Mobile Meta Tags -->
    <meta name="theme-color" content="#020617">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ProKeeper">
    <link rel="manifest" href="/manifest.json" id="pwa-manifest-link">
    <link rel="apple-touch-icon" href="/icons/appicon_blue_appletouchicon.png" id="apple-touch-icon-link">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|jetbrains-mono:400,500,700|dancing-script:600,700|caveat:600,700|great-vibes:400&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 selection:bg-blue-600 selection:text-white">
    <div class="min-h-full flex flex-col">
        <!-- Minimal Public Top Bar -->
        <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur sticky top-0 z-30 print:hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
                <a href="/" class="flex items-center space-x-2 text-white font-bold text-lg tracking-tight group">
                    <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-6 w-auto">
                    <span class="text-slate-100 font-extrabold tracking-wider text-sm">PROKEEPER</span>
                    <span class="text-[10px] font-mono font-bold text-blue-400 bg-blue-950/80 border border-blue-800/80 px-1.5 py-0.5 rounded">LIVE</span>
                </a>

                <div class="flex items-center space-x-3 text-xs">
                    <a href="{{ route('install') }}" class="hidden sm:inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-lg bg-blue-950/80 border border-blue-700/60 text-blue-300 hover:text-white hover:bg-blue-900/60 transition font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span>Install App</span>
                    </a>
                    <div class="flex items-center space-x-1.5 px-2.5 py-1 rounded-full bg-emerald-950/80 border border-emerald-700/60 text-emerald-400 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping inline-block mr-1"></span>
                        LIVE SYNC ACTIVE
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('SW registration skipped:', err);
                });
            });
        }
    </script>
</body>
</html>
