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
    }
}" @play-sound.window="playSound($event.detail)" @close-all-modals.window="showHelpModal = false">

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
                <a href="{{ route('public.scorebook', $game->access_code ?? '') }}" target="_blank" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-[11px] font-black transition gap-1.5 shadow-sm" title="Official NCAA Men's Basketball Scorebook (Printable)">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span>NCAA Scorebook</span>
                </a>
            @endif

            <a href="{{ route('public.live', $game->access_code ?? '') }}" target="_blank" class="hidden sm:inline-flex items-center px-2 py-1 rounded-lg bg-blue-600/20 hover:bg-blue-600/30 text-blue-300 border border-blue-500/30 text-[11px] font-bold transition">
                <span>Fan Live</span>
            </a>
        </div>
    </header>

    <!-- Main Viewport-Fitted Container (Zero Scrolling) -->
    <main class="flex-1 min-h-0 w-full p-2 flex flex-col overflow-hidden">
        @yield('content')
    </main>

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
