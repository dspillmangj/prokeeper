<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 antialiased select-none overflow-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#020617">

    <title>{{ config('app.name', 'ProKeeper') }} Operator - {{ $game->home_display_name ?? 'Home' }} vs {{ $game->away_display_name ?? 'Away' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900|jetbrains-mono:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        * {
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            height: 100dvh;
            max-height: 100dvh;
            overflow: hidden;
            margin: 0;
            padding: 0;
            background-color: #020617;
        }

        .scoreboard-glow {
            text-shadow: 0 0 25px rgba(59, 130, 246, 0.4);
        }

        .touch-active:active {
            transform: scale(0.97);
            filter: brightness(1.15);
        }
    </style>
</head>
<body class="h-full max-h-screen bg-slate-950 text-slate-100 font-sans flex flex-col overflow-hidden" x-data="{
    soundEnabled: true,
    isFullscreen: false,
    showHelpModal: false,
    showShareModal: false,
    copiedKey: null,
    audioCtx: null,

    initAudio() {
        if (!this.audioCtx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) {
                this.audioCtx = new AudioCtx();
            }
        }
        if (this.audioCtx && this.audioCtx.state === 'suspended') {
            this.audioCtx.resume();
        }
    },

    playSound(type) {
        if (!this.soundEnabled) return;
        this.initAudio();
        if (!this.audioCtx) return;

        const ctx = this.audioCtx;
        const now = ctx.currentTime;

        if (navigator.vibrate) {
            navigator.vibrate(type === 'score' ? [25, 20, 25] : 15);
        }

        if (type === 'score') {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, now);
            osc.frequency.exponentialRampToValueAtTime(880, now + 0.12);
            gain.gain.setValueAtTime(0.25, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now);
            osc.stop(now + 0.3);
        } else if (type === 'tap') {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.setValueAtTime(340, now);
            osc.frequency.exponentialRampToValueAtTime(140, now + 0.05);
            gain.gain.setValueAtTime(0.18, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.05);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now);
            osc.stop(now + 0.05);
        } else if (type === 'error') {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(140, now);
            osc.frequency.linearRampToValueAtTime(100, now + 0.18);
            gain.gain.setValueAtTime(0.2, now);
            gain.gain.exponentialRampToValueAtTime(0.001, now + 0.18);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now);
            osc.stop(now + 0.18);
        }
    },

    toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.().catch(() => {});
            this.isFullscreen = true;
        } else {
            document.exitFullscreen?.().catch(() => {});
            this.isFullscreen = false;
        }
    },

    getQr(url) {
        if (typeof window.generateQrSvg === 'function') {
            return window.generateQrSvg(url, { size: 140, margin: 2 });
        }
        return '';
    },

    async copy(text, key) {
        if (typeof window.copyToClipboard === 'function') {
            const ok = await window.copyToClipboard(text);
            if (ok) {
                this.copiedKey = key;
                if (this.soundEnabled) this.playSound('tap');
                setTimeout(() => {
                    if (this.copiedKey === key) this.copiedKey = null;
                }, 2000);
            }
        }
    }
}" @play-sound.window="playSound($event.detail)" @close-all-modals.window="showHelpModal = false; showShareModal = false">

    <!-- Ultra-Compact Stadium App Header Bar -->
    <header class="bg-slate-900/95 border-b border-slate-800/90 px-3 py-1.5 sm:px-4 flex items-center justify-between shrink-0 h-11 select-none z-30">
        <!-- Left: Return & ProKeeper Identity -->
        <div class="flex items-center space-x-2.5">
            <a href="{{ route('dashboard') }}" class="px-2 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition text-xs font-bold flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span class="hidden sm:inline">Exit</span>
            </a>

            <div class="flex items-center space-x-2 border-l border-slate-800 pl-2.5">
                <span class="text-xs font-black tracking-wider uppercase text-white">PROKEEPER</span>
                <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-mono text-[10px] font-black border border-emerald-500/40 flex items-center gap-1.5 shadow-sm" title="Operator live and synchronized">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    LIVE
                </span>
                <span class="hidden md:inline text-[10px] text-slate-400 font-mono">
                    {{ strtoupper($game->sport ?? 'Sport') }} &bull; CODE: {{ $game->access_code ?? '------' }}
                </span>
            </div>
        </div>

        <!-- Center: Game Matchup Header -->
        <div class="flex items-center space-x-2 text-xs font-bold truncate max-w-xs sm:max-w-md">
            <span class="text-blue-400 truncate">{{ $game->home_display_name ?? 'Home' }}</span>
            <span class="text-slate-500 font-mono text-[10px] font-black">VS</span>
            <span class="text-rose-400 truncate">{{ $game->away_display_name ?? 'Away' }}</span>
        </div>

        <!-- Right: Utility Controls -->
        <div class="flex items-center space-x-1.5">
            <button type="button" @click="soundEnabled = !soundEnabled; if(soundEnabled) playSound('tap');" class="p-1.5 rounded-lg text-xs font-medium transition" :class="soundEnabled ? 'bg-slate-800 text-emerald-400 border border-emerald-500/30' : 'bg-slate-900 text-slate-500 border border-slate-800'" title="Toggle Audio Sound FX">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                </svg>
            </button>

            <button type="button" @click="toggleFullscreen()" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition" title="Toggle Fullscreen">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0 0l-5-5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
            </button>

            <button type="button" @click="showHelpModal = true" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition flex items-center gap-1" title="Shortcut Guide (?)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono text-amber-300">?</span>
            </button>

            <button type="button" @click="$dispatch('open-game-details')" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 text-[11px] font-black transition gap-1.5 shadow-sm" title="Edit Game Setup, Schedule, Venue, Officials & Clock">
                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>Game Setup</span>
            </button>

            @if (($game->sport ?? 'basketball') === 'basketball')
                <a href="{{ route('public.scorebook', $game->access_code ?? '') }}" target="_blank" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-[11px] font-black transition gap-1.5 shadow-sm" title="Official Basketball Scorebook (Printable)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span>Official Scorebook</span>
                </a>
            @endif

            <button type="button" @click="showShareModal = true; if(soundEnabled) playSound('tap');" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-600/25 hover:bg-blue-600/40 text-blue-300 hover:text-white border border-blue-500/40 text-[11px] font-black transition gap-1.5 shadow-sm active:scale-95" title="Share Game Links & QR Codes">
                <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                <span>Share</span>
            </button>
        </div>
    </header>

    <!-- Main Viewport-Fitted Container (Zero Scrolling) -->
    <main class="flex-1 min-h-0 w-full p-2 flex flex-col overflow-hidden">
        @yield('content')
    </main>

    <!-- Share & Instant Mobile QR Modal -->
    <div x-show="showShareModal" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 sm:p-4 select-none" style="display: none;" @keydown.escape.window="showShareModal = false">
        <div class="bg-slate-900 border border-slate-700/80 rounded-2xl max-w-3xl w-full p-4 sm:p-6 shadow-2xl space-y-4 max-h-[92vh] flex flex-col overflow-hidden" @click.outside="showShareModal = false">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-blue-600/20 border border-blue-500/40 flex items-center justify-center text-blue-400 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-white flex items-center gap-2">
                            <span>Share Game & Live Feeds</span>
                            <span class="px-2 py-0.5 rounded bg-blue-950 border border-blue-800 text-[9px] font-mono text-blue-300 font-bold">QR CODES</span>
                        </h3>
                        <p class="text-xs text-slate-400 font-medium">
                            {{ $game->home_display_name ?? 'Home' }} vs {{ $game->away_display_name ?? 'Away' }} &bull; {{ strtoupper($game->sport ?? 'BASKETBALL') }}
                        </p>
                    </div>
                </div>

                <button @click="showShareModal = false" class="p-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition" title="Close (Esc)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Scrollable Content Area -->
            <div class="flex-1 overflow-y-auto pr-1 space-y-4">
                <!-- Prominent Game Code Banner -->
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-amber-500/40 p-4 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-3.5 text-center sm:text-left">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center shrink-0 text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-wider text-amber-300">Official Game Access Code</div>
                            <div class="text-2xl sm:text-3xl font-black font-mono tracking-widest text-white scoreboard-glow">
                                {{ $game->access_code ?? '------' }}
                            </div>
                            <div class="text-[11px] text-slate-400">
                                Enter code at spectator prompt or scan below for instant mobile access
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="copy('{{ $game->access_code ?? '' }}', 'code')" class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 shadow-sm" :class="copiedKey === 'code' ? 'bg-emerald-600 text-white' : 'bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40'">
                        <svg x-show="copiedKey !== 'code'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="copiedKey === 'code'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="copiedKey === 'code' ? 'Copied Code!' : 'Copy Code'"></span>
                    </button>
                </div>

                <!-- Three QR Code Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <!-- 1. Pure Stadium Scoreboard -->
                    <div class="rounded-xl bg-slate-950 border border-slate-800 p-3.5 flex flex-col justify-between space-y-3 hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between gap-1 mb-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-cyan-300">Scoreboard</h4>
                                </div>
                                <span class="px-1.5 py-0.5 rounded bg-cyan-950/80 border border-cyan-800/80 text-[8px] font-mono text-cyan-300 font-bold uppercase">Stadium</span>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug min-h-[32px]">
                                Real-time LED scoreboard with period, clock, team fouls & possession.
                            </p>
                        </div>

                        <!-- QR Code Container -->
                        <div class="bg-white p-2.5 rounded-xl shadow-inner mx-auto w-36 h-36 flex items-center justify-center border border-slate-200">
                            <div class="w-full h-full" x-html="getQr('{{ route('public.scoreboard', $game->access_code ?? '') }}')"></div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="flex items-center gap-1.5 pt-1">
                            <button type="button" @click="copy('{{ route('public.scoreboard', $game->access_code ?? '') }}', 'scoreboard')" class="flex-1 py-1.5 px-2 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[10px] font-bold text-slate-300 hover:text-white transition flex items-center justify-center gap-1">
                                <span x-text="copiedKey === 'scoreboard' ? 'Copied!' : 'Copy Link'"></span>
                            </button>
                            <a href="{{ route('public.scoreboard', $game->access_code ?? '') }}" target="_blank" class="py-1.5 px-2.5 rounded-lg bg-cyan-600/20 hover:bg-cyan-600/30 border border-cyan-500/40 text-[10px] font-black text-cyan-300 transition flex items-center gap-1" title="Open Scoreboard in new tab">
                                <span>Open</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- 2. Official Scorebook -->
                    <div class="rounded-xl bg-slate-950 border border-slate-800 p-3.5 flex flex-col justify-between space-y-3 hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between gap-1 mb-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-amber-300">Scorebook</h4>
                                </div>
                                <span class="px-1.5 py-0.5 rounded bg-amber-950/80 border border-amber-800/80 text-[8px] font-mono text-amber-300 font-bold uppercase">Official</span>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug min-h-[32px]">
                                Digital official scorebook ledger, running score table, box stats & signatures.
                            </p>
                        </div>

                        <!-- QR Code Container -->
                        <div class="bg-white p-2.5 rounded-xl shadow-inner mx-auto w-36 h-36 flex items-center justify-center border border-slate-200">
                            <div class="w-full h-full" x-html="getQr('{{ route('public.scorebook', $game->access_code ?? '') }}')"></div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="flex items-center gap-1.5 pt-1">
                            <button type="button" @click="copy('{{ route('public.scorebook', $game->access_code ?? '') }}', 'scorebook')" class="flex-1 py-1.5 px-2 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[10px] font-bold text-slate-300 hover:text-white transition flex items-center justify-center gap-1">
                                <span x-text="copiedKey === 'scorebook' ? 'Copied!' : 'Copy Link'"></span>
                            </button>
                            <a href="{{ route('public.scorebook', $game->access_code ?? '') }}" target="_blank" class="py-1.5 px-2.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/40 text-[10px] font-black text-amber-300 transition flex items-center gap-1" title="Open Scorebook in new tab">
                                <span>Open</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>

                    <!-- 3. Watch Live / Fan Experience -->
                    <div class="rounded-xl bg-slate-950 border border-slate-800 p-3.5 flex flex-col justify-between space-y-3 hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between gap-1 mb-2">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-blue-300">Watch Live</h4>
                                </div>
                                <span class="px-1.5 py-0.5 rounded bg-blue-950/80 border border-blue-800/80 text-[8px] font-mono text-blue-300 font-bold uppercase">Fan View</span>
                            </div>
                            <p class="text-[11px] text-slate-400 leading-snug min-h-[32px]">
                                Interactive spectator view with instant play-by-play feed & player stats.
                            </p>
                        </div>

                        <!-- QR Code Container -->
                        <div class="bg-white p-2.5 rounded-xl shadow-inner mx-auto w-36 h-36 flex items-center justify-center border border-slate-200">
                            <div class="w-full h-full" x-html="getQr('{{ route('public.live', $game->access_code ?? '') }}')"></div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="flex items-center gap-1.5 pt-1">
                            <button type="button" @click="copy('{{ route('public.live', $game->access_code ?? '') }}', 'live')" class="flex-1 py-1.5 px-2 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-[10px] font-bold text-slate-300 hover:text-white transition flex items-center justify-center gap-1">
                                <span x-text="copiedKey === 'live' ? 'Copied!' : 'Copy Link'"></span>
                            </button>
                            <a href="{{ route('public.live', $game->access_code ?? '') }}" target="_blank" class="py-1.5 px-2.5 rounded-lg bg-blue-600/20 hover:bg-blue-600/30 border border-blue-500/40 text-[10px] font-black text-blue-300 transition flex items-center gap-1" title="Open Watch Live in new tab">
                                <span>Open</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between pt-3 border-t border-slate-800 shrink-0">
                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Direct mobile scan &bull; No app install required</span>
                </div>

                <button @click="showShareModal = false" class="px-4 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs flex items-center gap-1.5 transition">
                    <span>Close</span>
                    <span class="px-1 py-0.2 rounded bg-slate-950 text-[9px] font-mono text-slate-400">Esc</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Hotkey & Flow Guide Modal -->
    <div x-show="showHelpModal" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-4 select-none" style="display: none;" @keydown.escape.window="showHelpModal = false">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-xl w-full p-5 shadow-2xl space-y-4" @click.outside="showHelpModal = false">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-800">
                <h3 class="text-sm font-black uppercase tracking-wider text-white">Operator Flow & Hotkeys</h3>
                <button @click="showHelpModal = false" class="p-1 rounded bg-slate-800 text-slate-400 hover:text-white" title="Close (Esc)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                    <h4 class="font-bold text-blue-400 uppercase text-[10px]">Touch / iPad / Mouse Flow</h4>
                    <p class="text-slate-300 text-[11px] leading-relaxed">
                        1. Tap any on-court player tile.<br>
                        2. Tap the stat action on the action pad.<br>
                        3. Stat is recorded with immediate score update.
                    </p>
                </div>

                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 space-y-1.5">
                    <h4 class="font-bold text-emerald-400 uppercase text-[10px]">Keyboard Hotkeys</h4>
                    <p class="text-slate-300 font-mono text-[11px] leading-relaxed">
                        &bull; Type Jersey # + <strong class="text-amber-300">-</strong> (HOME) or <strong class="text-amber-300">=</strong> (AWAY)<br>
                        &bull; Badges on buttons show their direct hotkey<br>
                        &bull; <strong class="text-amber-300">[</strong> / <strong class="text-amber-300">{</strong> : Home Score +1 / -1<br>
                        &bull; <strong class="text-amber-300">]</strong> / <strong class="text-amber-300">}</strong> : Away Score +1 / -1<br>
                        &bull; <strong class="text-amber-300">U</strong> or <strong class="text-amber-300">Ctrl+Z</strong> : Instant Undo<br>
                        &bull; <strong class="text-amber-300">H</strong> / <strong class="text-amber-300">A</strong> : Home / Away Timeouts<br>
                        &bull; <strong class="text-amber-300">R</strong> / <strong class="text-amber-300">⇧R</strong> : Home / Away Roster Editor (1-9 jump, 0 empty)<br>
                        &bull; <strong class="text-amber-300">Esc</strong> : Instant Reset / Close Any Modal
                    </p>
                </div>
            </div>

            <div class="flex justify-end pt-1">
                <button @click="showHelpModal = false" class="px-4 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs flex items-center gap-1.5">
                    <span>Close</span>
                    <span class="px-1 py-0.2 rounded bg-blue-950 text-[9px] font-mono text-blue-200">Esc</span>
                </button>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>

