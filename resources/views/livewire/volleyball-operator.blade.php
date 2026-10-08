<div class="h-full max-h-full flex flex-col justify-between overflow-hidden select-none" 
     x-data="ProKeeperEngine.createOperator('volleyball', {
         gameId: {{ $game->id }},
         homeTeamName: '{{ addslashes($game->home_display_name) }}',
         awayTeamName: '{{ addslashes($game->away_display_name) }}',
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

    <!-- 1. TOP SCOREBOARD BAR (Compact, Zero-Waste Height) -->
    <div class="bg-slate-900 border-b border-slate-800 p-2 sm:p-3 shrink-0 shadow-lg rounded-2xl mb-1">
        <div class="grid grid-cols-12 gap-2 items-center">

            <!-- HOME SCORE BLOCK -->
            <div class="col-span-5 flex items-center justify-between p-2 sm:p-2.5 rounded-xl border transition-all"
                 :class="server === 'home' ? 'bg-blue-950/60 border-blue-500/80 shadow-md shadow-blue-500/20' : 'bg-slate-950 border-slate-800'">
                <div class="space-y-0.5 truncate mr-2">
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <span class="text-[10px] font-black uppercase tracking-wider text-blue-400">HOME</span>
                        <template x-if="server === 'home'">
                            <span class="px-1.5 py-0.2 rounded bg-emerald-500 text-slate-950 text-[9px] font-black tracking-wider uppercase animate-pulse">SERVING</span>
                        </template>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-white truncate leading-tight">{{ $game->home_display_name }}</h2>
                    <div class="flex items-center space-x-2 text-[10px] text-slate-400 font-mono">
                        <span>ROT: <strong class="text-blue-300" x-text="'P' + homeRotation"></strong></span>
                        <span>TO: <strong class="text-white" x-text="homeTimeouts"></strong></span>
                    </div>
                </div>

                <!-- Score Counter & Direct Touch +/- -->
                <div class="flex items-center space-x-1.5 shrink-0">
                    <div class="flex flex-col space-y-1">
                        <button @click="adjustScoreFast('home', 1)" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-600 hover:bg-blue-500 touch-active text-white font-black text-sm flex items-center justify-center shadow transition" title="+1 Home">
                            +1
                        </button>
                        <button @click="adjustScoreFast('home', -1)" class="w-7 h-6 sm:w-8 sm:h-6 rounded-lg bg-slate-800 hover:bg-slate-700 touch-active text-slate-300 font-bold text-[10px] flex items-center justify-center transition" title="-1 Home">
                            -1
                        </button>
                    </div>
                    <div class="min-w-[45px] sm:min-w-[55px] text-right">
                        <span class="font-mono text-3xl sm:text-4xl font-black text-white tracking-tighter leading-none scoreboard-glow" x-text="homeScore"></span>
                    </div>
                </div>
            </div>

            <!-- CENTER SET SUMMARY & STATUS -->
            <div class="col-span-2 flex flex-col items-center justify-center text-center space-y-1">
                <span class="px-2.5 py-0.5 rounded-full bg-slate-800 text-blue-300 text-[10px] font-black uppercase tracking-widest border border-slate-700" x-text="periodName">
                    {{ $game->period_name }}
                </span>

                <!-- Set History Pills -->
                <div class="flex items-center flex-wrap justify-center gap-1 text-[9px] font-mono text-slate-400">
                    <template x-for="(s, idx) in homePeriodScores" :key="idx">
                        <span class="px-1.5 py-0.5 rounded"
                              :class="(idx + 1) === currentPeriod ? 'bg-blue-600 text-white font-bold' : 'bg-slate-950 text-slate-400 border border-slate-800'"
                              x-text="'S' + (idx + 1) + ': ' + s + '-' + (awayPeriodScores[idx] || 0)">
                        </span>
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
                 :class="server === 'away' ? 'bg-rose-950/60 border-rose-500/80 shadow-md shadow-rose-500/20' : 'bg-slate-950 border-slate-800'">
                <!-- Score Counter & Direct Touch +/- -->
                <div class="flex items-center space-x-1.5 shrink-0">
                    <div class="min-w-[45px] sm:min-w-[55px] text-left">
                        <span class="font-mono text-3xl sm:text-4xl font-black text-white tracking-tighter leading-none scoreboard-glow" x-text="awayScore"></span>
                    </div>
                    <div class="flex flex-col space-y-1">
                        <button @click="adjustScoreFast('away', 1)" class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-rose-600 hover:bg-rose-500 touch-active text-white font-black text-sm flex items-center justify-center shadow transition" title="+1 Away">
                            +1
                        </button>
                        <button @click="adjustScoreFast('away', -1)" class="w-7 h-6 sm:w-8 sm:h-6 rounded-lg bg-slate-800 hover:bg-slate-700 touch-active text-slate-300 font-bold text-[10px] flex items-center justify-center transition" title="-1 Away">
                            -1
                        </button>
                    </div>
                </div>

                <div class="space-y-0.5 truncate text-right ml-2">
                    <div class="flex items-center justify-end space-x-1.5">
                        <template x-if="server === 'away'">
                            <span class="px-1.5 py-0.2 rounded bg-emerald-500 text-slate-950 text-[9px] font-black tracking-wider uppercase animate-pulse">SERVING</span>
                        </template>
                        <span class="text-[10px] font-black uppercase tracking-wider text-rose-400">AWAY</span>
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-white truncate leading-tight">{{ $game->away_display_name }}</h2>
                    <div class="flex items-center justify-end space-x-2 text-[10px] text-slate-400 font-mono">
                        <span>TO: <strong class="text-white" x-text="awayTimeouts"></strong></span>
                        <span>ROT: <strong class="text-rose-300" x-text="'P' + awayRotation"></strong></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 2. CENTER TIER: LARGE ACTIVE PLAYERS GRID (50/50 Arena Split, Zero Scroll) -->
    <div class="flex-1 min-h-0 grid grid-cols-2 gap-2 my-1 overflow-hidden">
        
        <!-- HOME ACTIVE PLAYERS (6 LARGE BUTTONS) -->
        <div class="flex flex-col h-full bg-slate-900/90 border border-slate-800 rounded-2xl p-2 overflow-hidden shadow-inner">
            <div class="flex items-center justify-between pb-1 mb-1 border-b border-slate-800 text-[11px] font-bold text-blue-400">
                <span class="uppercase tracking-wider truncate">{{ $game->home_display_name }} (Court)</span>
                
                <div class="flex items-center space-x-1.5">
                    <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">Order 1-6</span>
                    <button type="button" @click="@this.openRosterModal('home')" class="px-2 py-0.5 rounded-lg bg-blue-950/90 hover:bg-blue-900 border border-blue-800 text-blue-300 hover:text-white text-[10px] font-bold transition flex items-center gap-1 shadow-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Edit Roster</span>
                    </button>
                </div>
            </div>

            <!-- 6 Large Touch Buttons Grid (2 cols x 3 rows) -->
            <div class="flex-1 min-h-0 grid grid-cols-2 grid-rows-3 gap-1.5">
                <template x-for="(player, idx) in homeCourt" :key="player.id || idx">
                    <button @click="openActionPad('home', player.jersey_number, player.player_name, player.id)"
                            type="button"
                            class="h-full w-full rounded-xl border-2 transition-all p-2 flex flex-col justify-between items-center text-center touch-active group"
                            :class="selectedPlayer && selectedPlayer.side === 'home' && selectedPlayer.jersey == player.jersey_number 
                                ? 'bg-blue-600 border-white shadow-lg shadow-blue-500/40 ring-2 ring-blue-300' 
                                : 'bg-slate-950/90 hover:bg-blue-950/50 border-slate-800/90 hover:border-blue-500/60'">
                        
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400 group-hover:text-blue-300">
                            <span class="font-bold" x-text="'ORD ' + (idx + 1)"></span>
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
        <div class="flex flex-col h-full bg-slate-900/90 border border-slate-800 rounded-2xl p-2 overflow-hidden shadow-inner">
            <div class="flex items-center justify-between pb-1 mb-1 border-b border-slate-800 text-[11px] font-bold text-rose-400">
                <span class="uppercase tracking-wider truncate">{{ $game->away_display_name }} (Court)</span>
                
                <div class="flex items-center space-x-1.5">
                    <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">Order 1-6</span>
                    <button type="button" @click="@this.openRosterModal('away')" class="px-2 py-0.5 rounded-lg bg-rose-950/90 hover:bg-rose-900 border border-rose-800 text-rose-300 hover:text-white text-[10px] font-bold transition flex items-center gap-1 shadow-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Edit Roster</span>
                    </button>
                </div>
            </div>

            <!-- 6 Large Touch Buttons Grid (2 cols x 3 rows) -->
            <div class="flex-1 min-h-0 grid grid-cols-2 grid-rows-3 gap-1.5">
                <template x-for="(player, idx) in awayCourt" :key="player.id || idx">
                    <button @click="openActionPad('away', player.jersey_number, player.player_name, player.id)"
                            type="button"
                            class="h-full w-full rounded-xl border-2 transition-all p-2 flex flex-col justify-between items-center text-center touch-active group"
                            :class="selectedPlayer && selectedPlayer.side === 'away' && selectedPlayer.jersey == player.jersey_number 
                                ? 'bg-rose-600 border-white shadow-lg shadow-rose-500/40 ring-2 ring-rose-300' 
                                : 'bg-slate-950/90 hover:bg-rose-950/50 border-slate-800/90 hover:border-rose-500/60'">
                        
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400 group-hover:text-rose-300">
                            <span class="font-bold" x-text="'ORD ' + (idx + 1)"></span>
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

    <!-- 3. BOTTOM TIER: NON-PLAYER-SCOPED ACTIONS DOCK (Fixed Height, Tactile Control) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2 shrink-0 shadow-xl mt-1">
        <div class="grid grid-cols-4 sm:grid-cols-9 gap-1.5 text-xs font-bold">
            
            <!-- HOME TIMEOUT -->
            <button @click="callTimeoutFast('home')" 
                    class="p-2 rounded-xl bg-slate-950 hover:bg-slate-800 touch-active border border-blue-800/60 text-blue-300 flex flex-col items-center justify-center">
                <span class="text-[10px] font-black uppercase">HOME TO</span>
                <span class="text-[9px] text-slate-400 font-mono" x-text="homeTimeouts + ' Left'"></span>
            </button>

            <!-- ROTATE HOME -->
            <button @click="rotateTeamFast('home')" 
                    class="p-2 rounded-xl bg-blue-900/40 hover:bg-blue-800/60 touch-active border border-blue-700/50 text-blue-200 flex flex-col items-center justify-center">
                <span class="text-[10px] font-black uppercase">ROTATE HOME</span>
                <span class="text-[9px] text-blue-300/80 font-mono" x-text="'Pos ' + ((homeRotation % 6) + 1) + ' Next'"></span>
            </button>

            <!-- TOGGLE SERVER -->
            <button @click="toggleServer()" 
                    class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-700 text-white flex flex-col items-center justify-center shadow">
                <span class="text-[10px] font-black uppercase">SERVER</span>
                <span class="text-[9px] text-emerald-400 font-mono" x-text="server.toUpperCase()"></span>
            </button>

            <!-- ADVANCE SET -->
            <button @click="nextPeriodFast()" 
                    class="p-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 touch-active text-white flex flex-col items-center justify-center shadow-lg shadow-emerald-600/30">
                <span class="text-[10px] font-black uppercase">+ NEXT SET</span>
                <span class="text-[9px] text-emerald-100 font-mono" x-text="'Set ' + (currentPeriod + 1)"></span>
            </button>

            <!-- SUBSTITUTION SHEET TRIGGER -->
            <button @click="openSubSheet('home')" 
                    class="p-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 touch-active text-white flex flex-col items-center justify-center shadow-lg shadow-indigo-600/30">
                <span class="text-[10px] font-black uppercase">SUBSTITUTE</span>
                <span class="text-[9px] text-indigo-200 font-mono">Quick Swap</span>
            </button>

            <!-- MANAGE ROSTERS ON THE FLY -->
            <button @click="@this.openRosterModal('home'); playSound('tap')" 
                    class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-600 text-cyan-300 flex flex-col items-center justify-center shadow">
                <span class="text-[10px] font-black uppercase">ROSTERS</span>
                <span class="text-[9px] text-slate-300 font-mono">Add / Edit</span>
            </button>

            <!-- ROTATE AWAY -->
            <button @click="rotateTeamFast('away')" 
                    class="p-2 rounded-xl bg-rose-900/40 hover:bg-rose-800/60 touch-active border border-rose-700/50 text-rose-200 flex flex-col items-center justify-center">
                <span class="text-[10px] font-black uppercase">ROTATE AWAY</span>
                <span class="text-[9px] text-rose-300/80 font-mono" x-text="'Pos ' + ((awayRotation % 6) + 1) + ' Next'"></span>
            </button>

            <!-- AWAY TIMEOUT -->
            <button @click="callTimeoutFast('away')" 
                    class="p-2 rounded-xl bg-slate-950 hover:bg-slate-800 touch-active border border-rose-800/60 text-rose-300 flex flex-col items-center justify-center">
                <span class="text-[10px] font-black uppercase">AWAY TO</span>
                <span class="text-[9px] text-slate-400 font-mono" x-text="awayTimeouts + ' Left'"></span>
            </button>

            <!-- UNDO LAST PLAY -->
            <button @click="undo()" 
                    class="p-2 rounded-xl bg-rose-950/80 hover:bg-rose-900 touch-active border border-rose-800 text-rose-300 flex flex-col items-center justify-center shadow">
                <span class="text-[10px] font-black uppercase">UNDO LAST</span>
                <span class="text-[9px] text-rose-400 font-mono">0ms Revert</span>
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
                    
                    <!-- KILL (+1 POINT) -->
                    <button @click="executeAction('K')" class="p-3.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 touch-active text-white border border-emerald-400/80 shadow-lg shadow-emerald-600/30 flex flex-col items-center justify-center">
                        <span class="text-sm font-black">+1 KILL</span>
                        <span class="text-[10px] font-mono opacity-90">Attack Point [K]</span>
                    </button>

                    <!-- ACE (+1 POINT) -->
                    <button @click="executeAction('A')" class="p-3.5 rounded-2xl bg-teal-600 hover:bg-teal-500 touch-active text-white border border-teal-400/80 shadow-lg shadow-teal-600/30 flex flex-col items-center justify-center">
                        <span class="text-sm font-black">+1 ACE</span>
                        <span class="text-[10px] font-mono opacity-90">Service Point [A]</span>
                    </button>

                    <!-- BLOCK SOLO (+1 POINT) -->
                    <button @click="executeAction('B')" class="p-3.5 rounded-2xl bg-cyan-600 hover:bg-cyan-500 touch-active text-white border border-cyan-400/80 shadow-lg shadow-cyan-600/30 flex flex-col items-center justify-center">
                        <span class="text-sm font-black">+1 BLK SOLO</span>
                        <span class="text-[10px] font-mono opacity-90">Solo Stuff [B]</span>
                    </button>

                    <!-- BLOCK ASSIST (+1 POINT) -->
                    <button @click="executeAction('C')" class="p-3.5 rounded-2xl bg-sky-600 hover:bg-sky-500 touch-active text-white border border-sky-400/80 shadow-lg shadow-sky-600/30 flex flex-col items-center justify-center">
                        <span class="text-sm font-black">+1 BLK AST</span>
                        <span class="text-[10px] font-mono opacity-90">Shared Stuff [C]</span>
                    </button>

                    <!-- DIG -->
                    <button @click="executeAction('D')" class="p-3 rounded-2xl bg-blue-600 hover:bg-blue-500 touch-active text-white border border-blue-400/80 shadow flex flex-col items-center justify-center">
                        <span class="text-sm font-black">DIG</span>
                        <span class="text-[10px] font-mono opacity-90">Defensive [D]</span>
                    </button>

                    <!-- SET ASSIST -->
                    <button @click="executeAction('Z')" class="p-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 touch-active text-white border border-indigo-400/80 shadow flex flex-col items-center justify-center">
                        <span class="text-sm font-black">ASSIST</span>
                        <span class="text-[10px] font-mono opacity-90">Set Ast [Z]</span>
                    </button>

                    <!-- ATTACK ATTEMPT -->
                    <button @click="executeAction('T')" class="p-3 rounded-2xl bg-slate-700 hover:bg-slate-600 touch-active text-white border border-slate-500 shadow flex flex-col items-center justify-center">
                        <span class="text-sm font-black">ATTACK (0pt)</span>
                        <span class="text-[10px] font-mono opacity-90">Attempt [T]</span>
                    </button>

                    <!-- ATTACK ERROR (OPP POINT) -->
                    <button @click="executeAction('E')" class="p-3 rounded-2xl bg-rose-700 hover:bg-rose-600 touch-active text-white border border-rose-500 shadow flex flex-col items-center justify-center">
                        <span class="text-sm font-black">ATTACK ERR</span>
                        <span class="text-[10px] font-mono opacity-90">Out/Net [E]</span>
                    </button>

                    <!-- SERVICE ERROR (OPP POINT) -->
                    <button @click="executeAction('S')" class="p-3 rounded-2xl bg-rose-800 hover:bg-rose-700 touch-active text-white border border-rose-600 shadow flex flex-col items-center justify-center">
                        <span class="text-sm font-black">SERVE ERR</span>
                        <span class="text-[10px] font-mono opacity-90">Fault/Net [S]</span>
                    </button>

                    <!-- BALL HANDLING ERROR -->
                    <button @click="executeAction('H')" class="p-3 rounded-2xl bg-red-800 hover:bg-red-700 touch-active text-white border border-red-600 shadow flex flex-col items-center justify-center">
                        <span class="text-sm font-black">BHE (HAND)</span>
                        <span class="text-[10px] font-mono opacity-90">Double/Lift [H]</span>
                    </button>

                    <!-- RECEPTION ERROR -->
                    <button @click="executeAction('R')" class="p-3 rounded-2xl bg-red-900 hover:bg-red-800 touch-active text-white border border-red-700 shadow flex flex-col items-center justify-center">
                        <span class="text-sm font-black">RECEPTION ERR</span>
                        <span class="text-[10px] font-mono opacity-90">Pass Shank [R]</span>
                    </button>

                    <!-- QUICK SUB BUTTON -->
                    <button @click="openSubSheet(selectedPlayer.side, selectedPlayer.jersey)" class="p-3 rounded-2xl bg-cyan-700 hover:bg-cyan-600 touch-active text-white border border-cyan-500 shadow flex flex-col items-center justify-center">
                        <span class="text-sm font-black">SUB PLAYER</span>
                        <span class="text-[10px] font-mono opacity-90">Swap Court &rarr;</span>
                    </button>

                </div>
            </div>
        </div>
    </template>

    <!-- 2-TAP QUICK SUBSTITUTION BOTTOM SHEET -->
    <div x-show="showSubSheet" class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none" style="display: none;"
         @click.self="showSubSheet = false">
        
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-lg w-full p-4 sm:p-5 shadow-2xl space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-black uppercase text-white tracking-wider">Quick 2-Tap Substitution</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                          :class="subTeamSide === 'home' ? 'bg-blue-900 text-blue-300' : 'bg-rose-900 text-rose-300'"
                          x-text="subTeamSide.toUpperCase()">
                    </span>
                </div>
                <button @click="showSubSheet = false" class="p-1 rounded bg-slate-800 text-slate-400 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Team Switcher -->
            <div class="grid grid-cols-2 gap-2">
                <button @click="subTeamSide = 'home'; subOutJersey = ''; subInJersey = '';" 
                        class="p-2 rounded-xl text-xs font-black uppercase tracking-wider transition border"
                        :class="subTeamSide === 'home' ? 'bg-blue-600 text-white border-blue-400' : 'bg-slate-950 text-slate-400 border-slate-800'">
                    {{ $game->home_display_name }} (Home)
                </button>
                <button @click="subTeamSide = 'away'; subOutJersey = ''; subInJersey = '';" 
                        class="p-2 rounded-xl text-xs font-black uppercase tracking-wider transition border"
                        :class="subTeamSide === 'away' ? 'bg-rose-600 text-white border-rose-400' : 'bg-slate-950 text-slate-400 border-slate-800'">
                    {{ $game->away_display_name }} (Away)
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs">
                <!-- STEP 1: TAP PLAYER LEAVING (COURT) -->
                <div class="space-y-1.5">
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">1. Tap Player OUT</div>
                    <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                        <template x-for="p in (subTeamSide === 'home' ? homeCourt : awayCourt)" :key="p.id || p.jersey_number">
                            <button @click="subOutJersey = p.jersey_number" 
                                    class="w-full p-2 rounded-xl border text-left flex items-center justify-between transition touch-active"
                                    :class="subOutJersey == p.jersey_number ? 'bg-rose-600 text-white border-white shadow' : 'bg-slate-950 text-slate-200 border-slate-800 hover:border-slate-700'">
                                <span class="font-mono font-black" x-text="'#' + p.jersey_number"></span>
                                <span class="truncate text-[11px] font-semibold" x-text="p.player_name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- STEP 2: TAP BENCH PLAYER ENTERING -->
                <div class="space-y-1.5">
                    <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">2. Tap Player IN</div>
                    <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                        <template x-for="p in (subTeamSide === 'home' ? homeBench : awayBench)" :key="p.id || p.jersey_number">
                            <button @click="subInJersey = p.jersey_number; if(subOutJersey) confirmSubFast();" 
                                    class="w-full p-2 rounded-xl border text-left flex items-center justify-between transition touch-active"
                                    :class="subInJersey == p.jersey_number ? 'bg-emerald-600 text-white border-white shadow' : 'bg-slate-950 text-slate-200 border-slate-800 hover:border-slate-700'">
                                <span class="font-mono font-black" x-text="'#' + p.jersey_number"></span>
                                <span class="truncate text-[11px] font-semibold" x-text="p.player_name"></span>
                            </button>
                        </template>
                        <div x-show="(subTeamSide === 'home' ? homeBench : awayBench).length === 0" class="text-[10px] text-slate-500 text-center py-4">
                            No bench players registered.
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs">
                <span class="text-[10px] text-slate-400" x-text="subOutJersey && subInJersey ? ('Sub OUT #' + subOutJersey + ' -> IN #' + subInJersey) : 'Select 1 player OUT and 1 player IN'"></span>
                <div class="space-x-1.5">
                    <button @click="showSubSheet = false" class="px-3 py-1.5 rounded-xl bg-slate-800 text-slate-300 font-semibold text-xs">Cancel</button>
                    <button @click="confirmSubFast()" :disabled="!subOutJersey || !subInJersey" class="px-4 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:opacity-40 text-white font-bold text-xs shadow">Confirm Sub</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ON-THE-FLY ROSTER MANAGEMENT MODAL -->
    @if ($showRosterModal)
        <div class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none animate-fade-in"
             wire:keydown.escape="closeRosterModal">
            
            <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-4 sm:p-6 shadow-2xl space-y-4 max-h-[92vh] flex flex-col">
                
                <!-- Modal Header: Team Switcher & Title -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-800 shrink-0">
                    <div class="flex items-center space-x-2">
                        <span class="p-2 rounded-xl bg-cyan-600/20 text-cyan-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                        <div>
                            <h3 class="text-base font-black text-white">Live Volleyball Lineup & Roster</h3>
                            <p class="text-xs text-slate-400">Manage 6-position court order, rotation starters, or libero.</p>
                        </div>
                    </div>

                    <button wire:click="closeRosterModal" class="p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Team Switcher Pill Buttons -->
                <div class="grid grid-cols-2 gap-2 shrink-0">
                    <button wire:click="setRosterModalTeam('home')"
                            type="button"
                            class="py-2 px-3 rounded-xl text-xs font-black uppercase tracking-wider transition border flex items-center justify-center gap-2 {{ $rosterModalTeam === 'home' ? 'bg-blue-600 text-white border-blue-400 shadow-lg shadow-blue-600/30' : 'bg-slate-950 text-slate-400 border-slate-800 hover:border-slate-700' }}">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span>
                        <span class="truncate">{{ $game->home_display_name }} (Home)</span>
                    </button>
                    <button wire:click="setRosterModalTeam('away')"
                            type="button"
                            class="py-2 px-3 rounded-xl text-xs font-black uppercase tracking-wider transition border flex items-center justify-center gap-2 {{ $rosterModalTeam === 'away' ? 'bg-rose-600 text-white border-rose-400 shadow-lg shadow-rose-600/30' : 'bg-slate-950 text-slate-400 border-slate-800 hover:border-slate-700' }}">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                        <span class="truncate">{{ $game->away_display_name }} (Away)</span>
                    </button>
                </div>

                <!-- Subtabs: Current Roster, Quick Add, Bulk Paste -->
                <div class="flex items-center space-x-2 border-b border-slate-800 pb-2 text-xs font-bold shrink-0">
                    <button wire:click="setRosterModalTab('list')" 
                            class="px-3 py-1.5 rounded-xl transition {{ $rosterModalTab === 'list' ? 'bg-slate-800 text-cyan-400 border border-slate-700' : 'text-slate-400 hover:text-white' }}">
                        📋 Roster & Rotation ({{ $rosterModalLineups->count() }})
                    </button>
                    <button wire:click="setRosterModalTab('create')" 
                            class="px-3 py-1.5 rounded-xl transition {{ $rosterModalTab === 'create' ? 'bg-slate-800 text-cyan-400 border border-slate-700' : 'text-slate-400 hover:text-white' }}">
                        ➕ Quick Add Player
                    </button>
                    <button wire:click="setRosterModalTab('paste')" 
                            class="px-3 py-1.5 rounded-xl transition {{ $rosterModalTab === 'paste' ? 'bg-slate-800 text-cyan-400 border border-slate-700' : 'text-slate-400 hover:text-white' }}">
                        📥 Paste from Sheets
                    </button>
                </div>

                <!-- Tab Content Area (Scrollable) -->
                <div class="flex-1 min-h-0 overflow-y-auto pr-1 space-y-3">
                    
                    <!-- TAB 1: ROSTER & LINEUP LIST -->
                    @if ($rosterModalTab === 'list')
                        @if ($rosterModalLineups->isEmpty())
                            <div class="p-8 text-center bg-slate-950/60 rounded-2xl border border-slate-800 space-y-3">
                                <p class="text-xs text-slate-400">No players registered for {{ strtoupper($rosterModalTeam) }} yet.</p>
                                <button wire:click="setRosterModalTab('create')" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold shadow transition">
                                    + Add First Player
                                </button>
                            </div>
                        @else
                            <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/60 shadow-inner">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead class="bg-slate-950 text-slate-400 font-mono uppercase text-[10px] border-b border-slate-800">
                                        <tr>
                                            <th class="w-14 px-3 py-2.5">Jersey</th>
                                            <th class="px-3 py-2.5">Player Name</th>
                                            <th class="w-20 px-3 py-2.5">Pos</th>
                                            <th class="w-24 px-3 py-2.5 text-center">Court Status</th>
                                            <th class="w-28 px-3 py-2.5 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60 text-slate-200">
                                        @foreach ($rosterModalLineups as $player)
                                            @if ($editingLineupId === $player->id)
                                                <!-- Inline Editing Row -->
                                                <tr class="bg-slate-800/60">
                                                    <td class="px-2 py-1.5">
                                                        <input type="text" wire:model="editJersey" class="w-full px-2 py-1 rounded bg-slate-950 border border-slate-700 text-white font-mono text-center text-xs focus:border-cyan-500 focus:outline-none" maxlength="5">
                                                    </td>
                                                    <td class="px-2 py-1.5">
                                                        <input type="text" wire:model="editName" class="w-full px-2 py-1 rounded bg-slate-950 border border-slate-700 text-white font-bold text-xs focus:border-cyan-500 focus:outline-none">
                                                    </td>
                                                    <td class="px-2 py-1.5">
                                                        <input type="text" wire:model="editPosition" placeholder="OH, S, MB" class="w-full px-2 py-1 rounded bg-slate-950 border border-slate-700 text-white uppercase text-center font-mono text-xs focus:border-cyan-500 focus:outline-none" maxlength="5">
                                                    </td>
                                                    <td class="px-2 py-1.5 text-center">
                                                        <label class="inline-flex items-center text-[10px] text-slate-300">
                                                            <input type="checkbox" wire:model="editIsOnCourt" class="rounded bg-slate-950 border-slate-700 text-emerald-500 mr-1">
                                                            On Court
                                                        </label>
                                                    </td>
                                                    <td class="px-2 py-1.5 text-center space-x-1">
                                                        <button wire:click="saveEditedLineup" class="px-2 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-bold">Save</button>
                                                        <button wire:click="cancelEditingLineup" class="px-2 py-1 rounded bg-slate-800 text-slate-300 text-[10px]">Cancel</button>
                                                    </td>
                                                </tr>
                                            @else
                                                <!-- Normal Display Row -->
                                                <tr class="hover:bg-slate-800/30 transition group">
                                                    <td class="px-3 py-2.5 font-mono font-black text-white text-sm">
                                                        #{{ $player->jersey_number }}
                                                    </td>
                                                    <td class="px-3 py-2.5">
                                                        <div class="font-bold text-slate-100">{{ $player->player_name }}</div>
                                                        @if ($player->is_starter)
                                                            <span class="text-[9px] text-blue-400 font-mono">Starter</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-2.5 font-mono text-slate-400">
                                                        {{ $player->position ?: '—' }}
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center">
                                                        <button wire:click="toggleLineupCourtStatus({{ $player->id }})"
                                                                type="button"
                                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase transition border {{ $player->is_on_court ? 'bg-emerald-950 border-emerald-700 text-emerald-300 hover:bg-emerald-900' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white' }}">
                                                            {{ $player->is_on_court ? 'On Court' : 'Bench' }}
                                                        </button>
                                                    </td>
                                                    <td class="px-3 py-2.5 text-center space-x-1">
                                                        <button wire:click="startEditingLineup({{ $player->id }})" class="p-1 rounded text-slate-400 hover:text-cyan-300 transition" title="Edit player">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                        </button>
                                                        <button wire:click="deleteLineupPlayer({{ $player->id }})" wire:confirm="Remove this player from the game?" class="p-1 rounded text-slate-400 hover:text-rose-400 transition" title="Remove player">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif

                    <!-- TAB 2: QUICK ADD PLAYER -->
                    @if ($rosterModalTab === 'create')
                        <form wire:submit.prevent="quickAddLineupPlayer" class="p-4 bg-slate-950/80 rounded-2xl border border-slate-800 space-y-3">
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-300 mb-1">Jersey # *</label>
                                    <input type="text" wire:model="newJersey" placeholder="e.g. 7" maxlength="5" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono font-bold text-sm focus:border-cyan-500 focus:outline-none">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-[11px] font-bold text-slate-300 mb-1">Full Name *</label>
                                    <input type="text" wire:model="newName" placeholder="e.g. Karch Kiraly" required class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-cyan-500 focus:outline-none">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-300 mb-1">Position (Optional)</label>
                                    <input type="text" wire:model="newPosition" placeholder="OH, MB, S, RS, L" maxlength="10" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white uppercase font-mono text-sm focus:border-cyan-500 focus:outline-none">
                                </div>
                                <div class="flex flex-col justify-center space-y-1 pt-3">
                                    <label class="flex items-center text-xs text-slate-300 cursor-pointer">
                                        <input type="checkbox" wire:model="newIsOnCourt" class="rounded bg-slate-900 border-slate-700 text-emerald-500 mr-2">
                                        Place on court (Rotation order)
                                    </label>
                                    <label class="flex items-center text-xs text-slate-300 cursor-pointer">
                                        <input type="checkbox" wire:model="newIsStarter" class="rounded bg-slate-900 border-slate-700 text-blue-500 mr-2">
                                        Mark as Starter
                                    </label>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between">
                                <label class="flex items-center text-[11px] text-slate-400 cursor-pointer">
                                    <input type="checkbox" wire:model="syncWithTeamRoster" class="rounded bg-slate-900 border-slate-700 text-indigo-500 mr-1.5">
                                    Save to team database for future games
                                </label>

                                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold text-white text-xs shadow-lg shadow-cyan-600/30 transition">
                                    + Add Player to Game
                                </button>
                            </div>
                        </form>
                    @endif

                    <!-- TAB 3: BULK PASTE FROM SHEETS -->
                    @if ($rosterModalTab === 'paste')
                        <div class="p-4 bg-slate-950/80 rounded-2xl border border-slate-800 space-y-3">
                            <div class="text-[11px] text-slate-400">
                                Paste list of players from Excel or Google Sheets (e.g. <span class="font-mono text-emerald-400">07 Karch Kiraly OH</span> on each line):
                            </div>

                            <textarea wire:model="bulkRosterInput" rows="6" placeholder="07 Karch Kiraly OH Starter&#10;11 Steve Timmons MB Starter&#10;03 Dusty Dvorak S Starter" class="w-full p-3 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-xs focus:border-cyan-500 focus:outline-none"></textarea>

                            <div class="flex items-center justify-between pt-1">
                                <div class="flex items-center space-x-3 text-xs text-slate-300">
                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" wire:model="bulkRosterMode" value="append" class="text-cyan-600 bg-slate-900 border-slate-700">
                                        <span>Append</span>
                                    </label>
                                    <label class="flex items-center space-x-1 cursor-pointer">
                                        <input type="radio" wire:model="bulkRosterMode" value="replace" class="text-cyan-600 bg-slate-900 border-slate-700">
                                        <span>Replace</span>
                                    </label>
                                </div>

                                <button type="button" wire:click="importBulkRosterToGame" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-white text-xs shadow-lg shadow-indigo-600/30 transition">
                                    Import Roster Now
                                </button>
                            </div>
                        </div>
                    @endif

                </div>

                <!-- Footer Summary Bar -->
                <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs shrink-0">
                    <span class="text-slate-400 font-mono text-[11px]">
                        {{ $rosterModalLineups->where('is_on_court', true)->count() }} On Court &bull; {{ $rosterModalLineups->where('is_on_court', false)->count() }} Bench
                    </span>

                    <button wire:click="closeRosterModal" class="px-4 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow">
                        Done
                    </button>
                </div>

            </div>
        </div>
    @endif

</div>
