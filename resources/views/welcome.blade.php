<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ProKeeper - Athletic Statistics & Scorekeeping</title>
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

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|jetbrains-mono:400,500,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 flex flex-col justify-between selection:bg-blue-600 selection:text-white">
    <!-- Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center space-x-2.5 text-white font-bold text-lg tracking-tight group">
                <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-7 w-auto">
                <span class="text-white font-black tracking-wider text-base">PROKEEPER</span>
            </a>

            <div class="flex items-center space-x-3">
                <a href="{{ route('install') }}" class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-blue-950/80 border border-blue-700/60 text-blue-300 hover:text-white hover:bg-blue-900/60 transition text-xs font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>Install PWA</span>
                </a>
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition shadow-lg shadow-blue-600/30">
                        Open Dashboard &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-slate-300 hover:text-white text-sm font-medium transition hover:bg-slate-800">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition shadow-lg shadow-blue-600/30">
                        Get Started
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main -->
    <main class="flex-1 flex items-center justify-center p-6">
        <div class="max-w-3xl w-full text-center space-y-8">
            <div class="space-y-4">
                <div class="inline-block mx-auto mb-2">
                    <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-16 w-auto mx-auto">
                </div>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight text-white">
                    ProKeeper Athletic Engine
                </h1>
                <p class="text-base text-slate-400 max-w-md mx-auto leading-relaxed">
                    Lightning-fast keyboard scorekeeping, live fan scoreboards, and official digital scorebooks.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-left">
                <a href="{{ route('install') }}" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-emerald-500/50 hover:bg-slate-900/90 transition group">
                    <div class="text-xs font-mono font-bold text-emerald-400 uppercase tracking-wider mb-1">Mobile & Desktop</div>
                    <div class="text-base font-bold text-white group-hover:text-emerald-300 transition">Install App PWA &rarr;</div>
                    <div class="text-xs text-slate-400 mt-1">Pick your theme icon and install for instant fullscreen access.</div>
                </a>

                <a href="{{ route('public.stats.prompt') }}" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-900/90 transition group">
                    <div class="text-xs font-mono font-bold text-indigo-400 uppercase tracking-wider mb-1">Bench & Coach</div>
                    <div class="text-base font-bold text-white group-hover:text-indigo-300 transition">Coach Live Stats &rarr;</div>
                    <div class="text-xs text-slate-400 mt-1">Quarter-by-quarter player box stats & PDF reports.</div>
                </a>

                <a href="{{ route('public.scoreboard.prompt') }}" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-blue-500/50 hover:bg-slate-900/90 transition group">
                    <div class="text-xs font-mono font-bold text-blue-400 uppercase tracking-wider mb-1">Fan Experience</div>
                    <div class="text-base font-bold text-white group-hover:text-blue-300 transition">Live Stadium Board &rarr;</div>
                    <div class="text-xs text-slate-400 mt-1">Enter a game code for real-time scores, clock, and fouls.</div>
                </a>

                <a href="{{ route('public.scorebook.prompt') }}" class="p-5 rounded-2xl bg-slate-900 border border-slate-800 hover:border-amber-500/50 hover:bg-slate-900/90 transition group">
                    <div class="text-xs font-mono font-bold text-amber-400 uppercase tracking-wider mb-1">Official Record</div>
                    <div class="text-base font-bold text-white group-hover:text-amber-300 transition">Digital Scorebook &rarr;</div>
                    <div class="text-xs text-slate-400 mt-1">View official running scorebooks and PDF archives.</div>
                </a>
            </div>

            @guest
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition shadow-lg shadow-blue-600/30">
                        Operator Sign In
                    </a>
                    <a href="{{ route('register') }}" class="w-full sm:w-auto px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-white font-semibold text-sm transition">
                        Create Organization Account
                    </a>
                </div>
            @endguest
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-6 bg-slate-950 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>&copy; {{ date('Y') }} ProKeeper Systems. All rights reserved.</div>
            <div class="flex items-center space-x-6 text-slate-400 font-medium">
                <a href="https://prokeeper.taligent.dev" class="hover:underline hover:text-slate-200 transition">Home</a>
                <a href="https://app.prokeeper.taligent.dev" class="hover:underline hover:text-slate-200 transition">App</a>
                <a href="https://docs.prokeeper.taligent.dev" class="hover:underline hover:text-slate-200 transition">Docs</a>
            </div>
        </div>
    </footer>
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
