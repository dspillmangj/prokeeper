<div class="w-full h-full max-h-full flex-1 min-h-0 flex flex-col justify-between overflow-hidden select-none" 
     wire:ignore.self
     x-data="ProKeeperEngine.createOperator('basketball', {
         gameId: {{ $game->id }},
         homeTeamName: '{{ addslashes($game->home_display_name) }}',
         awayTeamName: '{{ addslashes($game->away_display_name) }}',
         homeScore: {{ (int)$game->home_score }},
         awayScore: {{ (int)$game->away_score }},
         homePeriodScores: @js($game->home_period_scores ?? [0, 0, 0, 0]),
         awayPeriodScores: @js($game->away_period_scores ?? [0, 0, 0, 0]),
         currentPeriod: {{ (int)$game->current_period }},
         periodName: '{{ $game->period_name }}',
         possession: '{{ $game->possession_arrow ?? 'home' }}',
         homeFouls: {{ (int)$game->home_fouls_current_period }},
         awayFouls: {{ (int)$game->away_fouls_current_period }},
         homeTimeouts: {{ (int)$game->home_timeouts_remaining }},
         awayTimeouts: {{ (int)$game->away_timeouts_remaining }},
         homeCourt: @js($homeLineupOnCourt),
         awayCourt: @js($awayLineupOnCourt),
         homeBench: @js($homeBench),
         awayBench: @js($awayBench),
         recentEvents: @js($recentEvents),
     })">

    <!-- 1. TOP SCOREBOARD BAR (Compact, Zero-Waste Height) -->
    <div class="bg-slate-900 border-b border-slate-800 p-2 sm:p-3 shrink-0 shadow-lg rounded-2xl mb-1">
        <div class="grid grid-cols-12 gap-2 items-center">

            <!-- HOME SCORE BLOCK -->
            <div class="col-span-5 flex items-center justify-between p-2 sm:p-2.5 rounded-xl border transition-all"
                 :class="possession === 'home' ? 'bg-blue-950/60 border-blue-500/80 shadow-md shadow-blue-500/20' : 'bg-slate-950 border-slate-800'">
                <div class="space-y-0.5 truncate mr-2">
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <span class="text-[10px] font-black uppercase tracking-wider text-blue-400">HOME</span>
                        <template x-if="possession === 'home'">
                            <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 text-[9px] font-black tracking-wider uppercase">POSS</span>
                        </template>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-white truncate leading-tight">{{ $game->home_display_name }}</h2>
                    <div class="flex items-center space-x-2 text-[10px] text-slate-400 font-mono">
                        <span>FOULS: <strong class="text-white" x-text="homeFouls"></strong></span>
                        <span x-show="homeFouls >= 5" class="px-1 rounded bg-rose-950 text-rose-300 font-bold border border-rose-800 text-[9px] animate-pulse">BONUS</span>
                        <span>TO: <strong class="text-white" x-text="homeTimeouts"></strong></span>
                    </div>
                </div>

                <!-- Score Counter & Direct Touch +/- -->
                <div class="flex items-center space-x-1.5 shrink-0">
                    <div class="flex flex-col space-y-1">
                        <button @click="adjustScoreFast('home', 1)" class="w-8 h-7 sm:w-9 sm:h-8 rounded-lg bg-blue-600 hover:bg-blue-500 touch-active text-white font-black text-xs flex items-center justify-center gap-1 shadow transition" title="+1 Home ([)">
                            <span>+1</span>
                            <span class="px-1 py-0.2 rounded bg-black/40 border border-white/20 text-[8px] font-mono font-bold text-amber-300 leading-none">[</span>
                        </button>
                        <button @click="adjustScoreFast('home', -1)" class="w-8 h-6 sm:w-9 sm:h-6 rounded-lg bg-slate-800 hover:bg-slate-700 touch-active text-slate-300 font-bold text-[10px] flex items-center justify-center gap-1 transition" title="-1 Home ({)">
                            <span>-1</span>
                            <span class="px-1 py-0.2 rounded bg-black/40 border border-white/20 text-[8px] font-mono font-bold text-amber-300 leading-none">{</span>
                        </button>
                    </div>
                    <div class="min-w-[45px] sm:min-w-[55px] text-right">
                        <span class="font-mono text-3xl sm:text-4xl font-black text-white tracking-tighter leading-none scoreboard-glow" x-text="homeScore"></span>
                    </div>
                </div>
            </div>

            <!-- CENTER PERIOD SUMMARY & STATUS -->
            <div class="col-span-2 flex flex-col items-center justify-center text-center space-y-1">
                <div class="flex items-center gap-1.5">
                    <span class="px-2 py-0.5 rounded-full bg-slate-800 text-blue-300 text-[10px] font-black uppercase tracking-widest border border-slate-700" x-text="periodName">
                        {{ $game->period_name }}
                    </span>

                    <!-- Quick Play-by-Play Trigger -->
                    <button @click="openPlayByPlayModal()" 
                            class="px-2 py-0.5 rounded-full bg-slate-800 hover:bg-slate-700 border border-slate-700 hover:border-slate-500 text-amber-300 text-[9px] font-bold transition flex items-center gap-1 shadow-sm touch-active"
                            title="Play-by-Play Log (Hotkey: L)">
                        <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span>Plays</span>
                        <span class="px-1 py-0.2 rounded bg-slate-950 text-white font-mono text-[8px] font-black" x-text="recentEvents.length"></span>
                    </button>
                </div>

                <!-- Period Score History Pills (Interactive, Max 6 Periods) -->
                <div class="flex items-center flex-wrap justify-center gap-1 text-[9px] font-mono">
                    <template x-for="(s, idx) in homePeriodScores.slice(0, 6)" :key="idx">
                        <button type="button"
                                @click="setPeriodFast(idx + 1)"
                                class="px-2 py-0.5 rounded transition touch-active font-mono cursor-pointer"
                                :class="(idx + 1) === currentPeriod 
                                    ? 'bg-blue-600 text-white font-bold ring-1 ring-white/50 shadow' 
                                    : 'bg-slate-950 text-slate-400 hover:text-white hover:bg-slate-800 border border-slate-800'"
                                :title="'Click to switch to ' + ((idx + 1) <= 4 ? 'Q' + (idx + 1) : 'OT' + (idx + 1 - 4))"
                                x-text="((idx + 1) <= 4 ? 'Q' + (idx + 1) : 'OT' + (idx + 1 - 4)) + ': ' + s + '-' + (awayPeriodScores[idx] || 0)">
                        </button>
                    </template>
                </div>

                <!-- Real-time 0ms Local Status Indicator -->
                <div class="flex items-center justify-center space-x-1 text-[9px] font-semibold text-slate-300 truncate max-w-full px-1">
                    <span class="w-1.5 h-1.5 rounded-full"
                          :class="syncStatus === 'synced' ? 'bg-emerald-400' : (syncStatus === 'syncing' ? 'bg-amber-400 animate-pulse' : 'bg-rose-400')">
                    </span>
                    <span x-text="feedbackMessage" class="truncate"></span>
                </div>
            </div>

            <!-- AWAY SCORE BLOCK -->
            <div class="col-span-5 flex items-center justify-between p-2 sm:p-2.5 rounded-xl border transition-all"
                 :class="possession === 'away' ? 'bg-rose-950/60 border-rose-500/80 shadow-md shadow-rose-500/20' : 'bg-slate-950 border-slate-800'">
                <!-- Score Counter & Direct Touch +/- -->
                <div class="flex items-center space-x-1.5 shrink-0">
                    <div class="min-w-[45px] sm:min-w-[55px] text-left">
                        <span class="font-mono text-3xl sm:text-4xl font-black text-white tracking-tighter leading-none scoreboard-glow" x-text="awayScore"></span>
                    </div>
                    <div class="flex flex-col space-y-1">
                        <button @click="adjustScoreFast('away', 1)" class="w-8 h-7 sm:w-9 sm:h-8 rounded-lg bg-rose-600 hover:bg-rose-500 touch-active text-white font-black text-xs flex items-center justify-center gap-1 shadow transition" title="+1 Away (])">
                            <span>+1</span>
                            <span class="px-1 py-0.2 rounded bg-black/40 border border-white/20 text-[8px] font-mono font-bold text-amber-300 leading-none">]</span>
                        </button>
                        <button @click="adjustScoreFast('away', -1)" class="w-8 h-6 sm:w-9 sm:h-6 rounded-lg bg-slate-800 hover:bg-slate-700 touch-active text-slate-300 font-bold text-[10px] flex items-center justify-center gap-1 transition" title="-1 Away (})">
                            <span>-1</span>
                            <span class="px-1 py-0.2 rounded bg-black/40 border border-white/20 text-[8px] font-mono font-bold text-amber-300 leading-none">}</span>
                        </button>
                    </div>
                </div>

                <div class="space-y-0.5 truncate text-right ml-2">
                    <div class="flex items-center justify-end space-x-1.5">
                        <template x-if="possession === 'away'">
                            <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 text-[9px] font-black tracking-wider uppercase">POSS</span>
                        </template>
                        <span class="text-[10px] font-black uppercase tracking-wider text-rose-400">AWAY</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-white truncate leading-tight">{{ $game->away_display_name }}</h2>
                    <div class="flex items-center justify-end space-x-2 text-[10px] text-slate-400 font-mono">
                        <span>TO: <strong class="text-white" x-text="awayTimeouts"></strong></span>
                        <span x-show="awayFouls >= 5" class="px-1 rounded bg-rose-950 text-rose-300 font-bold border border-rose-800 text-[9px] animate-pulse">BONUS</span>
                        <span>FOULS: <strong class="text-white" x-text="awayFouls"></strong></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 2. CENTER TIER: LARGE ACTIVE PLAYERS GRID (50/50 Arena Split, Zero Scroll) -->
    <div class="flex-1 min-h-0 grid grid-cols-2 gap-2 my-1 overflow-hidden">
        
        <!-- HOME ACTIVE PLAYERS (5 LARGE BUTTONS) -->
        <div class="flex flex-col h-full bg-slate-900/90 border border-slate-800 rounded-2xl p-2 overflow-hidden shadow-inner">
            <div class="flex items-center justify-between pb-1 mb-1 border-b border-slate-800 text-[11px] font-bold text-blue-400">
                <span class="uppercase tracking-wider truncate">{{ $game->home_display_name }} (Court)</span>
                
                <div class="flex items-center space-x-1.5">
                    <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">5 On Court</span>
                    <button type="button" @click="@this.openRosterModal('home')" class="px-2 py-0.5 rounded-lg bg-blue-950/90 hover:bg-blue-900 border border-blue-800 text-blue-300 hover:text-white text-[10px] font-bold transition flex items-center gap-1 shadow-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Edit Roster</span>
                    </button>
                </div>
            </div>

            <!-- 5 Large Touch Buttons Grid (2 cols x 3 rows with 5th player) -->
            <div class="flex-1 min-h-0 grid grid-cols-2 grid-rows-3 gap-1.5">
                <template x-for="(player, idx) in homeCourt" :key="player.id || idx">
                    <button @click="openActionPad('home', player.jersey_number, player.player_name, player.id)"
                            type="button"
                            class="h-full w-full rounded-xl border-2 transition-all p-2 flex flex-col justify-between items-center text-center touch-active group"
                            :class="[
                                idx === 4 ? 'col-span-2' : '',
                                selectedPlayer && selectedPlayer.side === 'home' && selectedPlayer.jersey == player.jersey_number
                                    ? 'bg-blue-600 border-white shadow-lg shadow-blue-500/40 ring-2 ring-blue-300' 
                                    : 'bg-slate-950/90 hover:bg-blue-950/50 border-slate-800/90 hover:border-blue-500/60'
                            ]">
                        
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400 group-hover:text-blue-300">
                            <span class="font-bold" x-text="'#' + (idx + 1)"></span>
                            <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="player.position || 'G/F'"></span>
                        </div>

                        <!-- Massive Jersey Number -->
                        <div class="font-mono text-2xl sm:text-3xl lg:text-4xl font-black text-white leading-none tracking-tight" x-text="'#' + player.jersey_number">
                        </div>

                        <div class="w-full text-xs font-bold text-slate-200 truncate leading-none" x-text="player.player_name">
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <!-- AWAY ACTIVE PLAYERS (5 LARGE BUTTONS) -->
        <div class="flex flex-col h-full bg-slate-900/90 border border-slate-800 rounded-2xl p-2 overflow-hidden shadow-inner">
            <div class="flex items-center justify-between pb-1 mb-1 border-b border-slate-800 text-[11px] font-bold text-rose-400">
                <span class="uppercase tracking-wider truncate">{{ $game->away_display_name }} (Court)</span>

                <div class="flex items-center space-x-1.5">
                    <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">5 On Court</span>
                    <button type="button" @click="@this.openRosterModal('away')" class="px-2 py-0.5 rounded-lg bg-rose-950/90 hover:bg-rose-900 border border-rose-800 text-rose-300 hover:text-white text-[10px] font-bold transition flex items-center gap-1 shadow-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Edit Roster</span>
                    </button>
                </div>
            </div>

            <!-- 5 Large Touch Buttons Grid (2 cols x 3 rows with 5th player) -->
            <div class="flex-1 min-h-0 grid grid-cols-2 grid-rows-3 gap-1.5">
                <template x-for="(player, idx) in awayCourt" :key="player.id || idx">
                    <button @click="openActionPad('away', player.jersey_number, player.player_name, player.id)"
                            type="button"
                            class="h-full w-full rounded-xl border-2 transition-all p-2 flex flex-col justify-between items-center text-center touch-active group"
                            :class="[
                                idx === 4 ? 'col-span-2' : '',
                                selectedPlayer && selectedPlayer.side === 'away' && selectedPlayer.jersey == player.jersey_number
                                    ? 'bg-rose-600 border-white shadow-lg shadow-rose-500/40 ring-2 ring-rose-300' 
                                    : 'bg-slate-950/90 hover:bg-rose-950/50 border-slate-800/90 hover:border-rose-500/60'
                            ]">
                        
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400 group-hover:text-rose-300">
                            <span class="font-bold" x-text="'#' + (idx + 1)"></span>
                            <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="player.position || 'G/F'"></span>
                        </div>

                        <!-- Massive Jersey Number -->
                        <div class="font-mono text-2xl sm:text-3xl lg:text-4xl font-black text-white leading-none tracking-tight" x-text="'#' + player.jersey_number">
                        </div>

                        <div class="w-full text-xs font-bold text-slate-200 truncate leading-none" x-text="player.player_name">
                        </div>
                    </button>
                </template>
            </div>
        </div>

    </div>

    <!-- 3. BOTTOM TIER: NON-PLAYER-SCOPED ACTIONS DOCK (Fixed Height, Tactile Control) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2 shrink-0 shadow-xl mt-auto">
        <div class="grid grid-cols-4 sm:grid-cols-7 gap-1.5 text-xs font-bold">
            
            <!-- HOME TIMEOUT -->
            <button @click="callTimeoutFast('home')" 
                    class="p-2 rounded-xl bg-slate-950 hover:bg-slate-800 touch-active border border-blue-800/60 text-blue-300 flex flex-col items-center justify-center">
                <div class="flex items-center gap-1">
                    <span class="text-[10px] font-black uppercase">HOME TO</span>
                    <span class="px-1 py-0.2 rounded bg-blue-950/80 border border-blue-500/40 text-[8px] font-mono font-bold text-amber-300">H</span>
                </div>
                <span class="text-[9px] text-slate-400 font-mono" x-text="homeTimeouts + ' Left'"></span>
            </button>

            <!-- TOGGLE POSSESSION ARROW -->
            <button @click="togglePossession()" 
                    class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-700 text-amber-300 flex flex-col items-center justify-center shadow">
                <div class="flex items-center gap-1">
                    <span class="text-[10px] font-black uppercase">FLIP POSS</span>
                    <span class="px-1 py-0.2 rounded bg-slate-950/80 border border-slate-600 text-[8px] font-mono font-bold text-amber-300">P</span>
                </div>
                <span class="text-[9px] font-mono text-white" x-text="possession.toUpperCase() + ' ARROW'"></span>
            </button>

            <!-- ADVANCE PERIOD (MAX 6) -->
            <button @click="nextPeriodFast()" 
                    :disabled="currentPeriod >= 6"
                    class="p-2 rounded-xl touch-active text-white flex flex-col items-center justify-center transition"
                    :class="currentPeriod >= 6 ? 'bg-slate-800 text-slate-500 cursor-not-allowed opacity-60 border border-slate-700' : 'bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/30'">
                <div class="flex items-center gap-1">
                    <span class="text-[10px] font-black uppercase">+ NEXT PERIOD</span>
                    <span class="px-1 py-0.2 rounded bg-emerald-950/80 border border-emerald-400/50 text-[8px] font-mono font-bold text-amber-300" x-show="currentPeriod < 6">N</span>
                </div>
                <span class="text-[9px] font-mono" :class="currentPeriod >= 6 ? 'text-slate-400' : 'text-emerald-100'" x-text="currentPeriod >= 6 ? 'Max (OT2 / Period 6)' : ((currentPeriod + 1) <= 4 ? 'Period ' + (currentPeriod + 1) : 'OT' + (currentPeriod + 1 - 4))"></span>
            </button>

            <!-- SUBSTITUTION DOCK BUTTON (LINE UP & SEND IN) -->
            <template x-if="pendingSubCount() === 0">
                <button @click="openLineupModal()" 
                        class="p-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 touch-active text-white flex flex-col items-center justify-center shadow-lg shadow-indigo-600/30">
                    <div class="flex items-center gap-1">
                        <span class="text-[10px] font-black uppercase">SUBSTITUTE</span>
                        <span class="px-1 py-0.2 rounded bg-indigo-950/80 border border-indigo-400/50 text-[8px] font-mono font-bold text-amber-300">S</span>
                    </div>
                    <span class="text-[9px] text-indigo-200 font-mono">Line Up Subs</span>
                </button>
            </template>
            <template x-if="pendingSubCount() > 0">
                <div class="p-0.5 rounded-xl bg-indigo-950 border border-amber-400/80 shadow-lg shadow-indigo-950/60 flex gap-1 items-stretch">
                    <!-- Left Area: Open Lineup to add/modify pending subs -->
                    <button @click="openLineupModal()" 
                            class="flex-1 py-1 px-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 touch-active text-white flex flex-col items-center justify-center transition">
                        <div class="flex items-center gap-0.5">
                            <span class="text-[9px] font-black uppercase">+ LINE UP</span>
                            <span class="px-1 py-0.2 rounded bg-indigo-950 border border-indigo-400 text-[7px] font-mono font-bold text-amber-300">S</span>
                        </div>
                        <span class="text-[8px] font-mono text-amber-300 font-bold" x-text="pendingSubCount() + ' Lined Up'"></span>
                    </button>

                    <!-- Dedicated Action: SEND IN -->
                    <button @click.stop="openExecuteSubModal()" 
                            class="py-1 px-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 touch-active text-white flex flex-col items-center justify-center transition shadow-md shadow-emerald-500/50">
                        <span class="text-[9px] font-black uppercase tracking-tight text-white flex items-center gap-0.5 leading-tight">
                            SEND IN <span x-text="'(' + pendingSubCount() + ')'"></span>
                        </span>
                        <span class="text-[8px] font-bold text-emerald-100 uppercase tracking-tight leading-tight">Enter Game</span>
                    </button>
                </div>
            </template>

            <!-- MANAGE ROSTERS ON THE FLY -->
            <button @click="@this.openRosterModal('home'); playSound('tap')" 
                    class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-600 text-cyan-300 flex flex-col items-center justify-center shadow">
                <div class="flex items-center gap-1">
                    <span class="text-[10px] font-black uppercase">ROSTERS</span>
                    <span class="px-1 py-0.2 rounded bg-slate-950/80 border border-slate-600 text-[8px] font-mono font-bold text-amber-300">R</span>
                </div>
                <span class="text-[9px] text-slate-300 font-mono">Add / Edit</span>
            </button>

            <!-- AWAY TIMEOUT -->
            <button @click="callTimeoutFast('away')" 
                    class="p-2 rounded-xl bg-slate-950 hover:bg-slate-800 touch-active border border-rose-800/60 text-rose-300 flex flex-col items-center justify-center">
                <div class="flex items-center gap-1">
                    <span class="text-[10px] font-black uppercase">AWAY TO</span>
                    <span class="px-1 py-0.2 rounded bg-rose-950/80 border border-rose-500/40 text-[8px] font-mono font-bold text-amber-300">A</span>
                </div>
                <span class="text-[9px] text-slate-400 font-mono" x-text="awayTimeouts + ' Left'"></span>
            </button>

            <!-- UNDO LAST PLAY -->
            <button @click="undo()" 
                    class="p-2 rounded-xl bg-rose-950/80 hover:bg-rose-900 touch-active border border-rose-800 text-rose-300 flex flex-col items-center justify-center shadow">
                <div class="flex items-center gap-1">
                    <span class="text-[10px] font-black uppercase">UNDO LAST</span>
                    <span class="px-1 py-0.2 rounded bg-black/60 border border-rose-500/40 text-[8px] font-mono font-bold text-amber-300">U</span>
                </div>
                <span class="text-[9px] text-rose-400 font-mono">Undo</span>
            </button>

        </div>
    </div>

    <!-- INSTANT STAT ACTION MODAL OVERLAY (0ms Client-Side Trigger) -->
    <template x-if="selectedPlayer">
        <div class="fixed inset-0 bg-black/85 backdrop-blur-sm z-50 flex items-center justify-center p-3 select-none"
             @click.self="selectedPlayer = null">
            
            <div class="bg-slate-900 border-2 rounded-3xl max-w-lg w-full p-4 sm:p-5 shadow-2xl space-y-3"
                 :class="selectedPlayer.side === 'home' ? 'border-blue-500 shadow-blue-500/20' : 'border-rose-500 shadow-rose-500/20'">
                
                <!-- Target Player Header -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <div class="flex items-center space-x-2.5">
                        <span class="px-2.5 py-1 rounded-xl text-white font-mono font-black text-lg shadow"
                              :class="selectedPlayer.side === 'home' ? 'bg-blue-600' : 'bg-rose-600'"
                              x-text="'#' + selectedPlayer.jersey">
                        </span>
                        <div>
                            <h3 class="text-sm sm:text-base font-black text-white leading-none" x-text="selectedPlayer.name"></h3>
                            <span class="text-[10px] font-mono font-bold uppercase"
                                  :class="selectedPlayer.side === 'home' ? 'text-blue-400' : 'text-rose-400'"
                                  x-text="selectedPlayer.side.toUpperCase() + ' TEAM'">
                            </span>
                        </div>
                    </div>

                    <button @click="selectedPlayer = null" class="p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Instant Action Grid (Large Finger / Mouse Touch Targets) -->
                <div class="grid grid-cols-3 gap-2 text-xs font-bold">
                    
                    <!-- 2PT MAKE (+2 POINTS) -->
                    <button @click="executeAction('X')" class="p-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 touch-active text-white border border-emerald-400/80 shadow-lg shadow-emerald-600/30 flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">+2 MADE</span>
                            <span class="px-1.5 py-0.2 rounded bg-emerald-950 border border-emerald-300/50 text-[9px] font-mono font-bold text-amber-300">X</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">2pt Field Goal</span>
                    </button>

                    <!-- 3PT MAKE (+3 POINTS) -->
                    <button @click="executeAction('M')" class="p-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 touch-active text-white border border-teal-400/80 shadow-lg shadow-teal-600/30 flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">+3 MADE</span>
                            <span class="px-1.5 py-0.2 rounded bg-teal-950 border border-teal-300/50 text-[9px] font-mono font-bold text-amber-300">M</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">3pt Shot</span>
                    </button>

                    <!-- FREE THROW (+1 POINT) -->
                    <button @click="executeAction('B')" class="p-3.5 rounded-2xl bg-cyan-600 hover:bg-cyan-500 touch-active text-white border border-cyan-400/80 shadow-lg shadow-cyan-600/30 flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">+1 FT MAKE</span>
                            <span class="px-1.5 py-0.2 rounded bg-cyan-950 border border-cyan-300/50 text-[9px] font-mono font-bold text-amber-300">B</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Free Throw</span>
                    </button>

                    <!-- DEFENSIVE REBOUND -->
                    <button @click="executeAction('D')" class="p-3 rounded-2xl bg-blue-600 hover:bg-blue-500 touch-active text-white border border-blue-400/80 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">DEF REB</span>
                            <span class="px-1.5 py-0.2 rounded bg-blue-950 border border-blue-300/50 text-[9px] font-mono font-bold text-amber-300">D</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Defensive Reb</span>
                    </button>

                    <!-- OFFENSIVE REBOUND -->
                    <button @click="executeAction('O')" class="p-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 touch-active text-white border border-indigo-400/80 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">OFF REB</span>
                            <span class="px-1.5 py-0.2 rounded bg-indigo-950 border border-indigo-300/50 text-[9px] font-mono font-bold text-amber-300">O</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Offensive Reb</span>
                    </button>

                    <!-- ASSIST -->
                    <button @click="executeAction('A')" class="p-3 rounded-2xl bg-amber-600 hover:bg-amber-500 touch-active text-white border border-amber-400/80 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">ASSIST</span>
                            <span class="px-1.5 py-0.2 rounded bg-amber-950 border border-amber-300/50 text-[9px] font-mono font-bold text-amber-300">A</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Pass Ast</span>
                    </button>

                    <!-- 2PT MISS -->
                    <button @click="executeAction('Z')" class="p-3 rounded-2xl bg-slate-700 hover:bg-slate-600 touch-active text-white border border-slate-500 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">2PT MISS</span>
                            <span class="px-1.5 py-0.2 rounded bg-slate-900 border border-slate-400/50 text-[9px] font-mono font-bold text-amber-300">Z</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Attempt Miss</span>
                    </button>

                    <!-- 3PT MISS -->
                    <button @click="executeAction('N')" class="p-3 rounded-2xl bg-slate-700 hover:bg-slate-600 touch-active text-white border border-slate-500 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">3PT MISS</span>
                            <span class="px-1.5 py-0.2 rounded bg-slate-900 border border-slate-400/50 text-[9px] font-mono font-bold text-amber-300">N</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Attempt Miss</span>
                    </button>

                    <!-- STEAL -->
                    <button @click="executeAction('S')" class="p-3 rounded-2xl bg-purple-600 hover:bg-purple-500 touch-active text-white border border-purple-400 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">STEAL</span>
                            <span class="px-1.5 py-0.2 rounded bg-purple-950 border border-purple-300/50 text-[9px] font-mono font-bold text-amber-300">S</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Takeaway</span>
                    </button>

                    <!-- BLOCK -->
                    <button @click="executeAction('K')" class="p-3 rounded-2xl bg-purple-700 hover:bg-purple-600 touch-active text-white border border-purple-500 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">BLOCK</span>
                            <span class="px-1.5 py-0.2 rounded bg-purple-950 border border-purple-300/50 text-[9px] font-mono font-bold text-amber-300">K</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Swat</span>
                    </button>

                    <!-- TURNOVER -->
                    <button @click="executeAction('P')" class="p-3 rounded-2xl bg-rose-700 hover:bg-rose-600 touch-active text-white border border-rose-500 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">TURNOVER</span>
                            <span class="px-1.5 py-0.2 rounded bg-rose-950 border border-rose-300/50 text-[9px] font-mono font-bold text-amber-300">P</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Lost Ball</span>
                    </button>

                    <!-- PERSONAL FOUL -->
                    <button @click="executeAction('F')" class="p-3 rounded-2xl bg-red-700 hover:bg-red-600 touch-active text-white border border-red-500 shadow flex flex-col items-center justify-center">
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm font-black">PERS FOUL</span>
                            <span class="px-1.5 py-0.2 rounded bg-red-950 border border-red-300/50 text-[9px] font-mono font-bold text-amber-300">F</span>
                        </div>
                        <span class="text-[10px] font-mono opacity-90">Foul</span>
                    </button>

                </div>

                <div class="pt-2 border-t border-slate-800 flex justify-end">
                    <!-- SUBSTITUTE THIS PLAYER -->
                    <button @click="openLineupModal()" class="px-4 py-2 rounded-xl bg-cyan-700 hover:bg-cyan-600 touch-active text-white font-bold text-xs shadow flex items-center gap-1.5">
                        <span>Line Up Subs</span>
                        <span class="px-1.5 py-0.2 rounded bg-cyan-950 border border-cyan-400/50 text-[9px] font-mono font-bold text-amber-300">S</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- 1. DUAL-TEAM TABLE LINE-UP MODAL (CHECK-IN) -->
    <div x-show="showLineupModal" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none" style="display: none;"
         @click.self="showLineupModal = false">
        
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-4 sm:p-5 shadow-2xl space-y-3 max-h-[92vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-2 border-b border-slate-800 shrink-0">
                <div class="flex items-center space-x-2">
                    <span class="text-sm sm:text-base font-black uppercase text-white tracking-wider">Substitutions (Check-In)</span>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/20 border border-amber-500/40 text-[10px] font-mono font-bold text-amber-300"
                          x-text="pendingSubCount() + ' Lined Up'">
                    </span>
                </div>
                <button @click="showLineupModal = false" class="p-1 rounded-xl bg-slate-800 text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="text-[11px] text-slate-400 shrink-0">
                Tap bench players from either side who have arrived at the scorer's table to check in, then close. When they are waved onto the court, tap <strong class="text-emerald-400">SEND IN</strong>.
            </div>

            <!-- BOTH TEAMS DISPLAYED AT ONCE SIDE-BY-SIDE -->
            <div class="grid grid-cols-2 gap-3 flex-1 min-h-0 overflow-y-auto">
                <!-- HOME BENCH (LEFT COLUMN) -->
                <div class="flex flex-col bg-slate-950/80 border border-blue-900/60 rounded-2xl p-2.5 space-y-2">
                    <div class="flex items-center justify-between pb-1 border-b border-blue-900/50">
                        <span class="text-xs font-black uppercase text-blue-400 truncate">{{ $game->home_display_name }} (Bench)</span>
                        <span class="text-[9px] font-mono text-slate-400" x-text="homeBench.length + ' Available'"></span>
                    </div>

                    <div class="space-y-1.5 flex-1 overflow-y-auto pr-0.5">
                        <template x-for="p in homeBench" :key="p.id || p.jersey_number">
                            <button @click="togglePendingSub('home', p)" 
                                    type="button"
                                    class="w-full p-2 rounded-xl border text-left flex items-center justify-between transition touch-active"
                                    :class="isPlayerPending('home', p.jersey_number) 
                                        ? 'bg-blue-600 text-white border-white shadow-md shadow-blue-600/40 ring-2 ring-blue-400' 
                                        : 'bg-slate-900 text-slate-200 border-slate-800 hover:border-blue-700/60'">
                                <div class="flex items-center space-x-2 truncate">
                                    <span class="font-mono font-black text-sm" x-text="'#' + p.jersey_number"></span>
                                    <span class="truncate text-xs font-semibold" x-text="p.player_name"></span>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold shrink-0 ml-1"
                                      :class="isPlayerPending('home', p.jersey_number) ? 'bg-white text-blue-900' : 'bg-slate-800 text-slate-400'"
                                      x-text="isPlayerPending('home', p.jersey_number) ? 'LINED UP' : 'ADD'">
                                </span>
                            </button>
                        </template>
                        <div x-show="homeBench.length === 0" class="text-xs text-slate-500 text-center py-6">
                            No bench players on Home roster.
                        </div>
                    </div>
                </div>

                <!-- AWAY BENCH (RIGHT COLUMN) -->
                <div class="flex flex-col bg-slate-950/80 border border-rose-900/60 rounded-2xl p-2.5 space-y-2">
                    <div class="flex items-center justify-between pb-1 border-b border-rose-900/50">
                        <span class="text-xs font-black uppercase text-rose-400 truncate">{{ $game->away_display_name }} (Bench)</span>
                        <span class="text-[9px] font-mono text-slate-400" x-text="awayBench.length + ' Available'"></span>
                    </div>

                    <div class="space-y-1.5 flex-1 overflow-y-auto pr-0.5">
                        <template x-for="p in awayBench" :key="p.id || p.jersey_number">
                            <button @click="togglePendingSub('away', p)" 
                                    type="button"
                                    class="w-full p-2 rounded-xl border text-left flex items-center justify-between transition touch-active"
                                    :class="isPlayerPending('away', p.jersey_number) 
                                        ? 'bg-rose-600 text-white border-white shadow-md shadow-rose-600/40 ring-2 ring-rose-400' 
                                        : 'bg-slate-900 text-slate-200 border-slate-800 hover:border-rose-700/60'">
                                <div class="flex items-center space-x-2 truncate">
                                    <span class="font-mono font-black text-sm" x-text="'#' + p.jersey_number"></span>
                                    <span class="truncate text-xs font-semibold" x-text="p.player_name"></span>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold shrink-0 ml-1"
                                      :class="isPlayerPending('away', p.jersey_number) ? 'bg-white text-rose-900' : 'bg-slate-800 text-slate-400'"
                                      x-text="isPlayerPending('away', p.jersey_number) ? 'LINED UP' : 'ADD'">
                                </span>
                            </button>
                        </template>
                        <div x-show="awayBench.length === 0" class="text-xs text-slate-500 text-center py-6">
                            No bench players on Away roster.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Controls -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs shrink-0">
                <button x-show="pendingSubCount() > 0" 
                        @click="clearPendingSubs()" 
                        class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-semibold text-[11px] transition flex items-center gap-1.5">
                    <span>Clear Table Queue</span>
                    <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-600 text-[8px] font-mono text-amber-300 font-bold">C</span>
                </button>
                <div x-show="pendingSubCount() === 0"></div>

                <div class="flex items-center space-x-2">
                    <button @click="showLineupModal = false" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs transition flex items-center gap-1.5">
                        <span>Done (Keep Waiting)</span>
                        <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-600 text-[8px] font-mono text-slate-300">Esc</span>
                    </button>
                    <button x-show="pendingSubCount() > 0" 
                            @click="openExecuteSubModal()" 
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition">
                        <span>Send In Now</span>
                        <span x-text="'(' + pendingSubCount() + ')'"></span>
                        <span class="px-1.5 py-0.2 rounded bg-emerald-950 border border-emerald-400 text-[8px] font-mono text-amber-300 font-bold">↵ Enter</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. DUAL-TEAM EXECUTE SUBSTITUTIONS (ASSIGN PLAYERS COMING OUT) -->
    <div x-show="showExecuteSubModal" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none" style="display: none;"
         @click.self="showExecuteSubModal = false">
        
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-3xl w-full p-4 sm:p-5 shadow-2xl space-y-3 max-h-[94vh] flex flex-col">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-2 border-b border-slate-800 shrink-0">
                <div class="flex items-center space-x-2">
                    <span class="text-sm sm:text-base font-black uppercase text-white tracking-wider">Select Outgoing Players</span>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-[10px] font-mono font-bold text-emerald-300"
                          x-text="pendingSubCount() + ' Pending Sub(s)'">
                    </span>
                </div>
                <button @click="showExecuteSubModal = false" class="p-1 rounded-xl bg-slate-800 text-slate-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="text-[11px] text-slate-400 shrink-0">
                Select which on-court players are coming OUT to match the number of incoming substitutes for each team.
            </div>

            <!-- BOTH TEAMS DISPLAYED AT ONCE SIDE-BY-SIDE -->
            <div class="grid grid-cols-2 gap-3 flex-1 min-h-0 overflow-y-auto">
                <!-- HOME TEAM SUBS (LEFT COLUMN) -->
                <div class="flex flex-col bg-slate-950/80 border border-blue-900/60 rounded-2xl p-3 space-y-2.5">
                    <div class="flex items-center justify-between pb-1 border-b border-blue-900/50">
                        <span class="text-xs font-black uppercase text-blue-400 truncate">{{ $game->home_display_name }}</span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md"
                              :class="selectedOutSubs.home.length === pendingSubs.home.length && pendingSubs.home.length > 0 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-slate-800 text-slate-400'"
                              x-text="selectedOutSubs.home.length + ' / ' + pendingSubs.home.length + ' OUT Selected'">
                        </span>
                    </div>

                    <template x-if="pendingSubs.home.length === 0">
                        <div class="text-xs text-slate-500 text-center py-8">
                            No pending subs for Home.
                        </div>
                    </template>

                    <div class="space-y-3 flex-1 overflow-y-auto pr-0.5" x-show="pendingSubs.home.length > 0">
                        <!-- Incoming Players Pill List -->
                        <div class="p-2 bg-slate-900/90 border border-blue-800/60 rounded-xl space-y-1">
                            <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider block">Incoming Players:</span>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="inP in pendingSubs.home" :key="inP.id || inP.jersey_number">
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg bg-blue-600/30 border border-blue-500/50 text-blue-200 text-xs font-mono font-bold">
                                        <span class="text-[9px] font-black text-blue-400">IN</span>
                                        <span x-text="'#' + inP.jersey_number + ' ' + inP.player_name"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- On-Court Players Selection Grid -->
                        <div class="space-y-1.5">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tap On-Court Players Leaving Game:</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs">
                                <template x-for="courtP in homeCourt" :key="courtP.id || courtP.jersey_number">
                                    <button @click="toggleSelectedOut('home', courtP.jersey_number)"
                                            type="button"
                                            class="p-2 rounded-xl border text-left flex items-center justify-between transition touch-active"
                                            :class="isPlayerSelectedOut('home', courtP.jersey_number)
                                                ? 'bg-rose-600 text-white border-white shadow-lg ring-2 ring-rose-400'
                                                : 'bg-slate-900/90 text-slate-300 border-slate-800 hover:border-slate-600 hover:bg-slate-800'">
                                        <div class="flex items-center space-x-1.5 truncate">
                                            <span class="font-mono font-black text-xs" x-text="'#' + courtP.jersey_number"></span>
                                            <span class="truncate text-xs font-semibold" x-text="courtP.player_name"></span>
                                        </div>
                                        <span x-show="isPlayerSelectedOut('home', courtP.jersey_number)" class="text-[10px] font-black px-1.5 py-0.5 rounded bg-white text-rose-700 uppercase shrink-0 tracking-wider">OUT</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AWAY TEAM SUBS (RIGHT COLUMN) -->
                <div class="flex flex-col bg-slate-950/80 border border-rose-900/60 rounded-2xl p-3 space-y-2.5">
                    <div class="flex items-center justify-between pb-1 border-b border-rose-900/50">
                        <span class="text-xs font-black uppercase text-rose-400 truncate">{{ $game->away_display_name }}</span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md"
                              :class="selectedOutSubs.away.length === pendingSubs.away.length && pendingSubs.away.length > 0 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'bg-slate-800 text-slate-400'"
                              x-text="selectedOutSubs.away.length + ' / ' + pendingSubs.away.length + ' OUT Selected'">
                        </span>
                    </div>

                    <template x-if="pendingSubs.away.length === 0">
                        <div class="text-xs text-slate-500 text-center py-8">
                            No pending subs for Away.
                        </div>
                    </template>

                    <div class="space-y-3 flex-1 overflow-y-auto pr-0.5" x-show="pendingSubs.away.length > 0">
                        <!-- Incoming Players Pill List -->
                        <div class="p-2 bg-slate-900/90 border border-rose-800/60 rounded-xl space-y-1">
                            <span class="text-[10px] font-bold text-rose-400 uppercase tracking-wider block">Incoming Players:</span>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="inP in pendingSubs.away" :key="inP.id || inP.jersey_number">
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg bg-rose-600/30 border border-rose-500/50 text-rose-200 text-xs font-mono font-bold">
                                        <span class="text-[9px] font-black text-rose-400">IN</span>
                                        <span x-text="'#' + inP.jersey_number + ' ' + inP.player_name"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- On-Court Players Selection Grid -->
                        <div class="space-y-1.5">
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tap On-Court Players Leaving Game:</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs">
                                <template x-for="courtP in awayCourt" :key="courtP.id || courtP.jersey_number">
                                    <button @click="toggleSelectedOut('away', courtP.jersey_number)"
                                            type="button"
                                            class="p-2 rounded-xl border text-left flex items-center justify-between transition touch-active"
                                            :class="isPlayerSelectedOut('away', courtP.jersey_number)
                                                ? 'bg-rose-600 text-white border-white shadow-lg ring-2 ring-rose-400'
                                                : 'bg-slate-900/90 text-slate-300 border-slate-800 hover:border-slate-600 hover:bg-slate-800'">
                                        <div class="flex items-center space-x-1.5 truncate">
                                            <span class="font-mono font-black text-xs" x-text="'#' + courtP.jersey_number"></span>
                                            <span class="truncate text-xs font-semibold" x-text="courtP.player_name"></span>
                                        </div>
                                        <span x-show="isPlayerSelectedOut('away', courtP.jersey_number)" class="text-[10px] font-black px-1.5 py-0.5 rounded bg-white text-rose-700 uppercase shrink-0 tracking-wider">OUT</span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Controls -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs shrink-0">
                <button @click="openLineupModal()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition flex items-center gap-1.5">
                    <span>← Edit Lineup Table</span>
                    <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-600 text-[8px] font-mono text-amber-300 font-bold">B</span>
                </button>

                <div class="flex items-center space-x-2">
                    <button @click="showExecuteSubModal = false" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs transition flex items-center gap-1.5">
                        <span>Cancel (Keep Waiting)</span>
                        <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-600 text-[8px] font-mono text-slate-300">Esc</span>
                    </button>
                    <button @click="executeAllPendingSubs()" 
                            :disabled="!canExecutePendingSubs()"
                            class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-black text-xs shadow-lg shadow-emerald-600/40 transition flex items-center gap-1.5">
                        <span>Confirm Substitutions</span>
                        <span class="px-1.5 py-0.2 rounded bg-emerald-950 border border-emerald-400 text-[8px] font-mono text-amber-300 font-bold">↵ Enter</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. PLAY-BY-PLAY LOG VIEWER & QUICK EDIT / SWIPE DELETE MODAL -->
    <div x-show="showPlayByPlayModal" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none" style="display: none;"
         @click.self="closePlayByPlayModal()">
        
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-3xl w-full p-4 sm:p-5 shadow-2xl space-y-3 max-h-[94vh] flex flex-col">
            
            <!-- Header: Title, Filters & Close -->
            <div class="flex items-center justify-between pb-2 border-b border-slate-800 shrink-0">
                <div class="flex items-center space-x-2.5">
                    <span class="text-sm sm:text-base font-black uppercase text-white tracking-wider">Play-by-Play Log</span>
                    <span class="px-2 py-0.5 rounded-full bg-slate-800 text-amber-300 font-mono text-[10px] font-bold"
                          x-text="recentEvents.length + ' Plays'">
                    </span>
                </div>

                <!-- Back to Game button -->
                <button @click="closePlayByPlayModal()" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md shadow-blue-600/30 transition flex items-center gap-1.5">
                    <span>← Back to Game</span>
                    <span class="px-1 py-0.2 rounded bg-blue-950 text-[9px] font-mono text-blue-200">Esc</span>
                </button>
            </div>

            <!-- Filters Bar & Instructions -->
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs shrink-0 bg-slate-950/80 p-2 rounded-xl border border-slate-800">
                <!-- Team Filter -->
                <div class="flex items-center space-x-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase mr-1">Team:</span>
                    <button @click="pbpFilterTeam = 'all'" 
                            class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                            :class="pbpFilterTeam === 'all' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white'">
                        <span>All</span>
                        <span class="text-[8px] font-mono opacity-80">[1]</span>
                    </button>
                    <button @click="pbpFilterTeam = 'home'" 
                            class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                            :class="pbpFilterTeam === 'home' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-blue-300'">
                        <span>{{ $game->home_display_name }}</span>
                        <span class="text-[8px] font-mono opacity-80">[2]</span>
                    </button>
                    <button @click="pbpFilterTeam = 'away'" 
                            class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                            :class="pbpFilterTeam === 'away' ? 'bg-rose-600 text-white' : 'text-slate-400 hover:text-rose-300'">
                        <span>{{ $game->away_display_name }}</span>
                        <span class="text-[8px] font-mono opacity-80">[3]</span>
                    </button>
                </div>

                <!-- Period Filter -->
                <div class="flex items-center space-x-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase mr-1">Period:</span>
                    <button @click="pbpFilterPeriod = 'all'" 
                            class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition"
                            :class="pbpFilterPeriod === 'all' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white'">
                        All
                    </button>
                    <template x-for="p in Math.min(6, Math.max(4, currentPeriod))" :key="p">
                        <button @click="pbpFilterPeriod = p" 
                                class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition font-mono"
                                :class="pbpFilterPeriod == p ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-emerald-300'"
                                x-text="p <= 4 ? 'Q' + p : 'OT' + (p - 4)">
                        </button>
                    </template>
                </div>

                <div class="text-[10px] text-slate-400">
                    Swipe left or tap to delete &bull; Tap to edit
                </div>
            </div>

            <!-- Plays Chronological Feed (Scrollable with Touch & Click Actions) -->
            <div class="flex-1 min-h-0 overflow-y-auto space-y-1.5 pr-1">
                <template x-for="event in filteredRecentEvents()" :key="event.id || event.sequence">
                    <div class="relative overflow-hidden rounded-xl bg-slate-950 border border-slate-800 shadow-sm transition group"
                         @touchstart="onTouchStartPlay($event, event.id)"
                         @touchmove="onTouchMovePlay($event, event.id)"
                         @touchend="onTouchEndPlay($event, event.id)">
                        
                        <!-- Swipe Delete Reveal Background Layer -->
                        <div class="absolute inset-y-0 right-0 w-24 bg-rose-600 flex items-center justify-center text-white font-bold text-xs gap-1 cursor-pointer"
                             @click="deleteEventFast(event.id)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Delete</span>
                        </div>

                        <!-- Card Foreground (Slides on Swipe) -->
                        <div class="relative bg-slate-900/95 p-2.5 flex items-center justify-between gap-2 transition-transform select-none"
                             :style="{ transform: (_swipeTouchState[event.id]?.diffX || 0) < 0 ? 'translateX(' + Math.max(-100, _swipeTouchState[event.id].diffX) + 'px)' : 'none' }">
                            
                            <!-- Left: Period & Team Badge & Jersey -->
                            <div class="flex items-center space-x-2 shrink-0">
                                <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[9px] font-black"
                                      x-text="'Q' + (event.period || 1)">
                                </span>
                                <span class="px-2 py-0.5 rounded-lg text-white font-mono font-black text-xs shadow-sm"
                                      :class="event.team_side === 'home' ? 'bg-blue-600' : 'bg-rose-600'"
                                      x-text="'#' + (event.jersey_number || '--')">
                                </span>
                            </div>

                            <!-- Center: Player & Action Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-1.5 truncate">
                                    <span class="text-xs font-bold text-white truncate" x-text="event.player_name || 'Team Action'"></span>
                                    <span class="text-[10px] font-mono uppercase font-bold"
                                          :class="event.team_side === 'home' ? 'text-blue-400' : 'text-rose-400'"
                                          x-text="'(' + (event.team_side === 'home' ? 'Home' : 'Away') + ')'">
                                    </span>
                                </div>
                                <div class="text-[11px] font-semibold text-slate-300 truncate" x-text="event.action_name || event.description"></div>
                            </div>

                            <!-- Right: Score After & Edit/Delete Action Buttons -->
                            <div class="flex items-center space-x-2 shrink-0">
                                <div class="text-right hidden sm:block">
                                    <span class="text-[10px] font-mono text-slate-400 block leading-tight">SCORE</span>
                                    <span class="font-mono text-xs font-black text-white"
                                          x-text="(event.home_score_after ?? homeScore) + ' - ' + (event.away_score_after ?? awayScore)">
                                    </span>
                                </div>

                                <!-- Edit Button -->
                                <button @click.stop="startEditingEvent(event)" 
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-300 hover:text-cyan-200 transition shadow touch-active" 
                                        title="Edit this play">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>

                                <!-- Delete Button -->
                                <button @click.stop="deleteEventFast(event.id)" 
                                        class="p-1.5 rounded-lg bg-rose-950/80 hover:bg-rose-900 border border-rose-800 text-rose-300 hover:text-white transition shadow touch-active" 
                                        title="Delete this play">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="filteredRecentEvents().length === 0" class="text-xs text-slate-500 text-center py-12">
                    No plays recorded yet for this selection.
                </div>
            </div>

            <!-- Footer: Quick Stats & Return -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs shrink-0">
                <span class="text-[11px] text-slate-400 font-mono" x-text="recentEvents.length + ' Total Events Logged'"></span>
                <button @click="closePlayByPlayModal()" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs shadow-lg shadow-blue-600/30 transition flex items-center gap-1.5">
                    <span>← Done / Back to Game</span>
                    <span class="px-1.5 py-0.2 rounded bg-blue-950 text-[9px] font-mono text-blue-200">Esc</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 4. INLINE PLAY EDIT SUB-MODAL -->
    <template x-if="editingEvent">
        <div class="fixed inset-0 bg-black/90 backdrop-blur-md z-60 flex items-center justify-center p-3 select-none"
             @click.self="editingEvent = null">
            
            <div class="bg-slate-900 border-2 rounded-3xl max-w-md w-full p-4 sm:p-5 shadow-2xl space-y-3"
                 :class="editingEvent.team_side === 'home' ? 'border-blue-500' : 'border-rose-500'">
                
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-black text-white uppercase">Edit Play</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                              :class="editingEvent.team_side === 'home' ? 'bg-blue-900 text-blue-300' : 'bg-rose-900 text-rose-300'"
                              x-text="editingEvent.team_side.toUpperCase()">
                        </span>
                    </div>
                    <button @click="editingEvent = null" class="p-1 rounded-xl bg-slate-800 text-slate-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <!-- Jersey # Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Player Jersey #</label>
                        <input type="text" 
                               x-model="editingEvent.jersey_number" 
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                    </div>

                    <!-- Period / Set Selector (Max 6) -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Period (Max 6 / OT2)</label>
                        <div class="grid grid-cols-6 gap-1.5">
                            <template x-for="p in Math.min(6, Math.max(4, currentPeriod))" :key="p">
                                <button type="button" 
                                        @click="editingEvent.period = p"
                                        class="py-1.5 rounded-lg font-mono font-bold text-xs border transition"
                                        :class="editingEvent.period == p ? 'bg-emerald-600 text-white border-white' : 'bg-slate-950 text-slate-300 border-slate-800'">
                                    <span x-text="p <= 4 ? 'Q' + p : 'OT' + (p - 4)"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Action Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Action Type</label>
                        <select x-model="editingEvent.action_code"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold text-xs focus:border-blue-500 focus:outline-none">
                            <option value="X">2pt MAKE (+2)</option>
                            <option value="Z">2pt Miss (0)</option>
                            <option value="M">3pt MAKE (+3)</option>
                            <option value="N">3pt Miss (0)</option>
                            <option value="B">Free Throw MAKE (+1)</option>
                            <option value="V">Free Throw Miss (0)</option>
                            <option value="D">Defensive Rebound</option>
                            <option value="O">Offensive Rebound</option>
                            <option value="A">Assist</option>
                            <option value="S">Steal</option>
                            <option value="K">Block</option>
                            <option value="P">Turnover</option>
                            <option value="F">Personal Foul</option>
                            <option value="R">Offensive Foul</option>
                            <option value="T">Technical Foul</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                    <button @click="editingEvent = null" class="px-3 py-1.5 rounded-xl bg-slate-800 text-slate-300 font-semibold text-xs flex items-center gap-1.5">
                        <span>Cancel</span>
                        <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono text-slate-400">Esc</span>
                    </button>
                    <button @click="saveEditedEventFast()" class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-1.5">
                        <span>Save Changes</span>
                        <span class="px-1.5 py-0.2 rounded bg-emerald-950 border border-emerald-400 text-[8px] font-mono text-amber-300 font-bold">↵ Enter</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- ON-THE-FLY SPREADSHEET ROSTER MANAGEMENT MODAL -->
    @if ($showRosterModal)
        <div class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none animate-fade-in"
             wire:keydown.escape="closeRosterModal"
             x-data="inGameRosterSpreadsheet({
                 sport: 'basketball',
                 teamSide: '{{ $rosterModalTeam }}',
                 initialPlayers: {{ Js::from($rosterModalLineups->map(function($l) {
                     return [
                         'id' => $l->id,
                         'jersey_number' => (string)$l->jersey_number,
                         'name' => $l->player_name,
                         'is_on_court' => (bool)$l->is_on_court,
                     ];
                 })->values()) }}
             })">
            
            <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-3xl w-full p-4 sm:p-5 shadow-2xl space-y-3 max-h-[92vh] flex flex-col">
                
                <!-- Modal Header: Team Switcher & Save -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-800 shrink-0">
                    <!-- Home / Away Pill Buttons -->
                    <div class="flex items-center space-x-2">
                        <button wire:click="setRosterModalTeam('home')"
                                type="button"
                                class="py-1.5 px-3 rounded-lg text-xs font-black uppercase tracking-wider transition border flex items-center gap-1.5 cursor-pointer {{ $rosterModalTeam === 'home' ? 'bg-blue-600 text-white border-blue-400 shadow-md shadow-blue-600/30' : 'bg-slate-950 text-slate-400 border-slate-800 hover:border-slate-700' }}">
                            <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                            <span class="truncate max-w-[140px]">{{ $game->home_display_name }}</span>
                            <span class="px-1 py-0.2 rounded bg-slate-950/80 border border-slate-700 text-[8px] font-mono text-amber-300">1</span>
                        </button>
                        <button wire:click="setRosterModalTeam('away')"
                                type="button"
                                class="py-1.5 px-3 rounded-lg text-xs font-black uppercase tracking-wider transition border flex items-center gap-1.5 cursor-pointer {{ $rosterModalTeam === 'away' ? 'bg-rose-600 text-white border-rose-400 shadow-md shadow-rose-600/30' : 'bg-slate-950 text-slate-400 border-slate-800 hover:border-slate-700' }}">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            <span class="truncate max-w-[140px]">{{ $game->away_display_name }}</span>
                            <span class="px-1 py-0.2 rounded bg-slate-950/80 border border-slate-700 text-[8px] font-mono text-amber-300">2</span>
                        </button>
                    </div>

                    <!-- Right Header CTA: Stats & Save/Close -->
                    <div class="flex items-center space-x-2 text-xs">
                        <div class="font-mono text-[11px] text-slate-400 px-2.5 py-1 rounded bg-slate-950 border border-slate-800 hidden sm:block">
                            <span class="text-white font-bold" x-text="validCount"></span> players &bull; <span class="text-emerald-400 font-bold" x-text="onCourtCount"></span> on court
                        </div>

                        <template x-if="saveSuccessMessage">
                            <span class="text-xs font-bold text-emerald-400 flex items-center gap-1 bg-emerald-950 px-2 py-1 rounded border border-emerald-800 animate-fade-in" x-text="saveSuccessMessage"></span>
                        </template>

                        <button type="button" @click="saveAndApply($wire)" :disabled="isSaving"
                                class="px-4 py-1.5 rounded-xl font-bold text-xs text-white shadow transition cursor-pointer flex items-center gap-1.5"
                                :class="isDirty ? 'bg-emerald-600 hover:bg-emerald-500 ring-2 ring-emerald-400/50' : 'bg-blue-600 hover:bg-blue-500'">
                            <span x-text="isSaving ? 'Saving...' : (isDirty ? 'Save *' : 'Save Lineup')"></span>
                        </button>

                        <button wire:click="closeRosterModal" class="p-1.5 rounded-lg bg-slate-800 text-slate-400 hover:text-white transition cursor-pointer" title="Close (Esc)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Pure Spreadsheet Table Container (Scrollable) -->
                <div class="flex-1 min-h-0 overflow-y-auto rounded-xl border border-slate-700/80 bg-slate-950/80 shadow-inner" data-grid="ingame-roster-grid">
                    <table class="w-full border-collapse text-xs select-none">
                        <thead class="bg-slate-950 text-slate-400 font-mono uppercase text-[10px] sticky top-0 z-10 border-b border-slate-700 select-none">
                            <tr>
                                <th class="w-10 py-2 px-2 text-center bg-slate-950/90 border-r border-slate-800 text-slate-500 font-bold">#</th>
                                <th class="w-28 py-2 px-3 text-left border-r border-slate-800 uppercase tracking-wider text-slate-300">
                                    A &bull; Jersey #
                                </th>
                                <th class="py-2 px-3 text-left border-r border-slate-800 uppercase tracking-wider text-slate-300">
                                    B &bull; Player Name (First & Last)
                                </th>
                                <th class="w-28 py-2 px-2 text-center border-r border-slate-800 uppercase tracking-wider text-slate-300">
                                    Court Status
                                </th>
                                <th class="w-12 py-2 px-1 text-center text-slate-600"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-200 bg-slate-950/40">
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr class="hover:bg-slate-800/40 transition group">
                                    <!-- Row Number Header Cell -->
                                    <td class="w-10 text-center py-0 px-2 bg-slate-950/70 border-r border-slate-800 text-slate-500 font-mono text-[11px] select-none font-bold" x-text="idx + 1"></td>

                                    <!-- Jersey Number Cell -->
                                    <td class="w-28 p-0 border-r border-slate-800 relative">
                                        <input type="text"
                                               :data-row="idx"
                                               data-field="jersey_number"
                                               data-grid="ingame-roster-grid"
                                               x-model="row.jersey_number"
                                               @keydown="handleKeydown($event, idx, 'jersey_number')"
                                               @paste="handlePaste($event, idx, 'jersey_number')"
                                               placeholder=""
                                               maxlength="5"
                                               class="w-full h-8 px-3 py-1 bg-transparent border-0 outline-none text-white font-mono font-bold text-xs focus:bg-blue-950/40 focus:ring-2 focus:ring-blue-500 focus:ring-inset transition">
                                    </td>

                                    <!-- Player Name Cell (First & Last in single column) -->
                                    <td class="p-0 border-r border-slate-800 relative">
                                        <input type="text"
                                               :data-row="idx"
                                               data-field="name"
                                               data-grid="ingame-roster-grid"
                                               x-model="row.name"
                                               @keydown="handleKeydown($event, idx, 'name')"
                                               @paste="handlePaste($event, idx, 'name')"
                                               placeholder=""
                                               class="w-full h-8 px-3 py-1 bg-transparent border-0 outline-none text-white font-medium text-xs focus:bg-blue-950/40 focus:ring-2 focus:ring-blue-500 focus:ring-inset transition">
                                    </td>

                                    <!-- Court Status Toggle -->
                                    <td class="w-28 p-0 border-r border-slate-800 text-center">
                                        <button type="button"
                                                @click="toggleCourt(idx)"
                                                class="w-full h-8 px-2 flex items-center justify-center text-[10px] font-bold uppercase transition cursor-pointer"
                                                :class="row.is_on_court ? 'bg-emerald-950/80 text-emerald-300 hover:bg-emerald-900/90' : 'text-slate-500 hover:text-slate-300'">
                                            <span x-text="row.is_on_court ? 'On Court' : 'Bench'"></span>
                                        </button>
                                    </td>

                                    <!-- Actions (Delete) -->
                                    <td class="w-12 p-0 text-center">
                                        <button type="button" @click="removeRow(idx)" class="w-full h-8 flex items-center justify-center text-slate-600 hover:text-rose-400 opacity-0 group-hover:opacity-100 transition cursor-pointer" title="Delete Row">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Summary Bar -->
                <div class="px-2 pt-1 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400 shrink-0">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-blue-400 font-bold">Spreadsheet View:</span>
                        <span>Arrow keys navigate &bull; Paste (<kbd class="px-1 py-0.5 rounded bg-slate-800 text-white font-mono text-[9px]">Cmd+V</kbd> / <kbd class="px-1 py-0.5 rounded bg-slate-800 text-white font-mono text-[9px]">Ctrl+V</kbd>) directly from Google Sheets/Excel.</span>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button type="button" @click="addEmptyRow()" class="text-xs text-blue-400 hover:text-blue-300 font-bold font-mono transition cursor-pointer">
                            + Add Row
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endif

    <!-- Game Details & Officials Setup Modal -->
    @include('livewire.partials.game-details-modal')

</div>
