<div class="w-full h-full max-h-full flex-1 min-h-0 flex flex-col justify-between overflow-hidden select-none" 
     wire:ignore.self
     x-data="ProKeeperEngine.createOperator('volleyball', {
         gameId: {{ $game->id }},
         homeTeamName: '{{ addslashes($game->home_display_name) }}',
         awayTeamName: '{{ addslashes($game->away_display_name) }}',
         homeTeamColor: '{{ $game->home_team_score_color ?? '#1e40af' }}',
         awayTeamColor: '{{ $game->away_team_score_color ?? '#b91c1c' }}',
         broadcastFlip: @js((bool)($game->settings['broadcast_flip'] ?? false)),
         homeScore: {{ (int)$game->home_score }},
         awayScore: {{ (int)$game->away_score }},
         homePeriodScores: @js($game->home_period_scores ?? [0, 0, 0, 0]),
         awayPeriodScores: @js($game->away_period_scores ?? [0, 0, 0, 0]),
         currentPeriod: {{ (int)$game->current_period }},
         periodName: '{{ $game->period_name }}',
         server: '{{ $game->current_server ?? 'home' }}',
         homeRotation: {{ (int)$game->home_rotation }},
         awayRotation: {{ (int)$game->away_rotation }},
         homeTimeouts: {{ (int)$game->home_timeouts_remaining }},
         awayTimeouts: {{ (int)$game->away_timeouts_remaining }},
         homeCourt: @js($homeCourt),
         awayCourt: @js($awayCourt),
         homeBench: @js($homeBench),
         awayBench: @js($awayBench),
         recentEvents: @js($recentEvents),
     })">

    <!-- 1. TOP SCOREBOARD BAR (Compact, Zero-Waste Height, Dynamically Colored & Flippable) -->
    <div class="bg-slate-900 border-b border-slate-800 p-2 sm:p-3 shrink-0 shadow-lg rounded-2xl mb-1">
        <div class="flex flex-col sm:flex-row gap-2 items-stretch">

            <!-- HOME SCORE BLOCK -->
            <div class="flex-1 min-w-0 flex items-center justify-between p-2 sm:p-2.5 rounded-xl border transition-all"
                 :class="[
                     isFlipped ? 'order-3' : 'order-1',
                     server === 'home' ? 'shadow-lg ring-1 ring-white/30' : 'bg-slate-950 border-slate-800'
                 ]"
                 :style="server === 'home' ? { backgroundColor: homeTeamColor + '26', borderColor: homeTeamColor } : { borderColor: homeTeamColor + '40' }">
                <div class="space-y-0.5 truncate mr-2">
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shadow-sm" :style="{ backgroundColor: homeTeamColor }"></span>
                        <span class="text-[10px] font-black uppercase tracking-wider" :style="{ color: homeTeamColor }">HOME</span>
                        <template x-if="server === 'home'">
                            <span class="px-1.5 py-0.2 rounded bg-emerald-500 text-slate-950 text-[9px] font-black tracking-wider uppercase animate-pulse">SERVING</span>
                        </template>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-white truncate leading-tight">{{ $game->home_display_name }}</h2>
                    <div class="flex items-center space-x-2 text-[10px] text-slate-400 font-mono">
                        <span>ROT: <strong class="text-white" x-text="'P' + homeRotation"></strong></span>
                        <span>TO: <strong class="text-white" x-text="homeTimeouts"></strong></span>
                    </div>
                </div>

                <!-- Score Counter & Direct Touch +/- -->
                <div class="flex items-center space-x-1.5 shrink-0">
                    <div class="flex flex-col space-y-1">
                        <button @click="adjustScoreFast('home', 1)" 
                                :style="{ backgroundColor: homeTeamColor }"
                                class="w-8 h-7 sm:w-9 sm:h-8 rounded-lg hover:brightness-110 touch-active text-white font-black text-xs flex items-center justify-center gap-1 shadow transition" 
                                :title="'+1 Home (' + (isFlipped ? ']' : '[') + ')'">
                            <span>+1</span>
                            <span class="px-1 py-0.2 rounded bg-black/40 border border-white/20 text-[8px] font-mono font-bold text-amber-300 leading-none" x-text="isFlipped ? ']' : '['"></span>
                        </button>
                        <button @click="adjustScoreFast('home', -1)" class="w-8 h-6 sm:w-9 sm:h-6 rounded-lg bg-slate-800 hover:bg-slate-700 touch-active text-slate-300 font-bold text-[10px] flex items-center justify-center gap-1 transition" 
                                :title="'-1 Home (' + (isFlipped ? '}' : '{') + ')'">
                            <span>-1</span>
                            <span class="px-1 py-0.2 rounded bg-black/40 border border-white/20 text-[8px] font-mono font-bold text-amber-300 leading-none" x-text="isFlipped ? '}' : '{'"></span>
                        </button>
                    </div>
                    <div class="min-w-[45px] sm:min-w-[55px] text-right">
                        <span class="font-mono text-3xl sm:text-4xl font-black text-white tracking-tighter leading-none scoreboard-glow" x-text="homeScore"></span>
                    </div>
                </div>
            </div>

            <!-- CENTER SET SUMMARY & 5-SET STATUS STRIP -->
            <div class="order-2 shrink-0 flex flex-col items-center justify-center text-center space-y-1.5 px-1 w-full sm:w-auto sm:min-w-[300px] md:min-w-[350px]">
                
                <!-- Set Meta Bar: Set Name, Plays Log, and Sync Status -->
                <div class="flex items-center justify-between w-full px-1 gap-1.5 text-xs">
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-950 text-blue-300 text-[10px] font-black uppercase tracking-widest border border-slate-800 shadow-sm" x-text="periodName">
                        {{ $game->period_name }}
                    </span>

                    <div class="flex items-center space-x-1 text-[9px] font-semibold text-slate-300 truncate max-w-[140px]">
                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                              :class="syncStatus === 'synced' ? 'bg-emerald-400' : (syncStatus === 'syncing' ? 'bg-amber-400 animate-pulse' : 'bg-rose-400')">
                        </span>
                        <span x-text="feedbackMessage" class="truncate"></span>
                    </div>

                    <!-- Quick Play-by-Play Trigger -->
                    <button @click="openPlayByPlayModal()" 
                            class="px-2 py-0.5 rounded-full bg-slate-800 hover:bg-slate-700 border border-slate-700 hover:border-slate-500 text-amber-300 text-[9px] font-bold transition flex items-center gap-1 shadow-sm touch-active shrink-0"
                            title="Play-by-Play Log (Hotkey: L)">
                        <svg class="w-3 h-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span>Plays</span>
                        <span class="px-1 py-0.2 rounded bg-slate-950 text-white font-mono text-[8px] font-black" x-text="recentEvents.length"></span>
                    </button>
                </div>

                <!-- Complete 5-Set Score Strip (Set 1-5 in 1 Row) -->
                <div class="grid grid-cols-5 gap-1 w-full font-mono">
                    <template x-for="(s, idx) in [0, 1, 2, 3, 4]" :key="idx">
                        <button type="button"
                                @click="setPeriodFast(idx + 1)"
                                class="py-1 px-0.5 rounded-lg transition touch-active font-mono cursor-pointer flex flex-col items-center justify-center border text-center select-none"
                                :class="(idx + 1) === currentPeriod 
                                    ? 'bg-blue-600 text-white font-black border-blue-400 shadow-md shadow-blue-600/30 ring-1 ring-white/60' 
                                    : (((homePeriodScores[idx] || 0) > 0 || (awayPeriodScores[idx] || 0) > 0)
                                        ? 'bg-slate-950 text-slate-200 hover:text-white hover:bg-slate-800 border-slate-700'
                                        : 'bg-slate-950/70 text-slate-400 hover:text-slate-200 hover:bg-slate-900 border-slate-800/80')"
                                :title="'Switch to Set ' + (idx + 1)">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-tight leading-tight"
                                  :class="(idx + 1) === currentPeriod ? 'text-amber-300' : 'text-slate-400'"
                                  x-text="'Set ' + (idx + 1)">
                            </span>
                            <span class="text-[8px] sm:text-[9px] font-bold mt-0.5 leading-tight"
                                  :class="(idx + 1) === currentPeriod ? 'text-white font-black' : 'text-slate-300'"
                                  x-text="(homePeriodScores[idx] || 0) + '-' + (awayPeriodScores[idx] || 0)">
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- AWAY SCORE BLOCK -->
            <div class="flex-1 min-w-0 flex items-center justify-between p-2 sm:p-2.5 rounded-xl border transition-all"
                 :class="[
                     isFlipped ? 'order-1' : 'order-3',
                     server === 'away' ? 'shadow-lg ring-1 ring-white/30' : 'bg-slate-950 border-slate-800'
                 ]"
                 :style="server === 'away' ? { backgroundColor: awayTeamColor + '26', borderColor: awayTeamColor } : { borderColor: awayTeamColor + '40' }">
                <!-- Score Counter & Direct Touch +/- -->
                <div class="flex items-center space-x-1.5 shrink-0">
                    <div class="min-w-[45px] sm:min-w-[55px] text-left">
                        <span class="font-mono text-3xl sm:text-4xl font-black text-white tracking-tighter leading-none scoreboard-glow" x-text="awayScore"></span>
                    </div>
                    <div class="flex flex-col space-y-1">
                        <button @click="adjustScoreFast('away', 1)" 
                                :style="{ backgroundColor: awayTeamColor }"
                                class="w-8 h-7 sm:w-9 sm:h-8 rounded-lg hover:brightness-110 touch-active text-white font-black text-xs flex items-center justify-center gap-1 shadow transition" 
                                :title="'+1 Away (' + (isFlipped ? '[' : ']') + ')'">
                            <span>+1</span>
                            <span class="px-1 py-0.2 rounded bg-black/40 border border-white/20 text-[8px] font-mono font-bold text-amber-300 leading-none" x-text="isFlipped ? '[' : ']'"></span>
                        </button>
                        <button @click="adjustScoreFast('away', -1)" class="w-8 h-6 sm:w-9 sm:h-6 rounded-lg bg-slate-800 hover:bg-slate-700 touch-active text-slate-300 font-bold text-[10px] flex items-center justify-center gap-1 transition" 
                                :title="'-1 Away (' + (isFlipped ? '{' : '}') + ')'">
                            <span>-1</span>
                            <span class="px-1 py-0.2 rounded bg-black/40 border border-white/20 text-[8px] font-mono font-bold text-amber-300 leading-none" x-text="isFlipped ? '{' : '}'"></span>
                        </button>
                    </div>
                </div>

                <div class="space-y-0.5 truncate text-right ml-2">
                    <div class="flex items-center justify-end space-x-1.5">
                        <template x-if="server === 'away'">
                            <span class="px-1.5 py-0.2 rounded bg-emerald-500 text-slate-950 text-[9px] font-black tracking-wider uppercase animate-pulse">SERVING</span>
                        </template>
                        <span class="text-[10px] font-black uppercase tracking-wider" :style="{ color: awayTeamColor }">AWAY</span>
                        <span class="w-2.5 h-2.5 rounded-full shadow-sm" :style="{ backgroundColor: awayTeamColor }"></span>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-white truncate leading-tight">{{ $game->away_display_name }}</h2>
                    <div class="flex items-center justify-end space-x-2 text-[10px] text-slate-400 font-mono">
                        <span>TO: <strong class="text-white" x-text="awayTimeouts"></strong></span>
                        <span>ROT: <strong class="text-white" x-text="'P' + awayRotation"></strong></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 2. CENTER TIER: ACTIVE PLAYERS GRID OR FULL-TIER ACTION PAD (Adaptive Left-to-Right Top-to-Bottom) -->
    <div class="flex-1 min-h-0 relative my-1 overflow-hidden">
        
        <!-- DEFAULT: 2-COLUMN ACTIVE PLAYERS GRID (When no player is selected) -->
        <div x-show="!selectedPlayer" class="w-full h-full grid grid-cols-2 gap-2 overflow-hidden">
            <!-- HOME ACTIVE PLAYERS (6 LARGE BUTTONS) -->
            <div class="flex flex-col h-full bg-slate-900/90 border border-slate-800 rounded-2xl p-2 overflow-hidden shadow-inner transition-all"
                 :class="isFlipped ? 'order-2' : 'order-1'">
                <div class="flex items-center justify-between pb-1 mb-1 border-b border-slate-800 text-[11px] font-bold">
                    <span class="uppercase tracking-wider truncate flex items-center gap-1.5" :style="{ color: homeTeamColor }">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: homeTeamColor }"></span>
                        <span>{{ $game->home_display_name }} (Court)</span>
                    </span>
                    
                    <div class="flex items-center space-x-1.5">
                        <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">Order 1-6</span>
                        <button type="button" @click="@this.openRosterModal('home')" 
                                class="px-2 py-0.5 rounded-lg bg-slate-950 hover:bg-slate-800 border text-white text-[10px] font-bold transition flex items-center gap-1 shadow-sm"
                                :style="{ borderColor: homeTeamColor + '60' }"
                                title="Manage Home Team Roster (Hotkey: R)">
                            <svg class="w-3 h-3" :style="{ color: homeTeamColor }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Roster</span>
                            <span class="px-1 py-0.2 rounded bg-slate-800 border border-slate-700 text-[8px] font-mono text-amber-300 ml-0.5">R</span>
                        </button>
                    </div>
                </div>

                <!-- 6 Large Touch Buttons Grid (2 cols x 3 rows) -->
                <div class="flex-1 min-h-0 grid grid-cols-2 grid-rows-3 gap-1.5">
                    <template x-for="(player, idx) in homeCourt" :key="player.id || idx">
                        <button @click="openActionPad('home', player.jersey_number, player.player_name, player.id)"
                                type="button"
                                class="h-full w-full rounded-xl border-2 transition-all p-2 flex flex-col justify-between items-center text-center touch-active group"
                                :class="[
                                    selectedPlayer && selectedPlayer.side === 'home' && selectedPlayer.jersey == player.jersey_number 
                                        ? 'text-white border-white ring-2 ring-white/50' 
                                        : 'bg-slate-950/90 hover:bg-slate-900'
                                ]"
                                :style="selectedPlayer && selectedPlayer.side === 'home' && selectedPlayer.jersey == player.jersey_number 
                                    ? { backgroundColor: homeTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + homeTeamColor + '99' } 
                                    : { borderColor: homeTeamColor + '40' }">
                            
                            <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                                <span class="font-bold text-amber-300" x-text="'ORD ' + (idx + 1)"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="player.position || 'ATH'"></span>
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

            <!-- AWAY ACTIVE PLAYERS (6 LARGE BUTTONS) -->
            <div class="flex flex-col h-full bg-slate-900/90 border border-slate-800 rounded-2xl p-2 overflow-hidden shadow-inner transition-all"
                 :class="isFlipped ? 'order-1' : 'order-2'">
                <div class="flex items-center justify-between pb-1 mb-1 border-b border-slate-800 text-[11px] font-bold">
                    <span class="uppercase tracking-wider truncate flex items-center gap-1.5" :style="{ color: awayTeamColor }">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: awayTeamColor }"></span>
                        <span>{{ $game->away_display_name }} (Court)</span>
                    </span>
                    
                    <div class="flex items-center space-x-1.5">
                        <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">Order 1-6</span>
                        <button type="button" @click="@this.openRosterModal('away')" 
                                class="px-2 py-0.5 rounded-lg bg-slate-950 hover:bg-slate-800 border text-white text-[10px] font-bold transition flex items-center gap-1 shadow-sm"
                                :style="{ borderColor: awayTeamColor + '60' }"
                                title="Manage Away Team Roster (Hotkey: Shift+R)">
                            <svg class="w-3 h-3" :style="{ color: awayTeamColor }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Roster</span>
                            <span class="px-1 py-0.2 rounded bg-slate-800 border border-slate-700 text-[8px] font-mono text-amber-300 ml-0.5">⇧R</span>
                        </button>
                    </div>
                </div>

                <!-- 6 Large Touch Buttons Grid (2 cols x 3 rows) -->
                <div class="flex-1 min-h-0 grid grid-cols-2 grid-rows-3 gap-1.5">
                    <template x-for="(player, idx) in awayCourt" :key="player.id || idx">
                        <button @click="openActionPad('away', player.jersey_number, player.player_name, player.id)"
                                type="button"
                                class="h-full w-full rounded-xl border-2 transition-all p-2 flex flex-col justify-between items-center text-center touch-active group"
                                :class="[
                                    selectedPlayer && selectedPlayer.side === 'away' && selectedPlayer.jersey == player.jersey_number 
                                        ? 'text-white border-white ring-2 ring-white/50' 
                                        : 'bg-slate-950/90 hover:bg-slate-900'
                                ]"
                                :style="selectedPlayer && selectedPlayer.side === 'away' && selectedPlayer.jersey == player.jersey_number 
                                    ? { backgroundColor: awayTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + awayTeamColor + '99' } 
                                    : { borderColor: awayTeamColor + '40' }">
                            
                            <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                                <span class="font-bold text-amber-300" x-text="'ORD ' + (idx + 1)"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="player.position || 'ATH'"></span>
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

        <!-- ACTIVE: FULL ACTION PAD (Takes up 100% of the player card area left-to-right, top-to-bottom) -->
        <template x-if="selectedPlayer">
            <div class="w-full h-full bg-slate-900 border-2 rounded-2xl p-2.5 sm:p-3.5 md:p-4 shadow-2xl flex flex-col justify-between overflow-hidden select-none"
                 :style="{ borderColor: selectedPlayer.side === 'home' ? homeTeamColor : awayTeamColor, boxShadow: '0 8px 30px -4px ' + (selectedPlayer.side === 'home' ? homeTeamColor : awayTeamColor) + '60' }">
                
                <!-- Target Player Header -->
                <div class="flex items-center justify-between pb-1.5 sm:pb-2 border-b border-slate-800 shrink-0">
                    <div class="flex items-center space-x-2.5 sm:space-x-3">
                        <span class="px-3 sm:px-4 py-1 rounded-xl text-white font-mono font-black text-xl sm:text-2xl shadow-lg flex items-center justify-center shrink-0"
                              :style="{ backgroundColor: selectedPlayer.side === 'home' ? homeTeamColor : awayTeamColor }"
                              x-text="'#' + selectedPlayer.jersey">
                        </span>
                        <div>
                            <h3 class="text-base sm:text-xl font-black text-white leading-tight" x-text="selectedPlayer.name"></h3>
                            <span class="text-[10px] sm:text-xs font-mono font-bold uppercase tracking-wider"
                                  :style="{ color: selectedPlayer.side === 'home' ? homeTeamColor : awayTeamColor }"
                                  x-text="(selectedPlayer.side === 'home' ? homeTeamName : awayTeamName).toUpperCase() + ' (' + selectedPlayer.side.toUpperCase() + ')'">
                            </span>
                        </div>
                    </div>

                    <button @click="selectedPlayer = null" class="p-1.5 sm:p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white hover:bg-rose-900/80 hover:border-rose-500 border border-slate-700 transition shadow shrink-0 flex items-center gap-1.5" title="Close (Esc)">
                        <span class="text-xs font-bold font-mono text-slate-400 hidden sm:inline">Close</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Structured Action Groups (Fills middle height evenly) -->
                <div class="flex-1 flex flex-col justify-evenly gap-1.5 sm:gap-2.5 py-1 sm:py-1.5 min-h-0">
                    
                    <!-- BLOCK 1: POINTS & ATTACK (+1 PT) -->
                    <div class="bg-slate-950/70 p-2 sm:p-2.5 rounded-xl border border-slate-800/90 shadow-inner flex flex-col justify-center flex-1 min-h-0">
                        <div class="flex items-center justify-between px-1 pb-1">
                            <span class="text-[10px] sm:text-xs font-mono font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Point Scoring
                            </span>
                            <span class="text-[9px] sm:text-[10px] font-mono text-slate-400 font-medium">+1 Point to Team</span>
                        </div>

                        <div class="grid grid-cols-4 gap-2 sm:gap-3 flex-1 min-h-0">
                            <!-- KILL -->
                            <button @click="executeAction('K')" class="w-full py-2 sm:py-3 px-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-[0.98] transition-all text-white border-2 border-emerald-400/80 shadow-md shadow-emerald-950/50 flex flex-col items-center justify-center flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs sm:text-sm md:text-base font-black tracking-wide">+1 KILL</span>
                                    <span class="px-1.5 py-0.2 rounded bg-emerald-950/80 border border-emerald-300/60 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300 shadow">K</span>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-mono opacity-90 font-semibold">Attack Point</span>
                            </button>

                            <!-- ACE -->
                            <button @click="executeAction('A')" class="w-full py-2 sm:py-3 px-2 rounded-xl bg-teal-600 hover:bg-teal-500 active:scale-[0.98] transition-all text-white border-2 border-teal-400/80 shadow-md shadow-teal-950/50 flex flex-col items-center justify-center flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs sm:text-sm md:text-base font-black tracking-wide">+1 ACE</span>
                                    <span class="px-1.5 py-0.2 rounded bg-teal-950/80 border border-teal-300/60 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300 shadow">A</span>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-mono opacity-90 font-semibold">Service Point</span>
                            </button>

                            <!-- BLK SOLO -->
                            <button @click="executeAction('B')" class="w-full py-2 sm:py-3 px-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 active:scale-[0.98] transition-all text-white border-2 border-cyan-400/80 shadow-md shadow-cyan-950/50 flex flex-col items-center justify-center flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs sm:text-sm md:text-base font-black tracking-wide">+1 SOLO</span>
                                    <span class="px-1.5 py-0.2 rounded bg-cyan-950/80 border border-cyan-300/60 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300 shadow">B</span>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-mono opacity-90 font-semibold">Solo Stuff</span>
                            </button>

                            <!-- BLK AST -->
                            <button @click="executeAction('C')" class="w-full py-2 sm:py-3 px-2 rounded-xl bg-sky-600 hover:bg-sky-500 active:scale-[0.98] transition-all text-white border-2 border-sky-400/80 shadow-md shadow-sky-950/50 flex flex-col items-center justify-center flex-1">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs sm:text-sm md:text-base font-black tracking-wide">+1 BLK AST</span>
                                    <span class="px-1.5 py-0.2 rounded bg-sky-950/80 border border-sky-300/60 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300 shadow">C</span>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-mono opacity-90 font-semibold">Shared Stuff</span>
                            </button>
                        </div>
                    </div>

                    <!-- BLOCK 2: PLAYMAKING / DEFENSE -->
                    <div class="bg-slate-950/70 p-2 sm:p-2.5 rounded-xl border border-slate-800/90 shadow-inner flex flex-col justify-center">
                        <div class="flex items-center justify-between px-1 pb-1">
                            <span class="text-[10px] sm:text-xs font-mono font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                Defense & In-Play Actions
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 sm:gap-3">
                            <!-- DIG -->
                            <button @click="executeAction('D')" class="py-2 sm:py-2.5 px-1 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-[0.98] transition-all text-white border border-blue-400/80 shadow flex flex-col items-center justify-center">
                                <div class="flex items-center gap-1">
                                    <span class="text-[11px] sm:text-xs md:text-sm font-black tracking-wide">DIG</span>
                                    <span class="px-1 py-0.2 rounded bg-blue-950/80 border border-blue-300/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">D</span>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-mono opacity-85">Defensive Dig</span>
                            </button>

                            <!-- SET ASSIST -->
                            <button @click="executeAction('Z')" class="py-2 sm:py-2.5 px-1 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-[0.98] transition-all text-white border border-indigo-400/80 shadow flex flex-col items-center justify-center">
                                <div class="flex items-center gap-1">
                                    <span class="text-[11px] sm:text-xs md:text-sm font-black tracking-wide">ASSIST</span>
                                    <span class="px-1 py-0.2 rounded bg-indigo-950 border border-indigo-300/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">Z</span>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-mono opacity-85">Set Assist</span>
                            </button>

                            <!-- ATTACK ATTEMPT -->
                            <button @click="executeAction('T')" class="py-2 sm:py-2.5 px-1 rounded-xl bg-slate-700 hover:bg-slate-600 active:scale-[0.98] transition-all text-white border border-slate-500/80 shadow flex flex-col items-center justify-center">
                                <div class="flex items-center gap-1">
                                    <span class="text-[11px] sm:text-xs md:text-sm font-black tracking-wide">ATTACK (0pt)</span>
                                    <span class="px-1 py-0.2 rounded bg-slate-900 border border-slate-400/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">T</span>
                                </div>
                                <span class="text-[8px] sm:text-[9px] font-mono opacity-85">Attempt / In-Play</span>
                            </button>
                        </div>
                    </div>

                    <!-- BLOCK 3: ERRORS (OPPONENT POINT) -->
                    <div class="bg-slate-950/70 p-2 sm:p-2.5 rounded-xl border border-rose-900/40 shadow-inner flex flex-col justify-center">
                        <div class="flex items-center justify-between px-1 pb-1">
                            <span class="text-[10px] sm:text-xs font-mono font-bold text-rose-400 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                Errors (Opponent Point)
                            </span>
                        </div>

                        <div class="grid grid-cols-4 gap-2 sm:gap-2.5">
                            <!-- ATTACK ERR -->
                            <button @click="executeAction('E')" class="py-1.5 sm:py-2 px-1 rounded-lg bg-rose-700 hover:bg-rose-600 active:scale-[0.98] transition-all text-white border border-rose-500/80 shadow flex flex-col items-center justify-center">
                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] sm:text-xs font-black">ATTACK ERR</span>
                                    <span class="px-1 py-0.2 rounded bg-rose-950 border border-rose-300/50 text-[8px] font-mono font-bold text-amber-300">E</span>
                                </div>
                                <span class="text-[8px] font-mono opacity-85">Out/Net</span>
                            </button>

                            <!-- SERVE ERR -->
                            <button @click="executeAction('S')" class="py-1.5 sm:py-2 px-1 rounded-lg bg-rose-800 hover:bg-rose-700 active:scale-[0.98] transition-all text-white border border-rose-600/80 shadow flex flex-col items-center justify-center">
                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] sm:text-xs font-black">SERVE ERR</span>
                                    <span class="px-1 py-0.2 rounded bg-rose-950 border border-rose-300/50 text-[8px] font-mono font-bold text-amber-300">S</span>
                                </div>
                                <span class="text-[8px] font-mono opacity-85">Fault/Net</span>
                            </button>

                            <!-- BHE ERR -->
                            <button @click="executeAction('H')" class="py-1.5 sm:py-2 px-1 rounded-lg bg-red-800 hover:bg-red-700 active:scale-[0.98] transition-all text-white border border-red-600/80 shadow flex flex-col items-center justify-center">
                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] sm:text-xs font-black">BHE (HAND)</span>
                                    <span class="px-1 py-0.2 rounded bg-red-950 border border-red-300/50 text-[8px] font-mono font-bold text-amber-300">H</span>
                                </div>
                                <span class="text-[8px] font-mono opacity-85">Double/Lift</span>
                            </button>

                            <!-- RECEPTION ERR -->
                            <button @click="executeAction('R')" class="py-1.5 sm:py-2 px-1 rounded-lg bg-red-900 hover:bg-red-800 active:scale-[0.98] transition-all text-white border border-red-700/80 shadow flex flex-col items-center justify-center">
                                <div class="flex items-center gap-1">
                                    <span class="text-[10px] sm:text-xs font-black">RECEPT ERR</span>
                                    <span class="px-1 py-0.2 rounded bg-red-950 border border-red-300/50 text-[8px] font-mono font-bold text-amber-300">R</span>
                                </div>
                                <span class="text-[8px] font-mono opacity-85">Pass Shank</span>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- Footer Bar -->
                <div class="pt-1.5 sm:pt-2 border-t border-slate-800 flex items-center justify-between shrink-0">
                    <span class="text-[10px] sm:text-xs text-slate-400 font-medium hidden sm:inline">
                        Press key on keyboard or tap button • <kbd class="px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 font-mono text-[9px] sm:text-[10px] text-slate-300">Esc</kbd> to close
                    </span>

                    <button @click="openLineupModal()" class="px-3.5 py-1.5 sm:py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 hover:border-cyan-500 text-cyan-300 hover:text-white font-bold text-xs shadow transition flex items-center gap-1.5 ml-auto">
                        <span>Line Up Subs</span>
                        <span class="px-1.5 py-0.2 rounded bg-cyan-950 border border-cyan-400/50 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300">Tab</span>
                    </button>
                </div>
            </div>
        </template>

    </div>

    <!-- 3. BOTTOM TIER: NON-PLAYER-SCOPED ACTIONS DOCK (Single Layer, Even Horizontal Distribution) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2 shrink-0 shadow-xl mt-auto">
        <div class="grid grid-cols-10 gap-1 text-xs font-bold items-stretch">
            
            <!-- HOME TIMEOUT -->
            <button @click="callTimeoutFast('home')" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-slate-950 hover:bg-slate-800 touch-active border text-white flex flex-col items-center justify-center min-w-0"
                    :style="{ borderColor: homeTeamColor + '50' }">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate" :style="{ color: homeTeamColor }">HOME TO</span>
                    <span class="px-1 py-0.2 rounded bg-slate-900 border border-slate-700 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">H</span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-slate-400 font-mono truncate max-w-full" x-text="homeTimeouts + ' Left'"></span>
            </button>

            <!-- ROTATE HOME -->
            <button @click="rotateTeamFast('home')" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-slate-950 hover:bg-slate-800 touch-active border text-white flex flex-col items-center justify-center min-w-0"
                    :style="{ borderColor: homeTeamColor + '60' }">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate" :style="{ color: homeTeamColor }">ROT HOME</span>
                    <span class="px-1 py-0.2 rounded bg-slate-900 border border-slate-700 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">W</span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-slate-300 font-mono truncate max-w-full" x-text="'Pos ' + ((homeRotation % 6) + 1)"></span>
            </button>

            <!-- TOGGLE SERVER -->
            <button @click="toggleServer()" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-700 text-white flex flex-col items-center justify-center shadow min-w-0">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate">SERVER</span>
                    <span class="px-1 py-0.2 rounded bg-slate-950/80 border border-slate-600 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">V</span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-emerald-400 font-mono truncate max-w-full" x-text="server.toUpperCase()"></span>
            </button>

            <!-- FLIP COURT SIDES -->
            <button @click="toggleFlipCourt()" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-700 text-cyan-300 flex flex-col items-center justify-center shadow transition min-w-0"
                    title="Flip Court Sides (Hotkey: \ or Shift+X)">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate">FLIP SIDES</span>
                    <span class="px-1 py-0.2 rounded bg-slate-950/80 border border-slate-600 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">\</span>
                </div>
                <span class="text-[8px] font-mono text-slate-300 truncate max-w-full" x-text="isFlipped ? 'Away/Home' : 'Home/Away'"></span>
            </button>

            <!-- ADVANCE SET -->
            <button @click="nextPeriodFast()" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 touch-active text-white flex flex-col items-center justify-center shadow-lg shadow-emerald-600/30 min-w-0">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate">+ SET</span>
                    <span class="px-1 py-0.2 rounded bg-emerald-950/80 border border-emerald-400/50 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">N</span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-emerald-100 font-mono truncate max-w-full" x-text="'Set ' + (currentPeriod + 1)"></span>
            </button>

            <!-- SUBSTITUTION DOCK BUTTON (LINE UP & SEND IN) -->
            <div class="h-full min-w-0 flex">
                <button x-show="pendingSubCount() === 0"
                        @click="openLineupModal()" 
                        class="w-full h-full min-h-[42px] py-1 px-1 rounded-xl bg-indigo-600 hover:bg-indigo-500 touch-active text-white flex flex-col items-center justify-center shadow-lg shadow-indigo-600/30 min-w-0">
                    <div class="flex items-center gap-0.5 max-w-full truncate">
                        <span class="text-[9px] sm:text-[10px] font-black uppercase truncate">SUB</span>
                        <span class="px-1 py-0.2 rounded bg-indigo-950/80 border border-indigo-400/50 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">S</span>
                    </div>
                    <span class="text-[8px] sm:text-[9px] text-indigo-200 font-mono truncate max-w-full">Subs</span>
                </button>

                <div x-show="pendingSubCount() > 0"
                     class="w-full h-full p-0.5 rounded-xl bg-indigo-950 border border-amber-400/80 shadow-lg shadow-indigo-950/60 flex gap-0.5 items-stretch min-w-0">
                    <!-- Left Area: Open Lineup to add/modify pending subs -->
                    <button @click="openLineupModal()" 
                            class="flex-1 py-0.5 px-0.5 rounded bg-indigo-600 hover:bg-indigo-500 touch-active text-white flex flex-col items-center justify-center transition min-w-0">
                        <div class="flex items-center gap-0.5 max-w-full truncate">
                            <span class="text-[8px] font-black uppercase truncate">LINE</span>
                            <span class="px-0.5 py-0.2 rounded bg-indigo-950 border border-indigo-400 text-[6px] font-mono font-bold text-amber-300 shrink-0">S</span>
                        </div>
                        <span class="text-[7px] font-mono text-amber-300 font-bold truncate max-w-full" x-text="pendingSubCount()"></span>
                    </button>

                    <!-- Dedicated Action: SEND IN -->
                    <button @click.stop="openExecuteSubModal()" 
                            class="py-0.5 px-1 rounded bg-emerald-600 hover:bg-emerald-500 touch-active text-white flex flex-col items-center justify-center transition shadow-md shadow-emerald-500/50 min-w-0">
                        <span class="text-[8px] font-black uppercase tracking-tight text-white leading-tight truncate">
                            IN
                        </span>
                    </button>
                </div>
            </div>

            <!-- MANAGE ROSTERS ON THE FLY -->
            <button @click="@this.openRosterModal('home'); playSound('tap')" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-600 text-cyan-300 flex flex-col items-center justify-center shadow min-w-0">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate">ROSTERS</span>
                    <span class="px-1 py-0.2 rounded bg-slate-950/80 border border-slate-600 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">R</span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-slate-300 font-mono truncate max-w-full">Edit</span>
            </button>

            <!-- ROTATE AWAY -->
            <button @click="rotateTeamFast('away')" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-slate-950 hover:bg-slate-800 touch-active border text-white flex flex-col items-center justify-center min-w-0"
                    :style="{ borderColor: awayTeamColor + '60' }">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate" :style="{ color: awayTeamColor }">ROT AWAY</span>
                    <span class="px-1 py-0.2 rounded bg-slate-900 border border-slate-700 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">E</span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-slate-300 font-mono truncate max-w-full" x-text="'Pos ' + ((awayRotation % 6) + 1)"></span>
            </button>

            <!-- AWAY TIMEOUT -->
            <button @click="callTimeoutFast('away')" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-slate-950 hover:bg-slate-800 touch-active border text-white flex flex-col items-center justify-center min-w-0"
                    :style="{ borderColor: awayTeamColor + '50' }">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate" :style="{ color: awayTeamColor }">AWAY TO</span>
                    <span class="px-1 py-0.2 rounded bg-slate-900 border border-slate-700 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">A</span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-slate-400 font-mono truncate max-w-full" x-text="awayTimeouts + ' Left'"></span>
            </button>

            <!-- UNDO LAST PLAY -->
            <button @click="undo()" 
                    class="h-full min-h-[42px] py-1 px-1 rounded-xl bg-rose-950/80 hover:bg-rose-900 touch-active border border-rose-800 text-rose-300 flex flex-col items-center justify-center shadow min-w-0">
                <div class="flex items-center gap-0.5 max-w-full truncate">
                    <span class="text-[9px] sm:text-[10px] font-black uppercase truncate">UNDO</span>
                    <span class="px-1 py-0.2 rounded bg-black/60 border border-rose-500/40 text-[7px] sm:text-[8px] font-mono font-bold text-amber-300 shrink-0">U</span>
                </div>
                <span class="text-[8px] sm:text-[9px] text-rose-400 font-mono truncate max-w-full">Undo</span>
            </button>

        </div>
    </div>



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
                <!-- HOME BENCH -->
                <div class="flex flex-col bg-slate-950/80 border rounded-2xl p-2.5 space-y-2 transition-all"
                     :class="isFlipped ? 'order-2' : 'order-1'"
                     :style="{ borderColor: homeTeamColor + '40' }">
                    <div class="flex items-center justify-between pb-1 border-b" :style="{ borderColor: homeTeamColor + '30' }">
                        <span class="text-xs font-black uppercase truncate flex items-center gap-1.5" :style="{ color: homeTeamColor }">
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: homeTeamColor }"></span>
                            <span>{{ $game->home_display_name }} (Bench)</span>
                        </span>
                        <span class="text-[9px] font-mono text-slate-400" x-text="homeBench.length + ' Available'"></span>
                    </div>

                    <div class="space-y-1.5 flex-1 overflow-y-auto pr-0.5">
                        <template x-for="p in homeBench" :key="p.id || p.jersey_number">
                            <button @click="togglePendingSub('home', p)" 
                                    type="button"
                                    class="w-full p-2 rounded-xl border text-left flex items-center justify-between transition touch-active"
                                    :class="isPlayerPending('home', p.jersey_number) 
                                        ? 'text-white border-white shadow-md ring-2 ring-white/50' 
                                        : 'bg-slate-900 text-slate-200 border-slate-800 hover:border-slate-600'"
                                    :style="isPlayerPending('home', p.jersey_number) ? { backgroundColor: homeTeamColor } : {}">
                                <div class="flex items-center space-x-2 truncate">
                                    <span class="font-mono font-black text-sm" x-text="'#' + p.jersey_number"></span>
                                    <span class="truncate text-xs font-semibold" x-text="p.player_name"></span>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold shrink-0 ml-1"
                                      :class="isPlayerPending('home', p.jersey_number) ? 'bg-white text-slate-950' : 'bg-slate-800 text-slate-400'"
                                      x-text="isPlayerPending('home', p.jersey_number) ? 'LINED UP' : 'ADD'">
                                </span>
                            </button>
                        </template>
                        <div x-show="homeBench.length === 0" class="text-xs text-slate-500 text-center py-6">
                            No bench players on Home roster.
                        </div>
                    </div>
                </div>

                <!-- AWAY BENCH -->
                <div class="flex flex-col bg-slate-950/80 border rounded-2xl p-2.5 space-y-2 transition-all"
                     :class="isFlipped ? 'order-1' : 'order-2'"
                     :style="{ borderColor: awayTeamColor + '40' }">
                    <div class="flex items-center justify-between pb-1 border-b" :style="{ borderColor: awayTeamColor + '30' }">
                        <span class="text-xs font-black uppercase truncate flex items-center gap-1.5" :style="{ color: awayTeamColor }">
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: awayTeamColor }"></span>
                            <span>{{ $game->away_display_name }} (Bench)</span>
                        </span>
                        <span class="text-[9px] font-mono text-slate-400" x-text="awayBench.length + ' Available'"></span>
                    </div>

                    <div class="space-y-1.5 flex-1 overflow-y-auto pr-0.5">
                        <template x-for="p in awayBench" :key="p.id || p.jersey_number">
                            <button @click="togglePendingSub('away', p)" 
                                    type="button"
                                    class="w-full p-2 rounded-xl border text-left flex items-center justify-between transition touch-active"
                                    :class="isPlayerPending('away', p.jersey_number) 
                                        ? 'text-white border-white shadow-md ring-2 ring-white/50' 
                                        : 'bg-slate-900 text-slate-200 border-slate-800 hover:border-slate-600'"
                                    :style="isPlayerPending('away', p.jersey_number) ? { backgroundColor: awayTeamColor } : {}">
                                <div class="flex items-center space-x-2 truncate">
                                    <span class="font-mono font-black text-sm" x-text="'#' + p.jersey_number"></span>
                                    <span class="truncate text-xs font-semibold" x-text="p.player_name"></span>
                                </div>
                                <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold shrink-0 ml-1"
                                      :class="isPlayerPending('away', p.jersey_number) ? 'bg-white text-slate-950' : 'bg-slate-800 text-slate-400'"
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
                <!-- HOME TEAM SUBS -->
                <div class="flex flex-col bg-slate-950/80 border rounded-2xl p-3 space-y-2.5 transition-all"
                     :class="isFlipped ? 'order-2' : 'order-1'"
                     :style="{ borderColor: homeTeamColor + '40' }">
                    <div class="flex items-center justify-between pb-1 border-b" :style="{ borderColor: homeTeamColor + '30' }">
                        <span class="text-xs font-black uppercase truncate flex items-center gap-1.5" :style="{ color: homeTeamColor }">
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: homeTeamColor }"></span>
                            <span>{{ $game->home_display_name }}</span>
                        </span>
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
                        <div class="p-2 bg-slate-900/90 border rounded-xl space-y-1" :style="{ borderColor: homeTeamColor + '40' }">
                            <span class="text-[10px] font-bold uppercase tracking-wider block" :style="{ color: homeTeamColor }">Incoming Players:</span>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="inP in pendingSubs.home" :key="inP.id || inP.jersey_number">
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg border text-xs font-mono font-bold"
                                          :style="{ backgroundColor: homeTeamColor + '30', borderColor: homeTeamColor + '60', color: '#ffffff' }">
                                        <span class="text-[9px] font-black text-amber-300">IN</span>
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

                <!-- AWAY TEAM SUBS -->
                <div class="flex flex-col bg-slate-950/80 border rounded-2xl p-3 space-y-2.5 transition-all"
                     :class="isFlipped ? 'order-1' : 'order-2'"
                     :style="{ borderColor: awayTeamColor + '40' }">
                    <div class="flex items-center justify-between pb-1 border-b" :style="{ borderColor: awayTeamColor + '30' }">
                        <span class="text-xs font-black uppercase truncate flex items-center gap-1.5" :style="{ color: awayTeamColor }">
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: awayTeamColor }"></span>
                            <span>{{ $game->away_display_name }}</span>
                        </span>
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
                        <div class="p-2 bg-slate-900/90 border rounded-xl space-y-1" :style="{ borderColor: awayTeamColor + '40' }">
                            <span class="text-[10px] font-bold uppercase tracking-wider block" :style="{ color: awayTeamColor }">Incoming Players:</span>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="inP in pendingSubs.away" :key="inP.id || inP.jersey_number">
                                    <span class="inline-flex items-center space-x-1 px-2 py-0.5 rounded-lg border text-xs font-mono font-bold"
                                          :style="{ backgroundColor: awayTeamColor + '30', borderColor: awayTeamColor + '60', color: '#ffffff' }">
                                        <span class="text-[9px] font-black text-amber-300">IN</span>
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

    <!-- 3. PLAY-BY-PLAY LOG VIEWER & AUDIT LOG MODAL -->
    <div x-show="showPlayByPlayModal" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none" style="display: none;"
         @click.self="closePlayByPlayModal()">
        
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-4xl w-full p-4 sm:p-5 shadow-2xl space-y-3 max-h-[94vh] flex flex-col">
            
            <!-- Header: Title, Search, Manual Event & Close -->
            <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-slate-800 shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="p-1.5 rounded-xl bg-blue-600/20 text-blue-400 border border-blue-500/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm sm:text-base font-black uppercase text-white tracking-wider">Play-by-Play & Audit Log</span>
                            <span class="px-2 py-0.5 rounded-full bg-slate-800 text-amber-300 font-mono text-[10px] font-bold"
                                  x-text="filteredRecentEvents().length + ' / ' + recentEvents.length + ' Events'">
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400">Complete event history: timeouts, substitutions, rotations, scores & audit edits.</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <!-- + Log Manual Event Button -->
                    <button @click="openAddEventModal()" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/30 transition flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>+ Log Event</span>
                    </button>

                    <!-- Back to Game button -->
                    <button @click="closePlayByPlayModal()" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs border border-slate-700 transition flex items-center gap-1.5">
                        <span>← Back</span>
                        <span class="px-1 py-0.2 rounded bg-slate-950 text-[9px] font-mono text-slate-400">Esc</span>
                    </button>
                </div>
            </div>

            <!-- Filters Bar & Search -->
            <div class="space-y-2 shrink-0 bg-slate-950/80 p-2.5 rounded-2xl border border-slate-800">
                <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                    <!-- Team Filter -->
                    <div class="flex items-center space-x-1">
                        <span class="text-[10px] text-slate-400 font-bold uppercase mr-1">Team:</span>
                        <button @click="pbpFilterTeam = 'all'" 
                                class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                                :class="pbpFilterTeam === 'all' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white'">
                            <span>All</span>
                        </button>
                        <button @click="pbpFilterTeam = 'home'" 
                                class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                                :class="pbpFilterTeam === 'home' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-blue-300'">
                            <span>{{ $game->home_display_name }}</span>
                        </button>
                        <button @click="pbpFilterTeam = 'away'" 
                                class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition flex items-center gap-1"
                                :class="pbpFilterTeam === 'away' ? 'bg-rose-600 text-white' : 'text-slate-400 hover:text-rose-300'">
                            <span>{{ $game->away_display_name }}</span>
                        </button>
                    </div>

                    <!-- Set Filter -->
                    <div class="flex items-center space-x-1">
                        <span class="text-[10px] text-slate-400 font-bold uppercase mr-1">Set:</span>
                        <button @click="pbpFilterPeriod = 'all'" 
                                class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition"
                                :class="pbpFilterPeriod === 'all' ? 'bg-slate-700 text-white' : 'text-slate-400 hover:text-white'">
                            All
                        </button>
                        <template x-for="p in Math.min(5, Math.max(3, currentPeriod))" :key="p">
                            <button @click="pbpFilterPeriod = p" 
                                    class="px-2 py-0.5 rounded-lg text-[10px] font-bold transition font-mono"
                                    :class="pbpFilterPeriod == p ? 'bg-emerald-600 text-white' : 'text-slate-400 hover:text-emerald-300'"
                                    x-text="'Set ' + p">
                            </button>
                        </template>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex items-center space-x-1">
                        <span class="text-[10px] text-slate-400 font-bold uppercase mr-1">Category:</span>
                        <select x-model="pbpFilterCategory" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-0.5 text-[10px] font-bold text-slate-200 focus:outline-none">
                            <option value="all">All Events</option>
                            <option value="points">Points (Kills, Aces, Blocks)</option>
                            <option value="errors">Errors (Attack, Service, Ball Handling, Reception)</option>
                            <option value="defense">Defense & Setting (Digs, Assists)</option>
                            <option value="administrative">Timeouts, Subs, Rotations & Adjustments</option>
                        </select>
                    </div>
                </div>

                <!-- Instant Search Input -->
                <div class="relative">
                    <input type="text" 
                           x-model="pbpSearchQuery" 
                           placeholder="Filter events by player name, jersey number (#7), action type, or notes..."
                           class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 pl-8 text-xs text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none font-mono">
                    <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <button x-show="pbpSearchQuery" @click="pbpSearchQuery = ''" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-white text-xs">✕</button>
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
                            
                            <!-- Left: Set & Team Badge & Jersey -->
                            <div class="flex items-center space-x-2 shrink-0">
                                <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[9px] font-black"
                                      x-text="'S' + (event.period || 1)">
                                </span>
                                
                                <template x-if="event.jersey_number">
                                    <span class="px-2 py-0.5 rounded-lg text-white font-mono font-black text-xs shadow-sm"
                                          :class="event.team_side === 'home' ? 'bg-blue-600' : 'bg-rose-600'"
                                          x-text="'#' + event.jersey_number">
                                    </span>
                                </template>
                                <template x-if="!event.jersey_number">
                                    <span class="px-2 py-0.5 rounded-lg text-slate-300 font-mono font-bold text-[10px] bg-slate-800 border border-slate-700">
                                        TEAM
                                    </span>
                                </template>
                            </div>

                            <!-- Center: Player & Action Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-1.5 truncate">
                                    <span class="text-xs font-bold text-white truncate" x-text="event.player_name || 'Official Action'"></span>
                                    <span class="text-[10px] font-mono uppercase font-bold"
                                          :class="event.team_side === 'home' ? 'text-blue-400' : 'text-rose-400'"
                                          x-text="'(' + (event.team_side === 'home' ? 'Home' : 'Away') + ')'">
                                    </span>

                                    <!-- Event Type Tag Badge -->
                                    <template x-if="event.action_code === 'TIMEOUT'">
                                        <span class="px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[9px] font-bold">TIMEOUT</span>
                                    </template>
                                    <template x-if="event.action_code === 'SUB'">
                                        <span class="px-1.5 py-0.2 rounded bg-purple-500/20 text-purple-300 border border-purple-500/30 text-[9px] font-bold">SUB</span>
                                    </template>
                                    <template x-if="event.action_code === 'ROTATE'">
                                        <span class="px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[9px] font-bold">ROTATE</span>
                                    </template>
                                    <template x-if="['K', 'A', 'B', 'C'].includes(event.action_code)">
                                        <span class="px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[9px] font-bold">+PT</span>
                                    </template>
                                    <template x-if="['E', 'S', 'H', 'R'].includes(event.action_code)">
                                        <span class="px-1.5 py-0.2 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[9px] font-bold">ERROR</span>
                                    </template>
                                    <template x-if="event.action_code === 'SCORE_ADJ'">
                                        <span class="px-1.5 py-0.2 rounded bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-[9px] font-bold">ADJ</span>
                                    </template>
                                </div>
                                <div class="text-[11px] font-medium text-slate-300 truncate" x-text="event.description || event.action_name"></div>
                            </div>

                            <!-- Right: Score After & Edit/Delete Action Buttons -->
                            <div class="flex items-center space-x-2 shrink-0">
                                <div class="text-right hidden sm:block">
                                    <span class="text-[9px] font-mono text-slate-400 block leading-tight">SCORE</span>
                                    <span class="font-mono text-xs font-black text-white"
                                          x-text="(event.home_score_after ?? homeScore) + ' - ' + (event.away_score_after ?? awayScore)">
                                    </span>
                                </div>

                                <!-- Edit Button -->
                                <button @click.stop="startEditingEvent(event)" 
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-300 hover:text-cyan-200 transition shadow touch-active" 
                                        title="Edit this play / audit entry">
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

    <!-- 4. INLINE PLAY / AUDIT LOG EDIT MODAL -->
    <template x-if="editingEvent">
        <div class="fixed inset-0 bg-black/90 backdrop-blur-md z-60 flex items-center justify-center p-3 select-none"
             @click.self="editingEvent = null">
            
            <div class="bg-slate-900 border-2 rounded-3xl max-w-lg w-full p-4 sm:p-5 shadow-2xl space-y-3"
                 :class="editingEvent.team_side === 'home' ? 'border-blue-500' : 'border-rose-500'">
                
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-black text-white uppercase tracking-wide">Edit Event / Audit Entry</span>
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
                    <!-- Team Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Team Assignment</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="editingEvent.team_side = 'home'"
                                    class="py-1.5 rounded-xl font-bold text-xs border transition flex items-center justify-center gap-1.5"
                                    :class="editingEvent.team_side === 'home' ? 'bg-blue-600 text-white border-blue-400' : 'bg-slate-950 text-slate-400 border-slate-800'">
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                <span>{{ $game->home_display_name }} (Home)</span>
                            </button>
                            <button type="button" @click="editingEvent.team_side = 'away'"
                                    class="py-1.5 rounded-xl font-bold text-xs border transition flex items-center justify-center gap-1.5"
                                    :class="editingEvent.team_side === 'away' ? 'bg-rose-600 text-white border-rose-400' : 'bg-slate-950 text-slate-400 border-slate-800'">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                <span>{{ $game->away_display_name }} (Away)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Jersey & Points Grid -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Player Jersey # (or blank)</label>
                            <input type="text" 
                                   x-model="editingEvent.jersey_number" 
                                   placeholder="e.g. 7"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Points</label>
                            <input type="number" 
                                   x-model.number="editingEvent.points" 
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Period / Set Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Set</label>
                        <div class="grid grid-cols-5 gap-1.5">
                            <template x-for="p in Math.min(5, Math.max(3, currentPeriod))" :key="p">
                                <button type="button" 
                                        @click="editingEvent.period = p"
                                        class="py-1.5 rounded-lg font-mono font-bold text-xs border transition"
                                        :class="editingEvent.period == p ? 'bg-emerald-600 text-white border-white' : 'bg-slate-950 text-slate-300 border-slate-800'">
                                    <span x-text="'Set ' + p"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Action Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Action Type</label>
                        <select x-model="editingEvent.action_code"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold text-xs focus:border-blue-500 focus:outline-none">
                            <optgroup label="Points (+1)">
                                <option value="K">Kill (+1)</option>
                                <option value="A">Service Ace (+1)</option>
                                <option value="B">Block Solo (+1)</option>
                                <option value="C">Block Assist (+1)</option>
                            </optgroup>
                            <optgroup label="Errors (Opp +1)">
                                <option value="E">Attack Error (Opp +1)</option>
                                <option value="S">Service Error (Opp +1)</option>
                                <option value="H">Ball Handling Error (Opp +1)</option>
                                <option value="R">Reception Error (Opp +1)</option>
                            </optgroup>
                            <optgroup label="Defense & Setting (0)">
                                <option value="D">Dig</option>
                                <option value="Z">Set Assist</option>
                                <option value="T">Attack Attempt</option>
                            </optgroup>
                            <optgroup label="Administrative & Audit">
                                <option value="TIMEOUT">Timeout Charged</option>
                                <option value="SCORE_ADJ">Manual Score Adjustment</option>
                                <option value="SUB">Player Substitution</option>
                                <option value="ROTATE">Rotation Advance</option>
                                <option value="PERIOD">Set Advance</option>
                                <option value="NOTE">Audit Note / Official Scorer Entry</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Custom Description / Audit Notes -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Description / Scorer Audit Notes</label>
                        <input type="text" 
                               x-model="editingEvent.description" 
                               placeholder="e.g. Net violation assessed after replay check"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:border-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                    <button @click="editingEvent = null" class="px-3 py-1.5 rounded-xl bg-slate-800 text-slate-300 font-semibold text-xs flex items-center gap-1.5">
                        <span>Cancel</span>
                        <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono text-slate-400">Esc</span>
                    </button>
                    <button @click="saveEditedEventFast()" class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-1.5">
                        <span>Save Changes & Recalculate</span>
                        <span class="px-1.5 py-0.2 rounded bg-emerald-950 border border-emerald-400 text-[8px] font-mono text-amber-300 font-bold">↵ Enter</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- 5. ADD MANUAL EVENT / AUDIT LOG ENTRY MODAL -->
    <template x-if="showAddEventModal">
        <div class="fixed inset-0 bg-black/90 backdrop-blur-md z-60 flex items-center justify-center p-3 select-none"
             @click.self="closeAddEventModal()">
            
            <div class="bg-slate-900 border-2 border-emerald-500 rounded-3xl max-w-lg w-full p-4 sm:p-5 shadow-2xl space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-black text-white uppercase tracking-wide">+ Log Manual Event / Audit Entry</span>
                    </div>
                    <button @click="closeAddEventModal()" class="p-1 rounded-xl bg-slate-800 text-slate-400 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-3 text-xs">
                    <!-- Team Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Team</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="newEvent.team_side = 'home'"
                                    class="py-1.5 rounded-xl font-bold text-xs border transition flex items-center justify-center gap-1.5"
                                    :class="newEvent.team_side === 'home' ? 'bg-blue-600 text-white border-blue-400' : 'bg-slate-950 text-slate-400 border-slate-800'">
                                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                                <span>{{ $game->home_display_name }} (Home)</span>
                            </button>
                            <button type="button" @click="newEvent.team_side = 'away'"
                                    class="py-1.5 rounded-xl font-bold text-xs border transition flex items-center justify-center gap-1.5"
                                    :class="newEvent.team_side === 'away' ? 'bg-rose-600 text-white border-rose-400' : 'bg-slate-950 text-slate-400 border-slate-800'">
                                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                                <span>{{ $game->away_display_name }} (Away)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Action Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Action Type</label>
                        <select x-model="newEvent.action_code"
                                @change="onNewEventActionChange()"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold text-xs focus:border-blue-500 focus:outline-none">
                            <optgroup label="Points (+1)">
                                <option value="K">Kill (+1)</option>
                                <option value="A">Service Ace (+1)</option>
                                <option value="B">Block Solo (+1)</option>
                                <option value="C">Block Assist (+1)</option>
                            </optgroup>
                            <optgroup label="Errors (Opp +1)">
                                <option value="E">Attack Error (Opp +1)</option>
                                <option value="S">Service Error (Opp +1)</option>
                                <option value="H">Ball Handling Error (Opp +1)</option>
                                <option value="R">Reception Error (Opp +1)</option>
                            </optgroup>
                            <optgroup label="Defense & Setting (0)">
                                <option value="D">Dig</option>
                                <option value="Z">Set Assist</option>
                                <option value="T">Attack Attempt</option>
                            </optgroup>
                            <optgroup label="Administrative & Audit">
                                <option value="TIMEOUT">Timeout Charged</option>
                                <option value="SCORE_ADJ">Manual Score Adjustment</option>
                                <option value="SUB">Player Substitution</option>
                                <option value="ROTATE">Rotation Advance</option>
                                <option value="PERIOD">Set Advance</option>
                                <option value="NOTE">Audit Note / Official Scorer Entry</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Jersey & Points Grid -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Player Jersey #</label>
                            <input type="text" 
                                   x-model="newEvent.jersey_number" 
                                   placeholder="e.g. 7 (optional)"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Points</label>
                            <input type="number" 
                                   x-model.number="newEvent.points" 
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Set Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Set</label>
                        <div class="grid grid-cols-5 gap-1.5">
                            <template x-for="p in Math.min(5, Math.max(3, currentPeriod))" :key="p">
                                <button type="button" 
                                        @click="newEvent.period = p"
                                        class="py-1.5 rounded-lg font-mono font-bold text-xs border transition"
                                        :class="newEvent.period == p ? 'bg-emerald-600 text-white border-white' : 'bg-slate-950 text-slate-300 border-slate-800'">
                                    <span x-text="'Set ' + p"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Description / Audit Notes -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Custom Description / Audit Notes (Optional)</label>
                        <input type="text" 
                                x-model="newEvent.description" 
                                placeholder="e.g. Official review confirmed touch at net"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:border-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                    <button @click="closeAddEventModal()" class="px-3 py-1.5 rounded-xl bg-slate-800 text-slate-300 font-semibold text-xs">
                        Cancel
                    </button>
                    <button @click="saveManualEventFast()" class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30">
                        + Add to Audit Log
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- ON-THE-FLY SPREADSHEET ROSTER MANAGEMENT MODAL -->
    @if ($showRosterModal)
        <div class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none animate-fade-in"
             @keydown.window.escape.prevent="$wire.closeRosterModal()"
             wire:keydown.escape="closeRosterModal"
             x-data="inGameRosterSpreadsheet({
                 sport: 'volleyball',
                 teamSide: '{{ $rosterModalTeam }}',
                 initialPlayers: {{ Js::from($rosterModalLineups->map(function($l) {
                     return [
                         'id' => $l->id,
                         'jersey_number' => (string)$l->jersey_number,
                         'name' => $l->player_name,
                         'is_on_court' => (bool)$l->is_on_court,
                     ];
                 })->values()) }}
             })"
             @keydown.window="handleWindowKeydown($event)">
            
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
                        <template x-if="jumpFeedback">
                            <span class="px-2 py-0.5 rounded-md bg-amber-500/20 border border-amber-500/40 text-amber-300 font-mono text-[10px] font-bold animate-pulse flex items-center gap-1" x-text="'Jump: ' + jumpFeedback"></span>
                        </template>

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
                        <span>Press <kbd class="px-1 py-0.5 rounded bg-slate-800 text-amber-300 font-mono text-[9px]">1-9</kbd> jump to row &bull; <kbd class="px-1 py-0.5 rounded bg-slate-800 text-amber-300 font-mono text-[9px]">0</kbd> empty slot &bull; Arrow keys navigate</span>
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
