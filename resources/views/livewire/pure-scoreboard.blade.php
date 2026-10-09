<div>
    @if (!$game)
        <!-- Game Access Code Prompt Screen -->
        <div class="min-h-[85vh] flex items-center justify-center p-4">
            <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="text-center space-y-2">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-400 mb-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white">Live Stadium Scoreboard</h1>
                    <p class="text-xs text-slate-400">Enter a 6-digit game code to launch the real-time scoreboard view.</p>
                </div>

                @if ($errorMessage)
                    <div class="p-3 rounded-xl bg-rose-950/80 border border-rose-800 text-rose-300 text-xs font-semibold text-center animate-fade-in">
                        {{ $errorMessage }}
                    </div>
                @endif

                <form wire:submit.prevent="submitCode" class="space-y-4">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 text-center">
                            Game Access Code
                        </label>
                        <input type="text"
                               wire:model="inputCode"
                               maxlength="10"
                               placeholder="e.g. BSK001"
                               class="w-full text-center tracking-widest text-2xl font-mono font-black uppercase py-3.5 px-4 rounded-2xl bg-slate-950 border border-slate-700 text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-inner">
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-black text-sm tracking-wide transition shadow-lg shadow-blue-600/30 cursor-pointer">
                        Launch Scoreboard &rarr;
                    </button>
                </form>
            </div>
        </div>
    @else
        @php
            $homeColor = $game->home_team_score_color ?? '#1e40af';
            $awayColor = $game->away_team_score_color ?? '#b91c1c';
            $isFlipped = (bool)($game->settings['broadcast_flip'] ?? false);
        @endphp

        <!-- Live Pure Scoreboard Display -->
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-6 py-4 space-y-4 select-none" wire:poll.1000ms
             x-data="{
                 isFullscreen: false,
                 toggleFullscreen() {
                     if (!document.fullscreenElement) {
                         document.documentElement.requestFullscreen?.().catch(() => {});
                         this.isFullscreen = true;
                     } else {
                         document.exitFullscreen?.().catch(() => {});
                         this.isFullscreen = false;
                     }
                 }
             }">

            <!-- Scoreboard Utility Header -->
            <div class="flex items-center justify-between bg-slate-900/90 border border-slate-800 rounded-2xl px-4 py-2 text-xs">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('public.scoreboard.prompt') }}" class="text-slate-400 hover:text-white flex items-center gap-1 font-bold text-[11px] transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Change Game</span>
                    </a>
                    <div class="h-3.5 w-px bg-slate-800"></div>
                    <span class="px-2 py-0.5 rounded bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 font-mono text-[10px] font-black flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        LIVE
                    </span>
                    <span class="font-mono text-[11px] text-slate-400 hidden sm:inline">
                        CODE: <strong class="text-white">{{ $game->access_code }}</strong>
                    </span>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('public.watch', $game->access_code) }}" class="px-3 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-blue-300 hover:text-white font-bold text-xs transition border border-slate-700 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>Watch View</span>
                    </a>

                    <button type="button" @click="toggleFullscreen()" class="px-3 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs transition border border-slate-700 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0 0l-5-5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        <span class="hidden sm:inline" x-text="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"></span>
                    </button>
                </div>
            </div>

            <!-- Stadium LED Main Scoreboard Container -->
            <div class="bg-slate-950 border-2 border-slate-800 rounded-3xl p-5 sm:p-8 md:p-10 shadow-2xl relative overflow-hidden space-y-6">
                
                <!-- Main Header: Matchup, Scores & Center Period Box -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6 items-center">
                    
                    <!-- HOME TEAM CARD -->
                    <div class="md:col-span-5 bg-slate-900/90 rounded-3xl p-5 sm:p-6 shadow-xl relative overflow-hidden flex flex-col justify-between {{ $isFlipped ? 'order-3 md:order-3' : 'order-1 md:order-1' }}"
                         style="border: 2px solid {{ $homeColor }}80; box-shadow: 0 10px 25px -5px {{ $homeColor }}20;">
                        <!-- Top Team Bar with Possession Indicator -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center space-x-2">
                                <span class="w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: {{ $homeColor }}; box-shadow: 0 0 8px {{ $homeColor }};"></span>
                                <span class="text-xs font-black uppercase tracking-widest" style="color: {{ $homeColor }};">HOME</span>
                            </div>

                            @if ($game->sport === 'basketball' && $game->possession_arrow === 'home')
                                <div class="px-3 py-1 rounded-full bg-amber-500 text-slate-950 font-black text-xs tracking-wider uppercase flex items-center gap-1 shadow-lg shadow-amber-500/40 animate-pulse">
                                    <span>&bull; POSS &bull;</span>
                                </div>
                            @elseif ($game->sport === 'volleyball' && $game->current_server === 'home')
                                <div class="px-3 py-1 rounded-full bg-emerald-500 text-slate-950 font-black text-xs tracking-wider uppercase flex items-center gap-1 shadow-lg shadow-emerald-500/40 animate-pulse">
                                    <span>&bull; SERVING &bull;</span>
                                </div>
                            @endif
                        </div>

                        <!-- Team Name & Score -->
                        <div class="my-4 flex items-center justify-between gap-4">
                            <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight truncate">{{ $game->home_display_name }}</h2>
                            <span class="font-mono text-6xl sm:text-8xl md:text-9xl font-black text-white tracking-tighter shrink-0 text-right drop-shadow-md">
                                {{ $game->home_score }}
                            </span>
                        </div>

                        <!-- Team Fouls & Timeouts Remaining -->
                        <div class="pt-3 border-t border-slate-800 grid grid-cols-2 gap-3 text-xs">
                            @if ($game->sport === 'basketball')
                                <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Team Fouls</div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xl font-mono font-black text-white">{{ $game->home_fouls_current_period }}</span>
                                        @if ($game->isDoubleBonus('home'))
                                            <span class="px-1.5 py-0.5 rounded bg-rose-500/20 border border-rose-500/40 text-rose-300 font-bold text-[9px]">DBL BONUS</span>
                                        @elseif ($game->isBonus('home'))
                                            <span class="px-1.5 py-0.5 rounded bg-amber-500/20 border border-amber-500/40 text-amber-300 font-bold text-[9px]">BONUS</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Sets Won</div>
                                    <span class="text-xl font-mono font-black text-emerald-400">{{ $game->home_score_period_1 > $game->away_score_period_1 ? 1 : 0 }}</span>
                                </div>
                            @endif

                            <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Timeouts Left</div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xl font-mono font-black text-amber-300">{{ $game->home_timeouts_remaining }}</span>
                                    <div class="flex space-x-1">
                                        @for ($i = 0; $i < $game->home_timeouts_remaining; $i++)
                                            <span class="w-2 h-4 rounded-sm bg-amber-400"></span>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CENTER PERIOD & STADIUM CLOCK / STATUS -->
                    <div class="md:col-span-2 order-2 md:order-2 flex flex-col items-center justify-center text-center space-y-3 p-4 rounded-3xl bg-slate-900 border border-slate-800 shadow-lg">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                            {{ ucfirst($game->sport) }}
                        </span>

                        <div class="py-2.5 px-4 rounded-2xl bg-slate-950 border border-slate-700/80 shadow-inner w-full">
                            <span class="text-lg sm:text-xl font-mono font-black uppercase tracking-wider text-white">
                                {{ $game->period_name }}
                            </span>
                        </div>

                        <!-- Possession Arrow Visualizer -->
                        @if ($game->sport === 'basketball')
                            <div class="p-2 rounded-xl bg-slate-950 border border-slate-800 w-full flex items-center justify-between text-xs font-mono font-bold">
                                @if (!$isFlipped)
                                    <span class="px-2 py-0.5 rounded transition text-white shadow" style="{{ $game->possession_arrow === 'home' ? 'background-color: ' . $homeColor . ';' : 'color: rgb(100 116 139); background-color: transparent;' }}">&larr; HOME</span>
                                    <span class="px-2 py-0.5 rounded transition text-white shadow" style="{{ $game->possession_arrow === 'away' ? 'background-color: ' . $awayColor . ';' : 'color: rgb(100 116 139); background-color: transparent;' }}">AWAY &rarr;</span>
                                @else
                                    <span class="px-2 py-0.5 rounded transition text-white shadow" style="{{ $game->possession_arrow === 'away' ? 'background-color: ' . $awayColor . ';' : 'color: rgb(100 116 139); background-color: transparent;' }}">&larr; AWAY</span>
                                    <span class="px-2 py-0.5 rounded transition text-white shadow" style="{{ $game->possession_arrow === 'home' ? 'background-color: ' . $homeColor . ';' : 'color: rgb(100 116 139); background-color: transparent;' }}">HOME &rarr;</span>
                                @endif
                            </div>
                        @else
                            <div class="p-2 rounded-xl bg-slate-950 border border-slate-800 w-full text-center text-xs font-mono">
                                <span class="text-[10px] text-slate-400">SERVING:</span>
                                <strong class="ml-1 uppercase" style="color: {{ $game->current_server === 'home' ? $homeColor : $awayColor }};">{{ $game->current_server }}</strong>
                            </div>
                        @endif

                        <div class="text-[10px] font-mono text-slate-500">
                            {{ $game->venue ?: 'Main Arena' }}
                        </div>
                    </div>

                    <!-- AWAY TEAM CARD -->
                    <div class="md:col-span-5 bg-slate-900/90 rounded-3xl p-5 sm:p-6 shadow-xl relative overflow-hidden flex flex-col justify-between {{ $isFlipped ? 'order-1 md:order-1' : 'order-3 md:order-3' }}"
                         style="border: 2px solid {{ $awayColor }}80; box-shadow: 0 10px 25px -5px {{ $awayColor }}20;">
                        <!-- Top Team Bar with Possession Indicator -->
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            @if ($game->sport === 'basketball' && $game->possession_arrow === 'away')
                                <div class="px-3 py-1 rounded-full bg-amber-500 text-slate-950 font-black text-xs tracking-wider uppercase flex items-center gap-1 shadow-lg shadow-amber-500/40 animate-pulse">
                                    <span>&bull; POSS &bull;</span>
                                </div>
                            @elseif ($game->sport === 'volleyball' && $game->current_server === 'away')
                                <div class="px-3 py-1 rounded-full bg-emerald-500 text-slate-950 font-black text-xs tracking-wider uppercase flex items-center gap-1 shadow-lg shadow-emerald-500/40 animate-pulse">
                                    <span>&bull; SERVING &bull;</span>
                                </div>
                            @else
                                <div></div>
                            @endif

                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-black uppercase tracking-widest" style="color: {{ $awayColor }};">AWAY</span>
                                <span class="w-3.5 h-3.5 rounded-full shadow-sm" style="background-color: {{ $awayColor }}; box-shadow: 0 0 8px {{ $awayColor }};"></span>
                            </div>
                        </div>

                        <!-- Team Name & Score -->
                        <div class="my-4 flex items-center justify-between gap-4">
                            <span class="font-mono text-6xl sm:text-8xl md:text-9xl font-black text-white tracking-tighter shrink-0 text-left drop-shadow-md">
                                {{ $game->away_score }}
                            </span>
                            <h2 class="text-2xl sm:text-4xl font-black text-white tracking-tight truncate text-right">{{ $game->away_display_name }}</h2>
                        </div>

                        <!-- Team Fouls & Timeouts Remaining -->
                        <div class="pt-3 border-t border-slate-800 grid grid-cols-2 gap-3 text-xs">
                            <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1">
                                <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Timeouts Left</div>
                                <div class="flex items-center justify-end space-x-2">
                                    <div class="flex space-x-1">
                                        @for ($i = 0; $i < $game->away_timeouts_remaining; $i++)
                                            <span class="w-2 h-4 rounded-sm bg-amber-400"></span>
                                        @endfor
                                    </div>
                                    <span class="text-xl font-mono font-black text-amber-300">{{ $game->away_timeouts_remaining }}</span>
                                </div>
                            </div>

                            @if ($game->sport === 'basketball')
                                <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1 text-right">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Team Fouls</div>
                                    <div class="flex items-center justify-end space-x-2">
                                        @if ($game->isDoubleBonus('away'))
                                            <span class="px-1.5 py-0.5 rounded bg-rose-500/20 border border-rose-500/40 text-rose-300 font-bold text-[9px]">DBL BONUS</span>
                                        @elseif ($game->isBonus('away'))
                                            <span class="px-1.5 py-0.5 rounded bg-amber-500/20 border border-amber-500/40 text-amber-300 font-bold text-[9px]">BONUS</span>
                                        @endif
                                        <span class="text-xl font-mono font-black text-white">{{ $game->away_fouls_current_period }}</span>
                                    </div>
                                </div>
                            @else
                                <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-1 text-right">
                                    <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Sets Won</div>
                                    <span class="text-xl font-mono font-black text-emerald-400">{{ $game->away_score_period_1 > $game->home_score_period_1 ? 1 : 0 }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Period-by-Period Score Strip -->
                @if (isset($periodScores) && count($periodScores) > 0)
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3 overflow-x-auto">
                        <table class="w-full text-center text-xs font-mono">
                            <thead>
                                <tr class="text-slate-400 border-b border-slate-800 text-[10px] uppercase">
                                    <th class="py-1 px-3 text-left font-sans font-bold">Team</th>
                                    @foreach ($periodScores as $ps)
                                        <th class="py-1 px-3 {{ $ps['is_current'] ? 'text-amber-300 font-bold' : '' }}">
                                            {{ $ps['name'] }}
                                        </th>
                                    @endforeach
                                    <th class="py-1 px-3 text-right font-sans font-black text-white">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 font-bold">
                                <tr>
                                    <td class="py-2 px-3 text-left font-sans font-black truncate max-w-[120px]" style="color: {{ $homeColor }};">
                                        {{ $game->home_display_name }}
                                    </td>
                                    @foreach ($periodScores as $ps)
                                        <td class="py-2 px-3 text-slate-200 {{ $ps['is_current'] ? 'bg-slate-950/80 text-white font-black' : '' }}">
                                            {{ $ps['home'] }}
                                        </td>
                                    @endforeach
                                    <td class="py-2 px-3 text-right text-lg font-black text-white">
                                        {{ $game->home_score }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 text-left font-sans font-black truncate max-w-[120px]" style="color: {{ $awayColor }};">
                                        {{ $game->away_display_name }}
                                    </td>
                                    @foreach ($periodScores as $ps)
                                        <td class="py-2 px-3 text-slate-200 {{ $ps['is_current'] ? 'bg-slate-950/80 text-white font-black' : '' }}">
                                            {{ $ps['away'] }}
                                        </td>
                                    @endforeach
                                    <td class="py-2 px-3 text-right text-lg font-black text-white">
                                        {{ $game->away_score }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
