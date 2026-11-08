<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Athletic Command Center') - ProKeeper</title>
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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|jetbrains-mono:400,500,700&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100">
    <div class="min-h-full flex flex-col">
        <!-- Top Navigation Bar -->
        <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5 text-white font-bold text-lg tracking-tight group">
                        <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-7 w-auto">
                        <span class="text-white font-black tracking-wider text-base">PROKEEPER</span>
                    </a>

                    <nav class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('teams.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('teams.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Teams & Rosters
                        </a>
                        <a href="{{ route('games.create') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('games.create') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            New Game
                        </a>
                        <a href="{{ route('install') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('install') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Install App
                        </a>
                        <a href="{{ route('billing.index') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('billing.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Billing & Plans
                        </a>
                        <a href="{{ route('settings.security') }}" class="px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('settings.security*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            Security
                        </a>
                    </nav>
                </div>

                <div class="flex items-center space-x-4">
                    <div class="text-right hidden sm:block">
                        <div class="flex items-center justify-end gap-1.5">
                            <span class="text-sm font-semibold text-slate-200">{{ auth()->user()->name }}</span>
                            @php
                                $plan = auth()->user()->getEffectivePlan();
                            @endphp
                            @if ($plan === 'enterprise')
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/40">Enterprise</span>
                            @elseif ($plan === 'pro')
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-sky-500/20 text-sky-300 border border-sky-500/40">Pro</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-slate-800 text-slate-400 border border-slate-700">Free</span>
                            @endif
                        </div>
                        @if (auth()->user()->isEnterprise() && auth()->user()->organization)
                            <div class="text-xs text-slate-400">{{ auth()->user()->organization->name }}</div>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-medium transition" title="Sign out">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if (auth()->check() && !auth()->user()->hasVerifiedEmail())
                    <div class="mb-6 p-4 rounded-xl bg-amber-950/70 border border-amber-800/80 text-amber-200 text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <span>Your email address is unverified. Please verify your email before creating new rosters or live games.</span>
                        </div>
                        <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 font-semibold text-white text-xs transition">
                                Resend Verification Link
                            </button>
                        </form>
                    </div>
                @endif
                @if (session('success'))
                    <div class="mb-6 p-4 rounded-lg bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-sm flex items-center justify-between">
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-6 p-4 rounded-lg bg-rose-950/80 border border-rose-800 text-rose-300 text-sm flex items-center justify-between">
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-800/80 py-6 bg-slate-950 text-slate-500 text-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>&copy; {{ date('Y') }} ProKeeper Systems. All rights reserved.</div>
                <div class="flex items-center space-x-6 text-slate-400 font-medium">
                    <a href="https://prokeeper.org" class="hover:underline hover:text-slate-200 transition">Home</a>
                    <a href="https://app.prokeeper.org" class="hover:underline hover:text-slate-200 transition">App</a>
                    <a href="https://docs.prokeeper.org" class="hover:underline hover:text-slate-200 transition">Docs</a>
                </div>
            </div>
        </footer>
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
