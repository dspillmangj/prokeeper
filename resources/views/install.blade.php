<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100 selection:bg-blue-600 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Install ProKeeper App - PWA for iOS, Android & Desktop</title>
    <meta name="description" content="Install ProKeeper on your device. Choose your favorite app icon theme, install the progressive web app in seconds, and access live scoreboards or operator controls with or without signing in.">

    <!-- PWA & Mobile Meta Tags -->
    <meta name="theme-color" id="meta-theme-color" content="#020617">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ProKeeper">
    
    <!-- Dynamic Manifest & Apple Touch Icon -->
    <link rel="manifest" href="/manifest.json" id="pwa-manifest-link">
    <link rel="apple-touch-icon" href="/icons/appicon_blue_appletouchicon.png" id="apple-touch-icon-link">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg" id="favicon-link">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|jetbrains-mono:400,500,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @keyframes pulse-subtle {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.02); opacity: 0.95; }
        }
        .pulse-subtle {
            animation: pulse-subtle 4s ease-in-out infinite;
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(51, 65, 85, 0.6);
        }
        .glass-card-hover:hover {
            border-color: rgba(59, 130, 246, 0.6);
            box-shadow: 0 12px 30px -10px rgba(37, 99, 235, 0.25);
        }
    </style>
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 flex flex-col justify-between">
    <!-- Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/70 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center space-x-2.5 text-white font-bold text-lg tracking-tight group">
                <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-7 w-auto transition-transform group-hover:scale-105">
                <span class="text-white font-black tracking-wider text-base">PROKEEPER</span>
                <span class="text-[10px] font-mono font-bold text-blue-400 bg-blue-950/80 border border-blue-800/80 px-2 py-0.5 rounded-full uppercase tracking-wider">PWA Engine</span>
            </a>

            <div class="flex items-center space-x-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs sm:text-sm font-semibold transition border border-slate-700">
                        Open Dashboard &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-3.5 py-1.5 rounded-xl text-slate-300 hover:text-white text-xs sm:text-sm font-medium transition hover:bg-slate-800">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs sm:text-sm font-semibold transition shadow-lg shadow-blue-600/25">
                        Get Started
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-10 sm:py-16">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
            
            <!-- Hero Section -->
            <div class="text-center space-y-5 max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-950/80 border border-blue-800/80 text-blue-400 text-xs font-mono font-semibold">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                    <span>PROGRESSIVE WEB APP READY</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-tight">
                    Install <span class="bg-gradient-to-r from-blue-400 via-indigo-300 to-sky-400 bg-clip-text text-transparent">ProKeeper</span> on Your Device
                </h1>

                <p class="text-base sm:text-lg text-slate-400 leading-relaxed max-w-2xl mx-auto">
                    Enjoy fullscreen speed, instant live scoreboard updates, and zero app store downloads. Pick your app icon theme, install in seconds, and jump in as an operator or spectator.
                </p>

                <!-- Dynamic Install Status Banner -->
                <div id="install-status-pill" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-slate-900 border border-slate-800 text-xs font-medium text-slate-300">
                    <svg class="w-4 h-4 text-amber-400 animate-spin" id="install-spinner" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span id="install-status-text">Detecting browser installation support...</span>
                </div>
            </div>

            <!-- STEP 1: ICON SELECTION & DEVICE PREVIEW -->
            <section class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 border-b border-slate-800/80 pb-4">
                    <div>
                        <div class="text-xs font-mono font-bold text-blue-400 uppercase tracking-widest">Step 1 of 2</div>
                        <h2 class="text-2xl sm:text-3xl font-black text-white">Choose Your App Icon Theme</h2>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400">Select the icon badge you want displayed on your home screen or dock.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <!-- Icon Options (3 Theme Cards) -->
                    <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Theme 1: Electric Blue -->
                        <button type="button" 
                                onclick="selectIconTheme('blue')"
                                id="btn-theme-blue"
                                class="theme-card relative text-left p-5 rounded-2xl glass-card transition-all duration-300 border-2 border-blue-500 bg-slate-900/90 shadow-xl shadow-blue-600/20 group">
                            <div class="absolute top-3 right-3 w-5 h-5 rounded-full bg-blue-600 flex items-center justify-center text-white check-badge">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="w-16 h-16 rounded-2xl bg-slate-950 p-2.5 border border-blue-500/30 mb-4 shadow-lg group-hover:scale-105 transition-transform flex items-center justify-center">
                                <img src="/icons/appicon_blue_png.png" alt="Electric Blue Icon" class="w-full h-full object-contain drop-shadow">
                            </div>
                            <div class="text-xs font-mono font-bold text-blue-400 uppercase mb-1">Brand Signature</div>
                            <div class="text-base font-bold text-white">Electric Blue</div>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Official athletic royal gradient with high-visibility vibrancy.</p>
                        </button>

                        <!-- Theme 2: Midnight Obsidian -->
                        <button type="button" 
                                onclick="selectIconTheme('black')"
                                id="btn-theme-black"
                                class="theme-card relative text-left p-5 rounded-2xl glass-card transition-all duration-300 border-2 border-slate-800 hover:border-slate-600 group">
                            <div class="absolute top-3 right-3 w-5 h-5 rounded-full bg-slate-700 hidden items-center justify-center text-white check-badge">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="w-16 h-16 rounded-2xl bg-black p-2.5 border border-slate-800 mb-4 shadow-lg group-hover:scale-105 transition-transform flex items-center justify-center">
                                <img src="/icons/appicon_black_png.png" alt="Obsidian Dark Icon" class="w-full h-full object-contain drop-shadow">
                            </div>
                            <div class="text-xs font-mono font-bold text-slate-400 uppercase mb-1">Stealth OLED</div>
                            <div class="text-base font-bold text-white">Midnight Obsidian</div>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Sleek matte deep-dark icon optimized for OLED dark modes.</p>
                        </button>

                        <!-- Theme 3: Pure Quartz -->
                        <button type="button" 
                                onclick="selectIconTheme('white')"
                                id="btn-theme-white"
                                class="theme-card relative text-left p-5 rounded-2xl glass-card transition-all duration-300 border-2 border-slate-800 hover:border-slate-600 group">
                            <div class="absolute top-3 right-3 w-5 h-5 rounded-full bg-slate-700 hidden items-center justify-center text-white check-badge">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="w-16 h-16 rounded-2xl bg-slate-100 p-2.5 border border-slate-200 mb-4 shadow-lg group-hover:scale-105 transition-transform flex items-center justify-center">
                                <img src="/icons/appicon_white_png.png" alt="Pure Quartz Icon" class="w-full h-full object-contain drop-shadow">
                            </div>
                            <div class="text-xs font-mono font-bold text-slate-300 uppercase mb-1">Minimalist Light</div>
                            <div class="text-base font-bold text-white">Pure Quartz</div>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">Crisp high-contrast quartz aesthetic with pure clean lines.</p>
                        </button>
                    </div>

                    <!-- Interactive Device Live Preview Mockup -->
                    <div class="lg:col-span-5 flex flex-col items-center">
                        <div class="relative w-full max-w-[280px] aspect-[9/18] bg-slate-900 rounded-[44px] p-3 shadow-2xl border-4 border-slate-800 shadow-blue-900/20 flex flex-col justify-between overflow-hidden">
                            <!-- Dynamic Wallpaper Background -->
                            <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-indigo-950 to-slate-950 -z-10"></div>
                            
                            <!-- Phone Top Notch / Dynamic Island -->
                            <div class="w-full flex justify-between items-center px-4 pt-1 text-[11px] font-mono text-slate-400 z-10">
                                <span>9:41</span>
                                <div class="w-20 h-4 bg-black rounded-full mx-auto"></div>
                                <div class="flex items-center space-x-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 19.4c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0l1.79-1.79C9.07 20.26 11.02 21 12 21c4.97 0 9-4.03 9-9s-4.03-9-9-9z"/></svg>
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 4h-3V2h-4v2H7v18h10V4z"/></svg>
                                </div>
                            </div>

                            <!-- Phone Home Screen Grid -->
                            <div class="px-3 pt-6 space-y-6">
                                <!-- Widget -->
                                <div class="p-3 rounded-2xl bg-slate-800/80 border border-slate-700/60 backdrop-blur text-left">
                                    <div class="text-[9px] font-mono font-bold text-blue-400 uppercase">Live Game Alert</div>
                                    <div class="text-xs font-bold text-white mt-0.5">LBS Eagles vs Titans</div>
                                    <div class="text-[10px] text-slate-300 font-mono mt-1">Q4 • 01:24 | 68 - 65</div>
                                </div>

                                <!-- App Icons Grid -->
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <!-- PROKEEPER DYNAMIC APP ICON -->
                                    <div class="flex flex-col items-center group cursor-pointer" onclick="triggerInstallPrompt()">
                                        <div class="relative w-14 h-14 rounded-2xl p-1 bg-slate-950 border border-slate-700 shadow-xl transition-all duration-300 transform group-hover:scale-110" id="preview-icon-wrapper">
                                            <img id="preview-app-icon" src="/icons/appicon_blue_png.png" alt="ProKeeper App Icon" class="w-full h-full object-contain rounded-xl">
                                            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-blue-500 border-2 border-slate-900 animate-pulse"></span>
                                        </div>
                                        <span class="text-[10px] font-semibold text-white mt-1 tracking-tight" id="preview-app-label">ProKeeper</span>
                                    </div>

                                    <!-- Dummy Icon 1 -->
                                    <div class="flex flex-col items-center opacity-40">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-800/60 border border-slate-700 flex items-center justify-center text-slate-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        </div>
                                        <span class="text-[10px] text-slate-400 mt-1">Calendar</span>
                                    </div>

                                    <!-- Dummy Icon 2 -->
                                    <div class="flex flex-col items-center opacity-40">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-800/60 border border-slate-700 flex items-center justify-center text-slate-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                        </div>
                                        <span class="text-[10px] text-slate-400 mt-1">Stats</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Phone Dock -->
                            <div class="p-2.5 rounded-3xl bg-slate-900/80 border border-slate-800 backdrop-blur flex justify-around items-center mb-1">
                                <div class="w-10 h-10 rounded-xl bg-slate-800/80 flex items-center justify-center text-slate-400 opacity-60">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-slate-800/80 flex items-center justify-center text-slate-400 opacity-60">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                </div>
                                <div class="w-10 h-10 rounded-xl bg-slate-800/80 flex items-center justify-center text-slate-400 opacity-60">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                            </div>
                        </div>
                        <div class="text-[11px] text-slate-500 mt-2 font-mono">Live Home Screen Mockup Preview</div>
                    </div>
                </div>
            </section>

            <!-- STEP 2: INSTALLATION TRIGGER & DEVICE GUIDES -->
            <section class="space-y-6">
                <div class="border-b border-slate-800/80 pb-4">
                    <div class="text-xs font-mono font-bold text-blue-400 uppercase tracking-widest">Step 2 of 2</div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white">Install to Your Device</h2>
                </div>

                <!-- Primary Install Card -->
                <div class="glass-card rounded-3xl p-6 sm:p-8 space-y-8 border-blue-500/30">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div class="space-y-2 text-center sm:text-left">
                            <h3 class="text-xl sm:text-2xl font-bold text-white flex items-center justify-center sm:justify-start gap-2">
                                <span>Native One-Click PWA Installation</span>
                            </h3>
                            <p class="text-sm text-slate-400 max-w-xl">
                                Adds ProKeeper as an independent, lightweight application without app store bloat or memory hogging.
                            </p>
                        </div>

                        <!-- Install CTA Button -->
                        <div class="w-full sm:w-auto flex flex-col items-center">
                            <button type="button" 
                                    id="btn-install-pwa"
                                    onclick="triggerInstallPrompt()" 
                                    class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-base transition duration-200 shadow-xl shadow-blue-600/30 flex items-center justify-center gap-3 group">
                                <svg class="w-6 h-6 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span id="btn-install-text">Install ProKeeper App</span>
                            </button>
                        </div>
                    </div>

                    <!-- Platform-Specific Step-by-Step Accordion / Tabs -->
                    <div class="pt-6 border-t border-slate-800/80">
                        <div class="text-xs font-mono font-bold text-slate-400 uppercase tracking-wider mb-4">
                            Platform-Specific Instructions
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-left">
                            <!-- iOS Safari Guide -->
                            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                                <div class="flex items-center gap-2 text-blue-400 font-bold text-sm">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 7.17c.61-.75 1.04-1.8 1.01-2.87-.96.04-2.12.64-2.79 1.43-.58.68-1.1 1.77-1.02 2.82 1.08.08 2.19-.63 2.8-1.38z"/></svg>
                                    <span>iOS / Safari (iPhone & iPad)</span>
                                </div>
                                <ol class="text-xs text-slate-300 space-y-2 list-decimal list-inside font-medium leading-relaxed">
                                    <li>Open this page in <strong class="text-white">Safari</strong>.</li>
                                    <li>Tap the <strong class="text-white">Share</strong> button <span class="px-1.5 py-0.5 rounded bg-slate-800 text-blue-300 font-mono">📤</span> at the bottom of Safari.</li>
                                    <li>Scroll down and tap <strong class="text-white">"Add to Home Screen"</strong> <span class="px-1.5 py-0.5 rounded bg-slate-800 text-blue-300 font-mono">➕</span>.</li>
                                    <li>Tap <strong class="text-white">Add</strong> in the top right to complete.</li>
                                </ol>
                            </div>

                            <!-- Android Chrome Guide -->
                            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                                <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m-11.046 0c-.5511 0-.9993-.4486-.9993-.9997s.4482-.9993.9993-.9993c.5511 0 .9993.4482.9993.9993s-.4482.9997-.9993.9997m11.4045-6.02l1.9973-3.4592a.416.416 0 00-.1521-.5676.416.416 0 00-.5676.1521l-2.0223 3.503C15.5902 8.411 13.8533 8.0833 12 8.0833s-3.5902.3277-5.1368.8664L4.8409 5.4467a.4161.4161 0 00-.5677-.1521.4157.4157 0 00-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396"/></svg>
                                    <span>Android / Chrome & Samsung</span>
                                </div>
                                <ol class="text-xs text-slate-300 space-y-2 list-decimal list-inside font-medium leading-relaxed">
                                    <li>Tap the <strong class="text-white">"Install ProKeeper App"</strong> button above.</li>
                                    <li>Or tap the <strong class="text-white">three dots ⋮</strong> in the Chrome menu.</li>
                                    <li>Select <strong class="text-white">"Install App"</strong> or <strong class="text-white">"Add to Home Screen"</strong>.</li>
                                    <li>Confirm to launch directly from your apps list.</li>
                                </ol>
                            </div>

                            <!-- Desktop Chrome / Mac / Windows -->
                            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                                <div class="flex items-center gap-2 text-indigo-400 font-bold text-sm">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z"/></svg>
                                    <span>Desktop (Mac, Windows, ChromeOS)</span>
                                </div>
                                <ol class="text-xs text-slate-300 space-y-2 list-decimal list-inside font-medium leading-relaxed">
                                    <li>Click <strong class="text-white">Install</strong> in your browser's address bar <span class="px-1.5 py-0.5 rounded bg-slate-800 text-indigo-300 font-mono">💻</span>.</li>
                                    <li>On macOS Safari Sonoma+: Choose <strong class="text-white">File &rarr; Add to Dock</strong>.</li>
                                    <li>Launches as a dedicated standalone window with zero browser tab clutter.</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Scan with Mobile Camera QR Code -->
                    <div class="pt-6 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-6 bg-slate-900/40 p-5 rounded-2xl border border-slate-800/60">
                        <div class="flex items-center space-x-4">
                            <div class="w-20 h-20 bg-white p-2 rounded-xl shadow-lg shrink-0" id="qr-code-container">
                                <!-- QR code SVG inserted dynamically via JS -->
                            </div>
                            <div class="space-y-1">
                                <div class="text-sm font-bold text-white">Scan to Install on Mobile</div>
                                <div class="text-xs text-slate-400">Point your phone camera at this QR code to open the installer on your phone.</div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button type="button" 
                                    onclick="copyInstallUrl()" 
                                    id="btn-copy-link"
                                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition border border-slate-700 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10a2 2 0 00-2 2v3a2 2 0 002 2h10a2 2 0 002-2v-3a2 2 0 00-2-2z"/>
                                </svg>
                                <span id="copy-btn-text">Copy Install Link</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- STEP 3: USE THE APP - SIGN IN OR STAY ANONYMOUS -->
            <section class="space-y-6">
                <div class="border-b border-slate-800/80 pb-4">
                    <div class="text-xs font-mono font-bold text-emerald-400 uppercase tracking-widest">Get Started Today</div>
                    <h2 class="text-2xl sm:text-3xl font-black text-white">Choose How You Want to Use ProKeeper</h2>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">Sign in for official game management, or stay 100% anonymous to view live games.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- OPTION A: OPERATOR / ATHLETIC DIRECTOR (SIGN IN) -->
                    <div class="glass-card glass-card-hover rounded-3xl p-6 sm:p-8 flex flex-col justify-between space-y-6 border-blue-500/30 relative overflow-hidden group">
                        <div class="absolute -top-12 -right-12 w-36 h-36 bg-blue-600/10 rounded-full blur-3xl pointer-events-none"></div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-3 py-1 rounded-full bg-blue-950/80 border border-blue-800/80 text-blue-400 text-xs font-mono font-bold">
                                    ATHLETIC DIRECTOR & OPERATOR
                                </span>
                                <span class="text-xs text-slate-400 font-mono">Full Command Center</span>
                            </div>

                            <h3 class="text-2xl font-black text-white group-hover:text-blue-300 transition">
                                Sign In & Score Games
                            </h3>

                            <p class="text-sm text-slate-400 leading-relaxed">
                                Access turbo keyboard scorekeeping, roster management, live foul counts, official NCAA scorebook signatures, and PDF archives.
                            </p>

                            <ul class="text-xs text-slate-300 space-y-2.5 pt-2">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>High-speed keyboard hotkeys (points, fouls, subs, turnovers)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Full Basketball & Volleyball rules engine support</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <span>Real-time spectator broadcast and digital scorebook exports</span>
                                </li>
                            </ul>
                        </div>

                        <div class="pt-4 space-y-3">
                            @auth
                                <a href="{{ route('dashboard') }}" class="w-full py-3.5 px-6 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-2">
                                    <span>Open Operator Dashboard</span>
                                    <span>&rarr;</span>
                                </a>
                            @else
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <a href="{{ route('login') }}" class="flex-1 py-3.5 px-6 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition shadow-lg shadow-blue-600/30 text-center">
                                        Operator Sign In
                                    </a>
                                    <a href="{{ route('register') }}" class="flex-1 py-3.5 px-6 rounded-2xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-slate-200 font-semibold text-sm transition text-center">
                                        Create Account
                                    </a>
                                </div>
                            @endauth
                        </div>
                    </div>

                    <!-- OPTION B: ANONYMOUS FAN & SPECTATOR (GAME CODE) -->
                    <div class="glass-card glass-card-hover rounded-3xl p-6 sm:p-8 flex flex-col justify-between space-y-6 border-emerald-500/30 relative overflow-hidden group">
                        <div class="absolute -top-12 -right-12 w-36 h-36 bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="px-3 py-1 rounded-full bg-emerald-950/80 border border-emerald-800/80 text-emerald-400 text-xs font-mono font-bold">
                                    ANONYMOUS FAN & SPECTATOR
                                </span>
                                <span class="text-xs text-slate-400 font-mono">No Account Needed</span>
                            </div>

                            <h3 class="text-2xl font-black text-white group-hover:text-emerald-300 transition">
                                Enter Game Code
                            </h3>

                            <p class="text-sm text-slate-400 leading-relaxed">
                                Enter a 6-character game code provided by your school or coach to instantly follow live scoreboard scores, box scores, or official scorebooks.
                            </p>

                            <!-- Interactive Game Code Input -->
                            <div class="space-y-3 pt-2">
                                <div>
                                    <label for="spectator_game_code" class="block text-xs font-mono text-slate-400 uppercase tracking-wider mb-1.5">Game Access Code</label>
                                    <div class="relative">
                                        <input type="text" 
                                               id="spectator_game_code" 
                                               name="game_code" 
                                               placeholder="e.g. 7X9K2A"
                                               maxlength="10"
                                               oninput="this.value = this.value.toUpperCase()"
                                               class="w-full px-4 py-3.5 rounded-2xl bg-slate-950 border border-slate-700 text-white font-mono text-lg font-bold tracking-widest uppercase focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition placeholder:text-slate-600 placeholder:font-normal">
                                        <button type="button" 
                                                onclick="clearGameCode()" 
                                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 text-xs font-mono">
                                            CLEAR
                                        </button>
                                    </div>
                                </div>

                                <!-- 3 Instant Launch Buttons -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                                    <button type="button" 
                                            onclick="launchGame('watch')" 
                                            class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700/80 hover:border-emerald-500/50 text-slate-200 text-xs font-semibold transition text-center flex flex-col items-center gap-1 group">
                                        <span class="text-emerald-400 font-mono text-[10px]">📺 FAN VIEW</span>
                                        <span class="font-bold">Watch Game</span>
                                    </button>

                                    <button type="button" 
                                            onclick="launchGame('scoreboard')" 
                                            class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700/80 hover:border-blue-500/50 text-slate-200 text-xs font-semibold transition text-center flex flex-col items-center gap-1 group">
                                        <span class="text-blue-400 font-mono text-[10px]">⚡ STADIUM LED</span>
                                        <span class="font-bold">Scoreboard</span>
                                    </button>

                                    <button type="button" 
                                            onclick="launchGame('scorebook')" 
                                            class="p-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700/80 hover:border-amber-500/50 text-slate-200 text-xs font-semibold transition text-center flex flex-col items-center gap-1 group">
                                        <span class="text-amber-400 font-mono text-[10px]">📋 NCAA SHEET</span>
                                        <span class="font-bold">Scorebook</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Active / Recent Demo Games (if available in DB) -->
                        @if(isset($recentGames) && $recentGames->count() > 0)
                            <div class="pt-4 border-t border-slate-800/80">
                                <div class="text-[11px] font-mono text-slate-400 mb-2">Or jump into a recent live game:</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($recentGames as $rg)
                                        <button type="button" 
                                                onclick="setAndLaunchGame('{{ $rg->access_code ?? $rg->id }}')" 
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs font-medium text-slate-300 hover:text-white transition flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            <span>{{ $rg->home_team_name ?? 'Home' }} vs {{ $rg->away_team_name ?? 'Away' }}</span>
                                            <span class="font-mono text-[10px] text-blue-400">({{ $rg->access_code }})</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="pt-3 text-[11px] text-slate-500 font-mono">
                                Don't have a game code? Ask your athletic scorekeeper or visit <a href="/scoreboard" class="text-blue-400 hover:underline">/scoreboard</a>.
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-8 bg-slate-950 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center space-x-2">
                <img src="{{ asset('images/icon-white.svg') }}" alt="ProKeeper" class="h-4 w-auto opacity-70">
                <span>&copy; {{ date('Y') }} ProKeeper Systems. Progressive Web App Edition.</span>
            </div>
            <div class="flex items-center space-x-6 text-slate-400 font-medium">
                <a href="/install" class="hover:underline text-blue-400">Install PWA</a>
                <a href="/scoreboard" class="hover:underline hover:text-slate-200">Scoreboard</a>
                <a href="/watch" class="hover:underline hover:text-slate-200">Watch Live</a>
                <a href="/scorebook" class="hover:underline hover:text-slate-200">Scorebook</a>
                <a href="/login" class="hover:underline hover:text-slate-200">Sign In</a>
            </div>
        </div>
    </footer>

    <!-- Interactive JS Engine for Theme Selector, PWA Install Prompt & QR Code -->
    <script>
        // State
        let deferredPrompt = null;
        let selectedTheme = localStorage.getItem('prokeeper_icon_theme') || 'blue';

        const themeData = {
            blue: {
                name: 'Electric Blue',
                iconPng: '/icons/appicon_blue_png.png',
                appleTouch: '/icons/appicon_blue_appletouchicon.png',
                manifest: '/manifest-blue.json',
                themeColor: '#020617',
                borderClass: 'border-blue-500',
                glowClass: 'shadow-blue-600/20'
            },
            black: {
                name: 'Midnight Obsidian',
                iconPng: '/icons/appicon_black_png.png',
                appleTouch: '/icons/appicon_black_appletouchicon.png',
                manifest: '/manifest-black.json',
                themeColor: '#000000',
                borderClass: 'border-slate-500',
                glowClass: 'shadow-slate-600/20'
            },
            white: {
                name: 'Pure Quartz',
                iconPng: '/icons/appicon_white_png.png',
                appleTouch: '/icons/appicon_white_appletouchicon.png',
                manifest: '/manifest-white.json',
                themeColor: '#ffffff',
                borderClass: 'border-slate-300',
                glowClass: 'shadow-slate-300/20'
            }
        };

        // Initialize Theme Selection
        function selectIconTheme(theme) {
            if (!themeData[theme]) theme = 'blue';
            selectedTheme = theme;
            localStorage.setItem('prokeeper_icon_theme', theme);

            // Update theme cards UI
            ['blue', 'black', 'white'].forEach(t => {
                const btn = document.getElementById(`btn-theme-${t}`);
                if (!btn) return;
                const badge = btn.querySelector('.check-badge');
                
                if (t === theme) {
                    btn.classList.remove('border-slate-800', 'hover:border-slate-600');
                    btn.classList.add('border-blue-500', 'bg-slate-900/90', 'shadow-xl', 'shadow-blue-600/20');
                    if (badge) {
                        badge.classList.remove('hidden');
                        badge.classList.add('flex');
                    }
                } else {
                    btn.classList.remove('border-blue-500', 'shadow-xl', 'shadow-blue-600/20');
                    btn.classList.add('border-slate-800', 'hover:border-slate-600');
                    if (badge) {
                        badge.classList.remove('flex');
                        badge.classList.add('hidden');
                    }
                }
            });

            // Update Device Mockup Icon
            const previewIcon = document.getElementById('preview-app-icon');
            if (previewIcon) {
                previewIcon.src = themeData[theme].iconPng;
            }

            // Update Dynamic Manifest Link and Apple Touch Icon
            const manifestLink = document.getElementById('pwa-manifest-link');
            if (manifestLink) {
                manifestLink.href = `/manifest.json?theme=${theme}`;
            }

            const appleTouchLink = document.getElementById('apple-touch-icon-link');
            if (appleTouchLink) {
                appleTouchLink.href = themeData[theme].appleTouch;
            }

            const metaTheme = document.getElementById('meta-theme-color');
            if (metaTheme) {
                metaTheme.content = themeData[theme].themeColor;
            }
        }

        // PWA Install Prompt Handler
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            const statusPill = document.getElementById('install-status-pill');
            const statusText = document.getElementById('install-status-text');
            const spinner = document.getElementById('install-spinner');
            const installBtnText = document.getElementById('btn-install-text');

            if (spinner) spinner.remove();
            if (statusText) statusText.innerText = 'Ready to Install on your Device';
            if (statusPill) {
                statusPill.classList.remove('border-slate-800');
                statusPill.classList.add('border-emerald-500/50', 'bg-emerald-950/40', 'text-emerald-300');
            }
            if (installBtnText) installBtnText.innerText = 'Install ProKeeper App Now';
        });

        // Standalone Mode Detection
        function checkStandalone() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || 
                                 window.navigator.standalone || 
                                 document.referrer.includes('android-app://');

            const statusPill = document.getElementById('install-status-pill');
            const statusText = document.getElementById('install-status-text');
            const spinner = document.getElementById('install-spinner');
            const installBtn = document.getElementById('btn-install-pwa');

            if (isStandalone) {
                if (spinner) spinner.remove();
                if (statusText) statusText.innerText = 'ProKeeper is Installed & Running in Standalone App Mode';
                if (statusPill) {
                    statusPill.className = 'inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-emerald-950/80 border border-emerald-700/60 text-xs font-semibold text-emerald-300';
                }
                if (installBtn) {
                    installBtn.innerHTML = `
                        <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>App Already Installed</span>
                    `;
                    installBtn.classList.remove('bg-blue-600', 'hover:bg-blue-500');
                    installBtn.classList.add('bg-emerald-800/80', 'cursor-default');
                }
            } else if (!deferredPrompt) {
                if (spinner) spinner.remove();
                if (statusText) statusText.innerText = 'Use instructions below for iOS, Android or Desktop';
            }
        }

        async function triggerInstallPrompt() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    deferredPrompt = null;
                    checkStandalone();
                }
            } else {
                // If browser doesn't support direct trigger, scroll smoothly to platform instructions
                const isIos = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                if (isIos) {
                    alert('To install on iPhone or iPad: Tap the Share button (📤) in Safari and tap "Add to Home Screen" (➕).');
                } else {
                    alert('To install: Tap your browser menu (⋮ or Share) and select "Install App" or "Add to Home Screen".');
                }
            }
        }

        // Copy Install URL
        async function copyInstallUrl() {
            const url = window.location.href;
            const success = window.copyToClipboard ? await window.copyToClipboard(url) : true;
            const btnText = document.getElementById('copy-btn-text');
            if (btnText) {
                btnText.innerText = 'Copied Link!';
                setTimeout(() => {
                    btnText.innerText = 'Copy Install Link';
                }, 2500);
            }
        }

        // Game Code Launchers
        function getEnteredGameCode() {
            const input = document.getElementById('spectator_game_code');
            return input ? input.value.trim().toUpperCase() : '';
        }

        function clearGameCode() {
            const input = document.getElementById('spectator_game_code');
            if (input) {
                input.value = '';
                input.focus();
            }
        }

        function launchGame(routePrefix) {
            const code = getEnteredGameCode();
            if (code) {
                window.location.href = `/${routePrefix}/${encodeURIComponent(code)}`;
            } else {
                window.location.href = `/${routePrefix}`;
            }
        }

        function setAndLaunchGame(code) {
            const input = document.getElementById('spectator_game_code');
            if (input) input.value = code;
            launchGame('watch');
        }

        // Render QR Code on Load
        window.addEventListener('DOMContentLoaded', () => {
            selectIconTheme(selectedTheme);
            checkStandalone();

            // Render SVG QR code for the current URL
            const qrContainer = document.getElementById('qr-code-container');
            if (qrContainer && typeof window.generateQrSvg === 'function') {
                qrContainer.innerHTML = window.generateQrSvg(window.location.href, { size: 64, margin: 1 });
            }

            // Register Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js').catch(err => {
                    console.log('SW error:', err);
                });
            }
        });
    </script>
</body>
</html>
