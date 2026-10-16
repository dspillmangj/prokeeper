<div class="w-full h-full max-h-full flex-1 min-h-0 flex flex-col justify-between overflow-hidden select-none" 
     wire:ignore.self
     x-data="ProKeeperEngine.createOperator('basketball', {
         gameId: {{ $game->id }},
         homeTeamName: '{{ addslashes($game->home_display_name) }}',
         awayTeamName: '{{ addslashes($game->away_display_name) }}',
         homeTeamColor: '{{ $game->home_team_score_color ?? '#1e40af' }}',
         awayTeamColor: '{{ $game->away_team_score_color ?? '#b91c1c' }}',
         broadcastFlip: @js((bool)($game->settings['broadcast_flip'] ?? false)),
         homeScore: {{ (int)$game->home_score }},
         awayScore: {{ (int)$game->away_score }},
         homePeriodScores: @js($game->home_period_scores ?? [0, 0, 0, 0, 0, 0]),
         awayPeriodScores: @js($game->away_period_scores ?? [0, 0, 0, 0, 0, 0]),
         currentPeriod: {{ (int)$game->current_period }},
         periodName: '{{ $game->period_name }}',
         possession: '{{ $game->possession_arrow ?? 'home' }}',
         homeFouls: {{ (int)$game->home_fouls_current_period }},
         awayFouls: {{ (int)$game->away_fouls_current_period }},
         homeFullTimeouts: {{ (int)($game->home_timeouts_breakdown['rem_full'] ?? 3) }},
         home30sTimeouts: {{ (int)($game->home_timeouts_breakdown['rem_30s'] ?? 2) }},
         awayFullTimeouts: {{ (int)($game->away_timeouts_breakdown['rem_full'] ?? 3) }},
         away30sTimeouts: {{ (int)($game->away_timeouts_breakdown['rem_30s'] ?? 2) }},
         homeTimeouts: {{ (int)$game->home_timeouts_remaining }},
         awayTimeouts: {{ (int)$game->away_timeouts_remaining }},
         periodFormat: '{{ $game->period_format }}',
         timeoutsOt: {{ (int)$game->ot_timeouts_allowed }},
         timeoutsFull: {{ (int)$game->full_timeouts_allowed }},
         timeouts30s: {{ (int)$game->thirty_second_timeouts_allowed }},
         homeCourt: @js($homeLineupOnCourt),
         awayCourt: @js($awayLineupOnCourt),
         homeBench: @js($homeBench),
         awayBench: @js($awayBench),
         homeStats: @js($homeStats),
         awayStats: @js($awayStats),
         bonusFoulThreshold: {{ (int) $game->bonus_threshold }},
         doubleBonusFoulThreshold: {{ (int) $game->double_bonus_threshold }},
         playerFoulLimit: {{ (int) $game->player_foul_limit }},
         recentEvents: @js($recentEvents),
     })">

    <!-- 1. TOP SCOREBOARD BAR (Compact, Zero-Waste Height, Dynamically Colored & Flippable) -->
    <div class="bg-slate-900 border-b border-slate-800 p-2 sm:p-3 shrink-0 shadow-lg rounded-2xl mb-1">
        <div class="flex flex-col sm:flex-row gap-2 items-stretch">

            <!-- HOME SCORE BLOCK -->
            <div class="flex-1 min-w-0 flex items-center justify-between p-2 sm:p-2.5 rounded-xl border transition-all"
                 :class="[
                     isFlipped ? 'order-3' : 'order-1',
                     homeFouls >= {{ $game->double_bonus_threshold }}
                         ? 'ring-2 ring-rose-500 border-rose-500 shadow-[0_0_25px_rgba(244,63,94,0.45)] animate-pulse'
                         : (homeFouls >= {{ $game->bonus_threshold }}
                             ? 'ring-2 ring-amber-400 border-amber-400 shadow-[0_0_20px_rgba(251,191,36,0.35)]'
                             : (possession === 'home' ? 'shadow-lg ring-1 ring-white/30' : 'bg-slate-950 border-slate-800'))
                 ]"
                 :style="possession === 'home' ? { backgroundColor: homeTeamColor + '26', borderColor: homeTeamColor } : { borderColor: homeTeamColor + '40' }">
                <div class="space-y-1 truncate mr-2">
                    <div class="flex items-center space-x-1.5">
                        <span class="w-2.5 h-2.5 rounded-full shadow-sm" :style="{ backgroundColor: homeTeamColor }"></span>
                        <span class="text-[10px] font-black uppercase tracking-wider" :style="{ color: homeTeamColor }">HOME</span>
                        <template x-if="possession === 'home'">
                            <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 text-[9px] font-black tracking-wider uppercase">POSS</span>
                        </template>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-white truncate leading-tight">{{ $game->home_display_name }}</h2>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <!-- Prominent Team Fouls Display -->
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border transition-all font-mono"
                             :class="homeFouls >= {{ $game->double_bonus_threshold }}
                                ? 'bg-rose-950/90 border-rose-500 text-rose-200 ring-1 ring-rose-500 shadow-md shadow-rose-900/40 animate-pulse'
                                : (homeFouls >= {{ $game->bonus_threshold }}
                                    ? 'bg-amber-950/90 border-amber-500 text-amber-200 ring-1 ring-amber-500 shadow-md shadow-amber-900/40'
                                    : 'bg-slate-900 border-slate-700/80 text-slate-300')">
                            <span class="text-[9px] uppercase tracking-wider font-extrabold text-slate-400">FOULS</span>
                            <span class="text-sm sm:text-base font-black text-white leading-none" x-text="homeFouls"></span>
                        </div>
                        <template x-if="homeFouls >= {{ $game->double_bonus_threshold }}">
                            <span class="px-1.5 py-0.5 rounded-lg bg-rose-600 text-white font-black text-[9px] tracking-wider uppercase border border-rose-300 shadow-lg shadow-rose-600/50 flex items-center gap-1 animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                DOUBLE BONUS
                            </span>
                        </template>
                        <template x-if="homeFouls >= {{ $game->bonus_threshold }} && homeFouls < {{ $game->double_bonus_threshold }}">
                            <span class="px-1.5 py-0.5 rounded-lg bg-amber-500 text-slate-950 font-black text-[9px] tracking-wider uppercase border border-amber-300 shadow-md shadow-amber-500/40 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-950"></span>
                                BONUS
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Score Counter & Direct Touch +/- + Timeouts & Roster -->
                <div class="flex items-center space-x-1.5 shrink-0">
                    <!-- Home Timeouts & Roster -->
                    <div class="flex items-center gap-1 shrink-0">
                        <!-- Full TO -->
                        <button type="button" 
                                @click="requestTimeout('home', 'full')" 
                                class="h-7 sm:h-8 px-1.5 sm:px-2 rounded-lg bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 hover:border-slate-500 touch-active text-white flex items-center gap-1 transition shadow-sm"
                                :title="'Home Full Timeout (Hotkey: ' + (isFlipped ? 'G' : 'F') + ')'">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-tight">Full</span>
                            <span class="px-1 py-0.2 rounded text-[8px] font-mono font-bold text-white" :style="{ backgroundColor: homeTeamColor + '40', border: '1px solid ' + homeTeamColor }" x-text="homeFullTimeouts"></span>
                            <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono font-bold text-amber-300" x-text="isFlipped ? 'G' : 'F'"></span>
                        </button>
                        <!-- 30s TO -->
                        <button type="button" 
                                @click="requestTimeout('home', '30s')" 
                                class="h-7 sm:h-8 px-1.5 sm:px-2 rounded-lg bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 hover:border-slate-500 touch-active text-amber-300 flex items-center gap-1 transition shadow-sm"
                                :title="'Home 30s Timeout (Hotkey: ' + (isFlipped ? 'J' : 'H') + ')'">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-tight text-white">30s</span>
                            <span class="px-1 py-0.2 rounded bg-amber-950/80 border border-amber-800/80 text-[8px] font-mono font-bold text-amber-300" x-text="home30sTimeouts"></span>
                            <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono font-bold text-amber-300" x-text="isFlipped ? 'J' : 'H'"></span>
                        </button>
                        <!-- Roster -->
                        <button type="button" 
                                @click="@this.openRosterModal('home'); playSound('tap')" 
                                class="h-7 sm:h-8 px-1.5 sm:px-2 rounded-lg bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 hover:border-slate-500 touch-active text-cyan-300 flex items-center gap-1 transition shadow-sm"
                                :title="'Home Roster (Hotkey: ' + (isFlipped ? 'T' : 'R') + ')'">
                            <svg class="w-3.5 h-3.5 text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono font-bold text-amber-300" x-text="isFlipped ? 'T' : 'R'"></span>
                        </button>
                    </div>

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

            <!-- CENTER PERIOD SUMMARY & 6-PERIOD STATUS STRIP -->
            <div class="order-2 shrink-0 flex flex-col items-center justify-center text-center space-y-1.5 px-1 w-full sm:w-auto sm:min-w-[320px] md:min-w-[370px]">
                
                <!-- Period Meta Bar: Period Name, Plays Log, and Sync Status -->
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

                <!-- Complete 6-Period Score Strip (Q1, Q2, Q3, Q4, OT1, OT2 in 1 Row) -->
                <div class="grid grid-cols-6 gap-1 w-full font-mono">
                    <template x-for="(s, idx) in [0, 1, 2, 3, 4, 5]" :key="idx">
                        <button type="button"
                                @click="setPeriodFast(idx + 1)"
                                class="py-1 px-0.5 rounded-lg transition touch-active font-mono cursor-pointer flex flex-col items-center justify-center border text-center select-none"
                                :class="(idx + 1) === currentPeriod 
                                    ? 'bg-blue-600 text-white font-black border-blue-400 shadow-md shadow-blue-600/30 ring-1 ring-white/60' 
                                    : (((homePeriodScores[idx] || 0) > 0 || (awayPeriodScores[idx] || 0) > 0)
                                        ? 'bg-slate-950 text-slate-200 hover:text-white hover:bg-slate-800 border-slate-700'
                                        : 'bg-slate-950/70 text-slate-400 hover:text-slate-200 hover:bg-slate-900 border-slate-800/80')"
                                :title="'Switch to ' + ((idx + 1) <= 4 ? 'Quarter ' + (idx + 1) : 'Overtime ' + (idx + 1 - 4))">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-tight leading-tight"
                                  :class="(idx + 1) === currentPeriod ? 'text-amber-300' : 'text-slate-400'"
                                  x-text="(idx + 1) <= 4 ? 'Q' + (idx + 1) : 'OT' + (idx + 1 - 4)">
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
                     awayFouls >= {{ $game->double_bonus_threshold }}
                         ? 'ring-2 ring-rose-500 border-rose-500 shadow-[0_0_25px_rgba(244,63,94,0.45)] animate-pulse'
                         : (awayFouls >= {{ $game->bonus_threshold }}
                             ? 'ring-2 ring-amber-400 border-amber-400 shadow-[0_0_20px_rgba(251,191,36,0.35)]'
                             : (possession === 'away' ? 'shadow-lg ring-1 ring-white/30' : 'bg-slate-950 border-slate-800'))
                 ]"
                 :style="possession === 'away' ? { backgroundColor: awayTeamColor + '26', borderColor: awayTeamColor } : { borderColor: awayTeamColor + '40' }">
                <!-- Score Counter & Direct Touch +/- + Away Timeouts & Roster -->
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

                    <!-- Away Timeouts & Roster -->
                    <div class="flex items-center gap-1 shrink-0">
                        <!-- Full TO -->
                        <button type="button" 
                                @click="requestTimeout('away', 'full')" 
                                class="h-7 sm:h-8 px-1.5 sm:px-2 rounded-lg bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 hover:border-slate-500 touch-active text-white flex items-center gap-1 transition shadow-sm"
                                :title="'Away Full Timeout (Hotkey: ' + (isFlipped ? 'F' : 'G') + ')'">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-tight">Full</span>
                            <span class="px-1 py-0.2 rounded text-[8px] font-mono font-bold text-white" :style="{ backgroundColor: awayTeamColor + '40', border: '1px solid ' + awayTeamColor }" x-text="awayFullTimeouts"></span>
                            <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono font-bold text-amber-300" x-text="isFlipped ? 'F' : 'G'"></span>
                        </button>
                        <!-- 30s TO -->
                        <button type="button" 
                                @click="requestTimeout('away', '30s')" 
                                class="h-7 sm:h-8 px-1.5 sm:px-2 rounded-lg bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 hover:border-slate-500 touch-active text-amber-300 flex items-center gap-1 transition shadow-sm"
                                :title="'Away 30s Timeout (Hotkey: ' + (isFlipped ? 'H' : 'J') + ')'">
                            <span class="text-[9px] sm:text-[10px] font-black uppercase tracking-tight text-white">30s</span>
                            <span class="px-1 py-0.2 rounded bg-amber-950/80 border border-amber-800/80 text-[8px] font-mono font-bold text-amber-300" x-text="away30sTimeouts"></span>
                            <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono font-bold text-amber-300" x-text="isFlipped ? 'H' : 'J'"></span>
                        </button>
                        <!-- Roster -->
                        <button type="button" 
                                @click="@this.openRosterModal('away'); playSound('tap')" 
                                class="h-7 sm:h-8 px-1.5 sm:px-2 rounded-lg bg-slate-900/90 hover:bg-slate-800 border border-slate-700/80 hover:border-slate-500 touch-active text-cyan-300 flex items-center gap-1 transition shadow-sm"
                                :title="'Away Roster (Hotkey: ' + (isFlipped ? 'R' : 'T') + ')'">
                            <svg class="w-3.5 h-3.5 text-cyan-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[8px] font-mono font-bold text-amber-300" x-text="isFlipped ? 'R' : 'T'"></span>
                        </button>
                    </div>
                </div>

                <div class="space-y-1 truncate text-right ml-2">
                    <div class="flex items-center justify-end space-x-1.5">
                        <template x-if="possession === 'away'">
                            <span class="px-1.5 py-0.2 rounded bg-amber-500 text-slate-950 text-[9px] font-black tracking-wider uppercase">POSS</span>
                        </template>
                        <span class="text-[10px] font-black uppercase tracking-wider" :style="{ color: awayTeamColor }">AWAY</span>
                        <span class="w-2.5 h-2.5 rounded-full shadow-sm" :style="{ backgroundColor: awayTeamColor }"></span>
                    </div>
                    <h2 class="text-sm sm:text-base font-black text-white truncate leading-tight">{{ $game->away_display_name }}</h2>
                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                        <template x-if="awayFouls >= {{ $game->double_bonus_threshold }}">
                            <span class="px-1.5 py-0.5 rounded-lg bg-rose-600 text-white font-black text-[9px] tracking-wider uppercase border border-rose-300 shadow-lg shadow-rose-600/50 flex items-center gap-1 animate-pulse">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                                DOUBLE BONUS
                            </span>
                        </template>
                        <template x-if="awayFouls >= {{ $game->bonus_threshold }} && awayFouls < {{ $game->double_bonus_threshold }}">
                            <span class="px-1.5 py-0.5 rounded-lg bg-amber-500 text-slate-950 font-black text-[9px] tracking-wider uppercase border border-amber-300 shadow-md shadow-amber-500/40 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-950"></span>
                                BONUS
                            </span>
                        </template>
                        <!-- Prominent Team Fouls Display -->
                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg border transition-all font-mono"
                             :class="awayFouls >= {{ $game->double_bonus_threshold }}
                                ? 'bg-rose-950/90 border-rose-500 text-rose-200 ring-1 ring-rose-500 shadow-md shadow-rose-900/40 animate-pulse'
                                : (awayFouls >= {{ $game->bonus_threshold }}
                                    ? 'bg-amber-950/90 border-amber-500 text-amber-200 ring-1 ring-amber-500 shadow-md shadow-amber-900/40'
                                    : 'bg-slate-900 border-slate-700/80 text-slate-300')">
                            <span class="text-[9px] uppercase tracking-wider font-extrabold text-slate-400">FOULS</span>
                            <span class="text-sm sm:text-base font-black text-white leading-none" x-text="awayFouls"></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 2. CENTER TIER: ACTIVE PLAYERS GRID OR FULL-TIER ACTION PAD (Adaptive Left-to-Right Top-to-Bottom) -->
    <div class="flex-1 min-h-0 relative my-1 overflow-hidden">
        
        <!-- DEFAULT: 2-TEAM 2x3 GRIDS WITH CENTER-ANCHORED ACTION BOXES (When no player is selected) -->
        <div x-show="!selectedPlayer && !benchSelectSide && !showLineupModal && !showExecuteSubModal" class="w-full h-full grid grid-cols-2 gap-2 overflow-hidden">
        
            <!-- LEFT TEAM BOX (Container 1) -->
            <div class="h-full bg-slate-900/40 border border-slate-800/80 rounded-2xl p-2 flex flex-col min-h-0 shadow-inner">
                <!-- LH Team Header -->
                <div class="flex items-center justify-between pb-1 border-b border-slate-800 text-[11px] font-bold shrink-0">
                    <span class="uppercase tracking-wider truncate flex items-center gap-1.5" :style="{ color: isFlipped ? awayTeamColor : homeTeamColor }">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: isFlipped ? awayTeamColor : homeTeamColor }"></span>
                        <span x-text="(isFlipped ? awayTeamName : homeTeamName) + ' (Court)'"></span>
                    </span>
                    <div class="flex items-center space-x-1.5">
                        <span class="text-[10px] text-slate-400 font-mono">5 On Court</span>
                    </div>
                </div>

                <!-- LH 2x3 Grid -->
                <div class="flex-1 grid grid-cols-2 grid-rows-3 gap-1.5 sm:gap-2 min-h-0 mt-1.5">
                    <!-- Slot 1: LH Player 1 -->
                    <button @click="(isFlipped ? awayCourt : homeCourt)[0] && openActionPad(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0].jersey_number, (isFlipped ? awayCourt : homeCourt)[0].player_name, (isFlipped ? awayCourt : homeCourt)[0].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? awayCourt : homeCourt)[0] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number 
                                ? { backgroundColor: isFlipped ? awayTeamColor : homeTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? awayTeamColor : homeTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? awayTeamColor : homeTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? awayCourt : homeCourt)[0] ? '#' + (isFlipped ? awayCourt : homeCourt)[0].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? awayCourt : homeCourt)[0]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? awayCourt : homeCourt)[0] ? '#' + (isFlipped ? awayCourt : homeCourt)[0].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? awayCourt : homeCourt)[0]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[0]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 2: LH Player 2 -->
                    <button @click="(isFlipped ? awayCourt : homeCourt)[1] && openActionPad(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1].jersey_number, (isFlipped ? awayCourt : homeCourt)[1].player_name, (isFlipped ? awayCourt : homeCourt)[1].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? awayCourt : homeCourt)[1] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number 
                                ? { backgroundColor: isFlipped ? awayTeamColor : homeTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? awayTeamColor : homeTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? awayTeamColor : homeTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? awayCourt : homeCourt)[1] ? '#' + (isFlipped ? awayCourt : homeCourt)[1].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? awayCourt : homeCourt)[1]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? awayCourt : homeCourt)[1] ? '#' + (isFlipped ? awayCourt : homeCourt)[1].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? awayCourt : homeCourt)[1]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[1]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 3: LH Player 3 -->
                    <button @click="(isFlipped ? awayCourt : homeCourt)[2] && openActionPad(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2].jersey_number, (isFlipped ? awayCourt : homeCourt)[2].player_name, (isFlipped ? awayCourt : homeCourt)[2].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? awayCourt : homeCourt)[2] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number 
                                ? { backgroundColor: isFlipped ? awayTeamColor : homeTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? awayTeamColor : homeTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? awayTeamColor : homeTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? awayCourt : homeCourt)[2] ? '#' + (isFlipped ? awayCourt : homeCourt)[2].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? awayCourt : homeCourt)[2]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? awayCourt : homeCourt)[2] ? '#' + (isFlipped ? awayCourt : homeCourt)[2].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? awayCourt : homeCourt)[2]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[2]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 4: LH Player 4 -->
                    <button @click="(isFlipped ? awayCourt : homeCourt)[3] && openActionPad(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3].jersey_number, (isFlipped ? awayCourt : homeCourt)[3].player_name, (isFlipped ? awayCourt : homeCourt)[3].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? awayCourt : homeCourt)[3] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number 
                                ? { backgroundColor: isFlipped ? awayTeamColor : homeTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? awayTeamColor : homeTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? awayTeamColor : homeTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? awayCourt : homeCourt)[3] ? '#' + (isFlipped ? awayCourt : homeCourt)[3].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? awayCourt : homeCourt)[3]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? awayCourt : homeCourt)[3] ? '#' + (isFlipped ? awayCourt : homeCourt)[3].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? awayCourt : homeCourt)[3]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[3]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 5: LH Player 5 -->
                    <button @click="(isFlipped ? awayCourt : homeCourt)[4] && openActionPad(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4].jersey_number, (isFlipped ? awayCourt : homeCourt)[4].player_name, (isFlipped ? awayCourt : homeCourt)[4].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? awayCourt : homeCourt)[4] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'away' : 'home') && selectedPlayer.jersey == (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number 
                                ? { backgroundColor: isFlipped ? awayTeamColor : homeTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? awayTeamColor : homeTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? awayTeamColor : homeTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? awayCourt : homeCourt)[4] ? '#' + (isFlipped ? awayCourt : homeCourt)[4].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? awayCourt : homeCourt)[4]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? awayCourt : homeCourt)[4] ? '#' + (isFlipped ? awayCourt : homeCourt)[4].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? awayCourt : homeCourt)[4]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'away' : 'home', (isFlipped ? awayCourt : homeCourt)[4]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 6: LH BENCH STAT PICKER -->
                    <button @click="openBenchModal(isFlipped ? 'away' : 'home')"
                            type="button"
                            class="h-full w-full rounded-xl border-2 border-dashed transition-all p-2 flex flex-col justify-between items-center text-center touch-active group bg-slate-950/70 hover:bg-slate-900/90 shadow-lg"
                            :style="{ borderColor: (isFlipped ? awayTeamColor : homeTeamColor) + '80' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <span class="font-bold text-amber-300">BENCH</span>
                            <span class="px-1.5 py-0.2 rounded bg-slate-800 text-slate-200 text-[8px] font-bold" 
                                  x-text="(isFlipped ? awayBench.length : homeBench.length) + ' ON BENCH'"></span>
                        </div>
                        <div class="flex flex-col items-center justify-center my-auto">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shadow-inner mb-1"
                                 :style="{ backgroundColor: (isFlipped ? awayTeamColor : homeTeamColor) + '25', border: '1px solid ' + (isFlipped ? awayTeamColor : homeTeamColor) + '60' }">
                                <svg class="w-6 h-6" :style="{ color: isFlipped ? awayTeamColor : homeTeamColor }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <span class="text-xs sm:text-sm font-black uppercase text-white tracking-wider group-hover:scale-105 transition-transform">+ BENCH STAT</span>
                        </div>
                        <div class="w-full text-[9px] font-mono text-slate-400 truncate">
                            Tap to assign stat
                        </div>
                    </button>
                </div>
            </div>

            <!-- RIGHT TEAM BOX (Container 2) -->
            <div class="h-full bg-slate-900/40 border border-slate-800/80 rounded-2xl p-2 flex flex-col min-h-0 shadow-inner">
                <!-- RH Team Header -->
                <div class="flex items-center justify-between pb-1 border-b border-slate-800 text-[11px] font-bold shrink-0">
                    <span class="uppercase tracking-wider truncate flex items-center gap-1.5" :style="{ color: isFlipped ? homeTeamColor : awayTeamColor }">
                        <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: isFlipped ? homeTeamColor : awayTeamColor }"></span>
                        <span x-text="(isFlipped ? homeTeamName : awayTeamName) + ' (Court)'"></span>
                    </span>
                    <div class="flex items-center space-x-1.5">
                        <span class="text-[10px] text-slate-400 font-mono">5 On Court</span>
                    </div>
                </div>

                <!-- RH 2x3 Grid -->
                <div class="flex-1 grid grid-cols-2 grid-rows-3 gap-1.5 sm:gap-2 min-h-0 mt-1.5">
                    <!-- Slot 1: RH Player 1 -->
                    <button @click="(isFlipped ? homeCourt : awayCourt)[0] && openActionPad(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0].jersey_number, (isFlipped ? homeCourt : awayCourt)[0].player_name, (isFlipped ? homeCourt : awayCourt)[0].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? homeCourt : awayCourt)[0] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number 
                                ? { backgroundColor: isFlipped ? homeTeamColor : awayTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? homeTeamColor : awayTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? homeTeamColor : awayTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? homeCourt : awayCourt)[0] ? '#' + (isFlipped ? homeCourt : awayCourt)[0].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? homeCourt : awayCourt)[0]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? homeCourt : awayCourt)[0] ? '#' + (isFlipped ? homeCourt : awayCourt)[0].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? homeCourt : awayCourt)[0]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[0]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 2: RH Player 2 -->
                    <button @click="(isFlipped ? homeCourt : awayCourt)[1] && openActionPad(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1].jersey_number, (isFlipped ? homeCourt : awayCourt)[1].player_name, (isFlipped ? homeCourt : awayCourt)[1].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? homeCourt : awayCourt)[1] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number 
                                ? { backgroundColor: isFlipped ? homeTeamColor : awayTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? homeTeamColor : awayTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? homeTeamColor : awayTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? homeCourt : awayCourt)[1] ? '#' + (isFlipped ? homeCourt : awayCourt)[1].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? homeCourt : awayCourt)[1]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? homeCourt : awayCourt)[1] ? '#' + (isFlipped ? homeCourt : awayCourt)[1].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? homeCourt : awayCourt)[1]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[1]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 3: RH Player 3 -->
                    <button @click="(isFlipped ? homeCourt : awayCourt)[2] && openActionPad(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2].jersey_number, (isFlipped ? homeCourt : awayCourt)[2].player_name, (isFlipped ? homeCourt : awayCourt)[2].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? homeCourt : awayCourt)[2] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number 
                                ? { backgroundColor: isFlipped ? homeTeamColor : awayTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? homeTeamColor : awayTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? homeTeamColor : awayTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? homeCourt : awayCourt)[2] ? '#' + (isFlipped ? homeCourt : awayCourt)[2].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? homeCourt : awayCourt)[2]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? homeCourt : awayCourt)[2] ? '#' + (isFlipped ? homeCourt : awayCourt)[2].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? homeCourt : awayCourt)[2]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[2]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 4: RH Player 4 -->
                    <button @click="(isFlipped ? homeCourt : awayCourt)[3] && openActionPad(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3].jersey_number, (isFlipped ? homeCourt : awayCourt)[3].player_name, (isFlipped ? homeCourt : awayCourt)[3].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? homeCourt : awayCourt)[3] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number 
                                ? { backgroundColor: isFlipped ? homeTeamColor : awayTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? homeTeamColor : awayTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? homeTeamColor : awayTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? homeCourt : awayCourt)[3] ? '#' + (isFlipped ? homeCourt : awayCourt)[3].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? homeCourt : awayCourt)[3]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? homeCourt : awayCourt)[3] ? '#' + (isFlipped ? homeCourt : awayCourt)[3].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? homeCourt : awayCourt)[3]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[3]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>

                    <!-- Slot 5: RH BENCH STAT PICKER (Inside Column 1, adjacent to center court) -->
                    <button @click="openBenchModal(isFlipped ? 'home' : 'away')"
                            type="button"
                            class="h-full w-full rounded-xl border-2 border-dashed transition-all p-2 flex flex-col justify-between items-center text-center touch-active group bg-slate-950/70 hover:bg-slate-900/90 shadow-lg"
                            :style="{ borderColor: (isFlipped ? homeTeamColor : awayTeamColor) + '80' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <span class="font-bold text-amber-300">BENCH</span>
                            <span class="px-1.5 py-0.2 rounded bg-slate-800 text-slate-200 text-[8px] font-bold" 
                                  x-text="(isFlipped ? homeBench.length : awayBench.length) + ' ON BENCH'"></span>
                        </div>
                        <div class="flex flex-col items-center justify-center my-auto">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl flex items-center justify-center shadow-inner mb-1"
                                 :style="{ backgroundColor: (isFlipped ? homeTeamColor : awayTeamColor) + '25', border: '1px solid ' + (isFlipped ? homeTeamColor : awayTeamColor) + '60' }">
                                <svg class="w-6 h-6" :style="{ color: isFlipped ? homeTeamColor : awayTeamColor }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <span class="text-xs sm:text-sm font-black uppercase text-white tracking-wider group-hover:scale-105 transition-transform">+ BENCH STAT</span>
                        </div>
                        <div class="w-full text-[9px] font-mono text-slate-400 truncate">
                            Tap to assign stat
                        </div>
                    </button>

                    <!-- Slot 6: RH Player 5 (Outside Column 2) -->
                    <button @click="(isFlipped ? homeCourt : awayCourt)[4] && openActionPad(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4].jersey_number, (isFlipped ? homeCourt : awayCourt)[4].player_name, (isFlipped ? homeCourt : awayCourt)[4].id)"
                            type="button"
                            :class="[
                                !(isFlipped ? homeCourt : awayCourt)[4] ? 'invisible pointer-events-none' : '',
                                selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number
                                    ? 'text-white border-white ring-2 ring-white/50' 
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                        ? 'bg-rose-950/40 border-rose-500 ring-2 ring-rose-500 shadow-lg shadow-rose-950/80 animate-pulse'
                                        : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                            ? 'bg-slate-950/90 hover:bg-slate-900 border-amber-500/80 ring-1 ring-amber-500/50'
                                            : 'bg-slate-950/90 hover:bg-slate-900'))
                            ]"
                            class="h-full w-full rounded-xl border-2 transition-all p-1.5 sm:p-2 flex flex-col justify-between items-center text-center touch-active group overflow-hidden"
                            :style="selectedPlayer && selectedPlayer.side === (isFlipped ? 'home' : 'away') && selectedPlayer.jersey == (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number 
                                ? { backgroundColor: isFlipped ? homeTeamColor : awayTeamColor, borderColor: '#ffffff', boxShadow: '0 8px 20px -4px ' + (isFlipped ? homeTeamColor : awayTeamColor) + '99' } 
                                : { borderColor: (isFlipped ? homeTeamColor : awayTeamColor) + '40' }">
                        <div class="w-full flex items-center justify-between text-[9px] font-mono text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="font-bold text-amber-300" x-text="(isFlipped ? homeCourt : awayCourt)[4] ? '#' + (isFlipped ? homeCourt : awayCourt)[4].jersey_number : ''"></span>
                                <span class="px-1 py-0.2 rounded bg-slate-800 text-slate-300 text-[8px] font-bold" x-text="(isFlipped ? homeCourt : awayCourt)[4]?.position || 'G/F'"></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span x-show="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                      class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                    5 FOULS
                                </span>
                                <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]" title="Points">
                                    <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).points"></span>p
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-center justify-center my-0.5">
                            <div class="font-mono text-2xl sm:text-3xl font-black text-white leading-none tracking-tight" x-text="(isFlipped ? homeCourt : awayCourt)[4] ? '#' + (isFlipped ? homeCourt : awayCourt)[4].jersey_number : ''"></div>
                            <div class="w-full text-xs font-bold text-slate-200 truncate leading-tight mt-0.5" x-text="(isFlipped ? homeCourt : awayCourt)[4]?.player_name || ''"></div>
                        </div>
                        <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono">
                            <div class="flex items-center gap-1 text-slate-400">
                                <span title="Personal Fouls">PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).fouls_pers"></strong></span>
                                <span title="Offensive Fouls">OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).fouls_off"></strong></span>
                                <span title="Technical Fouls">TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).fouls_tech"></strong></span>
                            </div>
                            <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                  :class="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                    ? 'bg-rose-600 text-white animate-pulse'
                                    : (getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                        ? 'bg-amber-500 text-slate-950 font-black'
                                        : 'bg-slate-800 text-slate-300')"
                                  title="Total Fouls">
                                <span x-text="getPlayerStat(isFlipped ? 'home' : 'away', (isFlipped ? homeCourt : awayCourt)[4]?.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                            </span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- BENCH PLAYER SELECTION DIALOG (Takes up 100% of the player card area, just as big as Action Pad) -->
        <template x-if="benchSelectSide && !selectedPlayer && !showLineupModal && !showExecuteSubModal">
            <div class="w-full h-full bg-slate-900 border-2 rounded-2xl p-2.5 sm:p-3.5 md:p-4 shadow-2xl flex flex-col justify-between overflow-hidden select-none relative"
                 :style="{ borderColor: benchSelectSide === 'home' ? homeTeamColor : awayTeamColor, boxShadow: '0 8px 30px -4px ' + (benchSelectSide === 'home' ? homeTeamColor : awayTeamColor) + '60' }">
                
                <!-- Target Header -->
                <div class="flex items-center justify-between pb-2 border-b border-slate-800 shrink-0">
                    <div class="flex items-center space-x-3">
                        <span class="px-3 sm:px-4 py-1.5 rounded-xl text-white font-mono font-black text-sm sm:text-base shadow-lg flex items-center justify-center shrink-0"
                              :style="{ backgroundColor: benchSelectSide === 'home' ? homeTeamColor : awayTeamColor }">
                            BENCH
                        </span>
                        <div>
                            <h3 class="text-base sm:text-xl font-black text-white leading-tight">
                                Select Bench Player to Record Stat
                            </h3>
                            <span class="text-[10px] sm:text-xs font-mono font-bold uppercase tracking-wider"
                                  :style="{ color: benchSelectSide === 'home' ? homeTeamColor : awayTeamColor }"
                                  x-text="(benchSelectSide === 'home' ? homeTeamName : awayTeamName).toUpperCase() + ' (' + (benchSelectSide === 'home' ? homeBench.length : awayBench.length) + ' ON BENCH)'">
                            </span>
                        </div>
                    </div>

                    <!-- Nice Big Exit Button Up Top -->
                    <button @click="benchSelectSide = null" 
                            class="py-2 px-3 sm:px-4 rounded-xl bg-slate-800 text-slate-200 hover:text-white hover:bg-rose-900/90 hover:border-rose-500 border border-slate-700 transition shadow shrink-0 flex items-center gap-2 touch-active font-black text-xs sm:text-sm" 
                            title="Close Bench Selector (Esc)">
                        <span class="font-mono">CLOSE</span>
                        <span class="px-1.5 py-0.5 rounded bg-black/40 border border-white/20 text-[9px] font-mono text-amber-300">Esc</span>
                        <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Bench Players Grid: Each player's button is as big as it can be while showing all players on the bench -->
                <div class="flex-1 min-h-0 overflow-y-auto py-2 sm:py-3">
                    <div class="grid gap-2 sm:gap-3 h-full auto-rows-fr"
                         :class="{
                             'grid-cols-2': (benchSelectSide === 'home' ? homeBench.length : awayBench.length) <= 2,
                             'grid-cols-2 sm:grid-cols-3': (benchSelectSide === 'home' ? homeBench.length : awayBench.length) >= 3 && (benchSelectSide === 'home' ? homeBench.length : awayBench.length) <= 6,
                             'grid-cols-2 sm:grid-cols-4': (benchSelectSide === 'home' ? homeBench.length : awayBench.length) >= 7 && (benchSelectSide === 'home' ? homeBench.length : awayBench.length) <= 10,
                             'grid-cols-3 sm:grid-cols-4 md:grid-cols-5': (benchSelectSide === 'home' ? homeBench.length : awayBench.length) > 10
                         }">
                        <template x-for="player in (benchSelectSide === 'home' ? homeBench : awayBench)" :key="player.id || player.jersey_number">
                            <button @click="selectBenchPlayer(player, benchSelectSide)"
                                    type="button"
                                    class="h-full min-h-[90px] sm:min-h-[110px] w-full rounded-2xl border-2 bg-slate-950/90 hover:bg-slate-900 hover:scale-[1.02] active:scale-[0.98] transition-all p-2.5 sm:p-3 flex flex-col justify-between items-center text-center touch-active shadow-lg group"
                                    :style="{ borderColor: (benchSelectSide === 'home' ? homeTeamColor : awayTeamColor) + '60' }">
                                <div class="w-full flex items-center justify-between text-[10px] font-mono text-slate-400">
                                    <span class="font-bold text-amber-300" x-text="'#' + player.jersey_number"></span>
                                    <div class="flex items-center gap-1">
                                        <span x-show="getPlayerStat(benchSelectSide, player.jersey_number).total_fouls >= {{ $game->player_foul_limit }}" 
                                              class="px-1.5 py-0.2 rounded bg-rose-600 text-white font-black text-[8px] tracking-wider uppercase animate-pulse shadow-sm">
                                            5 FOULS
                                        </span>
                                        <span class="px-1.5 py-0.2 rounded bg-emerald-950/90 border border-emerald-500/40 text-emerald-300 font-mono font-black text-[9px]">
                                            <span x-text="getPlayerStat(benchSelectSide, player.jersey_number).points"></span>p
                                        </span>
                                    </div>
                                </div>
                                <div class="font-mono text-3xl sm:text-4xl lg:text-5xl font-black text-white leading-none tracking-tight my-1 group-hover:scale-105 transition-transform" 
                                     x-text="'#' + player.jersey_number"></div>
                                <div class="w-full text-xs sm:text-sm font-black text-slate-100 truncate leading-tight" 
                                     x-text="player.player_name || ('Player #' + player.jersey_number)"></div>
                                <div class="w-full pt-1 border-t border-slate-800/80 flex items-center justify-between text-[8px] sm:text-[9px] font-mono mt-1">
                                    <div class="flex items-center gap-1 text-slate-400">
                                        <span>PF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(benchSelectSide, player.jersey_number).fouls_pers"></strong></span>
                                        <span>OF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(benchSelectSide, player.jersey_number).fouls_off"></strong></span>
                                        <span>TF:<strong class="text-slate-200 font-black" x-text="getPlayerStat(benchSelectSide, player.jersey_number).fouls_tech"></strong></span>
                                    </div>
                                    <span class="px-1.5 py-0.2 rounded font-black tracking-tight"
                                          :class="getPlayerStat(benchSelectSide, player.jersey_number).total_fouls >= {{ $game->player_foul_limit }}
                                            ? 'bg-rose-600 text-white animate-pulse'
                                            : (getPlayerStat(benchSelectSide, player.jersey_number).total_fouls >= ({{ $game->player_foul_limit }} - 1)
                                                ? 'bg-amber-500 text-slate-950 font-black'
                                                : 'bg-slate-800 text-slate-300')">
                                        <span x-text="getPlayerStat(benchSelectSide, player.jersey_number).total_fouls"></span>/{{ $game->player_foul_limit }} F
                                    </span>
                                </div>
                            </button>
                        </template>
                    </div>

                    <!-- Empty Bench State -->
                    <template x-if="(benchSelectSide === 'home' ? homeBench.length : awayBench.length) === 0">
                        <div class="h-full flex flex-col items-center justify-center text-center p-6 space-y-3">
                            <svg class="w-12 h-12 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            <p class="text-slate-400 font-bold text-sm">No bench players found on roster.</p>
                            <button @click="@this.openRosterModal(benchSelectSide); benchSelectSide = null" 
                                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-md">
                                Open Team Roster
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Footer Hint -->
                <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400 font-mono shrink-0">
                    <span>Tap player to immediately open Action Pad</span>
                    <span>Or type <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-200">#</kbd> + <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-200">-</kbd> / <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-200">=</kbd> anytime</span>
                </div>
            </div>
        </template>

        <!-- ACTIVE: FULL ACTION PAD (Takes up 100% of the player card area left-to-right, top-to-bottom) -->
        <template x-if="selectedPlayer">
            <div class="w-full h-full bg-slate-900 border-2 rounded-2xl p-2.5 sm:p-3.5 md:p-4 shadow-2xl flex flex-col justify-between overflow-hidden select-none relative"
                 :style="{ borderColor: selectedPlayer.side === 'home' ? homeTeamColor : awayTeamColor, boxShadow: '0 8px 30px -4px ' + (selectedPlayer.side === 'home' ? homeTeamColor : awayTeamColor) + '60' }">
                
                <!-- Target Player Header -->
                <div class="flex items-center justify-between pb-1.5 sm:pb-2 border-b border-slate-800 shrink-0">
                    <div class="flex items-center space-x-2.5 sm:space-x-3">
                        <span class="px-3 sm:px-4 py-1 rounded-xl text-white font-mono font-black text-xl sm:text-2xl shadow-lg flex items-center justify-center shrink-0"
                              :style="{ backgroundColor: selectedPlayer.side === 'home' ? homeTeamColor : awayTeamColor }"
                              x-text="'#' + selectedPlayer.jersey">
                        </span>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base sm:text-xl font-black text-white leading-tight" x-text="selectedPlayer.name"></h3>
                                <template x-if="getPlayerStat(selectedPlayer.side, selectedPlayer.jersey).total_fouls >= {{ $game->player_foul_limit }}">
                                    <span class="px-1.5 py-0.5 rounded bg-rose-600 text-white text-[9px] font-black uppercase tracking-wider animate-pulse shadow-md">
                                        5 FOULS (FOULED OUT)
                                    </span>
                                </template>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 text-[10px] sm:text-xs font-mono">
                                <span class="font-bold uppercase tracking-wider"
                                      :style="{ color: selectedPlayer.side === 'home' ? homeTeamColor : awayTeamColor }"
                                      x-text="(selectedPlayer.side === 'home' ? homeTeamName : awayTeamName).toUpperCase() + ' (' + selectedPlayer.side.toUpperCase() + ')'">
                                </span>
                                <span class="text-slate-500">•</span>
                                <span class="text-emerald-400 font-bold"><strong x-text="getPlayerStat(selectedPlayer.side, selectedPlayer.jersey).points"></strong> PTS</span>
                                <span class="text-slate-500">•</span>
                                <span class="text-slate-300 font-bold">
                                    <strong x-text="getPlayerStat(selectedPlayer.side, selectedPlayer.jersey).total_fouls"></strong>/{{ $game->player_foul_limit }} Fouls 
                                    (<span x-text="getPlayerStat(selectedPlayer.side, selectedPlayer.jersey).fouls_pers"></span> PF, 
                                     <span x-text="getPlayerStat(selectedPlayer.side, selectedPlayer.jersey).fouls_off"></span> OF, 
                                     <span x-text="getPlayerStat(selectedPlayer.side, selectedPlayer.jersey).fouls_tech"></span> TF<template x-if="getPlayerStat(selectedPlayer.side, selectedPlayer.jersey).fouls_flagrant > 0">, <span x-text="getPlayerStat(selectedPlayer.side, selectedPlayer.jersey).fouls_flagrant"></span> FF</template>)
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button @click="quickSubForPlayer(selectedPlayer.side, selectedPlayer.jersey)" 
                                type="button"
                                class="px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-xl bg-indigo-950/90 hover:bg-indigo-900 border border-indigo-400/60 hover:border-indigo-300 text-indigo-300 hover:text-white transition shadow shrink-0 flex items-center gap-1.5 touch-active" 
                                title="Substitute this player out (Hotkey: Tab)">
                            <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            <span class="text-xs font-black font-mono">SUB OUT</span>
                            <span class="px-1 py-0.2 rounded bg-indigo-950 border border-indigo-400/50 text-[9px] font-mono font-bold text-amber-300 hidden sm:inline">Tab</span>
                        </button>
                        <button @click="selectedPlayer = null; closeAssistModal()" class="p-1.5 sm:p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white hover:bg-rose-900/80 hover:border-rose-500 border border-slate-700 transition shadow shrink-0 flex items-center gap-1.5" title="Close (Esc)">
                            <span class="text-xs font-bold font-mono text-slate-400 hidden sm:inline">Close</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Structured Action Groups (Fills middle height evenly) -->
                <div class="flex-1 flex flex-col justify-evenly gap-1.5 sm:gap-2.5 py-1 sm:py-1.5 min-h-0">
                    
                    <!-- BLOCK 1: SCORING & SHOOTING -->
                    <div class="bg-slate-950/70 p-2 sm:p-2.5 rounded-xl border border-slate-800/90 shadow-inner flex flex-col justify-center flex-1 min-h-0">
                        <div class="flex items-center justify-between px-1 pb-1">
                            <span class="text-[10px] sm:text-xs font-mono font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Scoring & Shooting
                            </span>
                            <span class="text-[9px] sm:text-[10px] font-mono text-amber-300/90 font-medium">Tip: Hold +2 / +3 to Drag-Assist</span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 sm:gap-3 flex-1 min-h-0">
                            <!-- 2PT COLUMN -->
                            <div class="flex flex-col gap-1 sm:gap-1.5 h-full">
                                <button type="button"
                                        @pointerdown="onShotButtonPointerDown($event, 'X')"
                                        @click="onShotButtonClick('X')"
                                        class="w-full py-2 sm:py-3 px-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-[0.98] transition-all text-white border-2 border-emerald-400/80 shadow-md shadow-emerald-950/50 flex flex-col items-center justify-center flex-1 touch-none group">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm sm:text-base md:text-lg font-black tracking-wide">+2 MADE</span>
                                        <span class="px-1.5 py-0.2 rounded bg-emerald-950/80 border border-emerald-300/60 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300 shadow">X</span>
                                    </div>
                                    <span class="text-[9px] sm:text-[10px] font-mono opacity-90 font-semibold">2pt Field Goal</span>
                                    <span class="text-[7px] sm:text-[8px] font-mono text-emerald-200/90 font-medium tracking-tight">Tap: Solo • Hold: Assist</span>
                                </button>
                                <button @click="executeAction('Z')" class="w-full py-1 sm:py-1.5 px-2 rounded-lg bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition-all text-slate-200 border border-slate-700 hover:border-slate-500 shadow flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs sm:text-sm font-bold">2PT MISS</span>
                                        <span class="px-1.5 py-0.2 rounded bg-slate-900 border border-slate-600 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">Z</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono text-slate-400">2pt Miss</span>
                                </button>
                            </div>

                            <!-- 3PT COLUMN -->
                            <div class="flex flex-col gap-1 sm:gap-1.5 h-full">
                                <button type="button"
                                        @pointerdown="onShotButtonPointerDown($event, 'M')"
                                        @click="onShotButtonClick('M')"
                                        class="w-full py-2 sm:py-3 px-2 rounded-xl bg-teal-600 hover:bg-teal-500 active:scale-[0.98] transition-all text-white border-2 border-teal-400/80 shadow-md shadow-teal-950/50 flex flex-col items-center justify-center flex-1 touch-none group">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm sm:text-base md:text-lg font-black tracking-wide">+3 MADE</span>
                                        <span class="px-1.5 py-0.2 rounded bg-teal-950/80 border border-teal-300/60 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300 shadow">M</span>
                                    </div>
                                    <span class="text-[9px] sm:text-[10px] font-mono opacity-90 font-semibold">3pt Shot</span>
                                    <span class="text-[7px] sm:text-[8px] font-mono text-teal-200/90 font-medium tracking-tight">Tap: Solo • Hold: Assist</span>
                                </button>
                                <button @click="executeAction('N')" class="w-full py-1 sm:py-1.5 px-2 rounded-lg bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition-all text-slate-200 border border-slate-700 hover:border-slate-500 shadow flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs sm:text-sm font-bold">3PT MISS</span>
                                        <span class="px-1.5 py-0.2 rounded bg-slate-900 border border-slate-600 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">N</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono text-slate-400">3pt Miss</span>
                                </button>
                            </div>

                            <!-- FT COLUMN -->
                            <div class="flex flex-col gap-1 sm:gap-1.5 h-full">
                                <button @click="executeAction('B')" class="w-full py-2 sm:py-3 px-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 active:scale-[0.98] transition-all text-white border-2 border-cyan-400/80 shadow-md shadow-cyan-950/50 flex flex-col items-center justify-center flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm sm:text-base md:text-lg font-black tracking-wide">+1 FT MAKE</span>
                                        <span class="px-1.5 py-0.2 rounded bg-cyan-950/80 border border-cyan-300/60 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300 shadow">B</span>
                                    </div>
                                    <span class="text-[9px] sm:text-[10px] font-mono opacity-90 font-semibold">Free Throw</span>
                                </button>
                                <button @click="executeAction('V')" class="w-full py-1 sm:py-1.5 px-2 rounded-lg bg-slate-800 hover:bg-slate-700 active:scale-[0.98] transition-all text-slate-200 border border-slate-700 hover:border-slate-500 shadow flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs sm:text-sm font-bold">FT MISS</span>
                                        <span class="px-1.5 py-0.2 rounded bg-slate-900 border border-slate-600 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">V</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono text-slate-400">FT Miss</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ROW 1: DEDICATED REBOUNDS BOX + PLAYMAKING / DEFENSE BOX -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 sm:gap-2.5">
                        <!-- REBOUNDS DEDICATED BOX (Cols 1-5 on md) -->
                        <div class="md:col-span-5 bg-slate-950/70 p-2 sm:p-2.5 rounded-xl border border-blue-900/50 shadow-inner flex flex-col justify-between">
                            <div class="flex items-center justify-between px-1 pb-1.5">
                                <span class="text-[10px] sm:text-xs font-mono font-bold text-blue-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    Rebounds
                                </span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 flex-1 items-stretch">
                                <!-- DEF REBOUND -->
                                <button @click="executeAction('D')" class="py-2.5 sm:py-3 px-2 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-[0.98] transition-all text-white border border-blue-400/80 shadow-md shadow-blue-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs sm:text-sm md:text-base font-black tracking-wide">DEF REB</span>
                                        <span class="px-1.5 py-0.5 rounded bg-blue-950 border border-blue-300/50 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300">D</span>
                                    </div>
                                    <span class="text-[9px] sm:text-[10px] font-mono opacity-85 mt-0.5">Defensive</span>
                                </button>

                                <!-- OFF REBOUND -->
                                <button @click="executeAction('O')" class="py-2.5 sm:py-3 px-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-[0.98] transition-all text-white border border-indigo-400/80 shadow-md shadow-indigo-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs sm:text-sm md:text-base font-black tracking-wide">OFF REB</span>
                                        <span class="px-1.5 py-0.5 rounded bg-indigo-950 border border-indigo-300/50 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300">O</span>
                                    </div>
                                    <span class="text-[9px] sm:text-[10px] font-mono opacity-85 mt-0.5">Offensive</span>
                                </button>
                            </div>
                        </div>

                        <!-- PLAYMAKING & DEFENSE BOX (Cols 6-12 on md) -->
                        <div class="md:col-span-7 bg-slate-950/70 p-2 sm:p-2.5 rounded-xl border border-slate-800/90 shadow-inner flex flex-col justify-between">
                            <div class="flex items-center justify-between px-1 pb-1.5">
                                <span class="text-[10px] sm:text-xs font-mono font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    Playmaking & Defense
                                </span>
                            </div>

                            <div class="grid grid-cols-4 gap-1.5 sm:gap-2 flex-1 items-stretch">
                                <!-- ASSIST -->
                                <button @click="executeAction('A')" class="py-2.5 sm:py-3 px-1 rounded-xl bg-amber-600 hover:bg-amber-500 active:scale-[0.98] transition-all text-white border border-amber-400/80 shadow-md shadow-amber-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black tracking-wide">ASSIST</span>
                                        <span class="px-1 py-0.2 rounded bg-amber-950 border border-amber-300/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">A</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Pass Ast</span>
                                </button>

                                <!-- STEAL -->
                                <button @click="executeAction('S')" class="py-2.5 sm:py-3 px-1 rounded-xl bg-purple-600 hover:bg-purple-500 active:scale-[0.98] transition-all text-white border border-purple-400/80 shadow-md shadow-purple-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black tracking-wide">STEAL</span>
                                        <span class="px-1 py-0.2 rounded bg-purple-950 border border-purple-300/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">S</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Takeaway</span>
                                </button>

                                <!-- BLOCK -->
                                <button @click="executeAction('K')" class="py-2.5 sm:py-3 px-1 rounded-xl bg-fuchsia-700 hover:bg-fuchsia-600 active:scale-[0.98] transition-all text-white border border-fuchsia-500/80 shadow-md shadow-fuchsia-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black tracking-wide">BLOCK</span>
                                        <span class="px-1 py-0.2 rounded bg-fuchsia-950 border border-fuchsia-300/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">K</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Block</span>
                                </button>

                                <!-- SWAT -->
                                <button @click="executeAction('W')" class="py-2.5 sm:py-3 px-1 rounded-xl bg-violet-700 hover:bg-violet-600 active:scale-[0.98] transition-all text-white border border-violet-500/80 shadow-md shadow-violet-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black tracking-wide">SWAT</span>
                                        <span class="px-1 py-0.2 rounded bg-violet-950 border border-violet-300/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">W</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Swat</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ROW 2: TURNOVERS & FOULS (SIDE BY SIDE, ENLARGED BUTTONS) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-2.5">
                        <!-- TURNOVERS BLOCK -->
                        <div class="bg-slate-950/70 p-2 sm:p-2.5 rounded-xl border border-rose-900/50 shadow-inner space-y-1.5 flex flex-col justify-between">
                            <div class="flex items-center justify-between px-1">
                                <span class="text-[10px] sm:text-xs font-mono font-bold text-rose-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Turnovers
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-1.5 sm:gap-2 flex-1 items-stretch">
                                <button @click="executeAction('P')" class="py-2.5 sm:py-3 px-1.5 rounded-xl bg-rose-700 hover:bg-rose-600 active:scale-[0.98] transition-all text-white border border-rose-500/80 shadow-md shadow-rose-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black">PASS TO</span>
                                        <span class="px-1 py-0.2 rounded bg-rose-950 border border-rose-300/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">P</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Passing TO</span>
                                </button>
                                <button @click="executeAction('U')" class="py-2.5 sm:py-3 px-1.5 rounded-xl bg-rose-800 hover:bg-rose-700 active:scale-[0.98] transition-all text-white border border-rose-600/80 shadow-md shadow-rose-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black">FUMBLE</span>
                                        <span class="px-1 py-0.2 rounded bg-rose-950 border border-rose-400/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">U</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Fumble TO</span>
                                </button>
                                <button @click="executeAction('I')" class="py-2.5 sm:py-3 px-1.5 rounded-xl bg-rose-900 hover:bg-rose-800 active:scale-[0.98] transition-all text-white border border-rose-700/80 shadow-md shadow-rose-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black">VIOLATION</span>
                                        <span class="px-1 py-0.2 rounded bg-rose-950 border border-rose-400/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">I</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Violation TO</span>
                                </button>
                            </div>
                        </div>

                        <!-- FOULS BLOCK -->
                        <div class="bg-slate-950/70 p-2 sm:p-2.5 rounded-xl border border-red-900/50 shadow-inner space-y-1.5 flex flex-col justify-between">
                            <div class="flex items-center justify-between px-1">
                                <span class="text-[10px] sm:text-xs font-mono font-bold text-red-400 uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Fouls
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-1.5 sm:gap-2 flex-1 items-stretch">
                                <button @click="executeAction('F')" class="py-2.5 sm:py-3 px-1.5 rounded-xl bg-red-700 hover:bg-red-600 active:scale-[0.98] transition-all text-white border border-red-500/80 shadow-md shadow-red-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black">PERS FOUL</span>
                                        <span class="px-1 py-0.2 rounded bg-red-950 border border-red-300/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">F</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Personal</span>
                                </button>
                                <button @click="executeAction('R')" class="py-2.5 sm:py-3 px-1.5 rounded-xl bg-red-800 hover:bg-red-700 active:scale-[0.98] transition-all text-white border border-red-600/80 shadow-md shadow-red-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black">OFF FOUL</span>
                                        <span class="px-1 py-0.2 rounded bg-red-950 border border-red-400/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">R</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Offensive</span>
                                </button>
                                <button @click="executeAction('T')" class="py-2.5 sm:py-3 px-1.5 rounded-xl bg-amber-800 hover:bg-amber-700 active:scale-[0.98] transition-all text-white border border-amber-500/80 shadow-md shadow-amber-950 flex flex-col items-center justify-center">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs sm:text-sm font-black">TECH FOUL</span>
                                        <span class="px-1 py-0.2 rounded bg-amber-950 border border-amber-400/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300">T</span>
                                    </div>
                                    <span class="text-[8px] sm:text-[9px] font-mono opacity-85 mt-0.5">Technical</span>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Bar -->
                <div class="pt-1.5 sm:pt-2 border-t border-slate-800 flex items-center justify-between shrink-0">
                    <span class="text-[10px] sm:text-xs text-slate-400 font-medium hidden sm:inline">
                        Press key on keyboard or tap button • Hold +2/+3 to drag-assist • <kbd class="px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 font-mono text-[9px] sm:text-[10px] text-slate-300">Esc</kbd> to close
                    </span>

                    <button @click="quickSubForPlayer(selectedPlayer.side, selectedPlayer.jersey)" class="px-4 py-2 rounded-xl bg-indigo-900/90 hover:bg-indigo-800 border-2 border-indigo-400/80 text-white hover:text-white font-black text-xs sm:text-sm shadow-lg transition flex items-center gap-2 ml-auto touch-active">
                        <svg class="w-4 h-4 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span>Sub Out This Player</span>
                        <span class="px-1.5 py-0.2 rounded bg-indigo-950 border border-indigo-400/50 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300">Tab</span>
                    </button>
                </div>

                <!-- ASSIST SELECTOR OVERLAY (Instant Hold & Drag-to-Assist) -->
                <template x-if="assistModal && assistModal.open">
                    <div class="absolute inset-0 z-40 bg-slate-950/95 backdrop-blur-md p-3 sm:p-4 rounded-2xl flex flex-col justify-between border-2 border-amber-500/80 shadow-2xl select-none touch-none animate-in fade-in zoom-in-95 duration-150">
                        <!-- Overlay Header -->
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800 shrink-0">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg text-white font-mono font-black text-xs sm:text-sm shadow"
                                      :class="assistModal.actionCode === 'M' ? 'bg-teal-600 border border-teal-400/80' : 'bg-emerald-600 border border-emerald-400/80'"
                                      x-text="assistModal.actionCode === 'M' ? '+3 3PT MADE' : '+2 2PT MADE'">
                                </span>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-black text-amber-300 uppercase tracking-wide flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-400 shrink-0 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        <span>Drag Finger To Assisting Teammate</span>
                                    </h4>
                                    <span class="text-[9px] sm:text-[10px] font-mono text-slate-400">Release finger on teammate to award assist, or tap below</span>
                                </div>
                            </div>

                            <button type="button" @click="closeAssistModal()" class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-900 text-slate-300 hover:text-white border border-slate-700 transition shadow shrink-0" title="Close assist picker">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <!-- Teammate Drag Targets Grid -->
                        <div class="flex-1 py-2 sm:py-3 flex flex-col justify-center min-h-0">
                            <div class="text-[10px] sm:text-xs font-mono font-bold text-slate-300 uppercase tracking-wider mb-2 flex items-center justify-between px-1">
                                <span>Other Players On Court (<span x-text="selectedPlayer ? (selectedPlayer.side === 'home' ? homeTeamName : awayTeamName) : ''"></span>)</span>
                                <span class="text-[9px] text-amber-400 font-normal">Release over player card</span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 flex-1 items-stretch min-h-0">
                                <template x-for="teammate in getOtherCourtPlayers()" :key="teammate.id || teammate.jersey_number">
                                    <button type="button"
                                            :data-assist-jersey="teammate.jersey_number"
                                            @click="selectAssistTeammate(teammate.jersey_number)"
                                            class="relative p-2 sm:p-3 rounded-xl flex flex-col items-center justify-between transition-all duration-150 border-2 text-center group cursor-pointer select-none"
                                            :class="assistModal.hoveredJersey === String(teammate.jersey_number)
                                                ? 'bg-amber-500/25 border-amber-400 ring-4 ring-amber-400/80 shadow-xl shadow-amber-500/40 scale-[1.04]'
                                                : 'bg-slate-900/90 border-slate-700/80 hover:border-amber-400/60 hover:bg-slate-800/90 shadow'">
                                        
                                        <!-- Top: Position & Assist Indicator -->
                                        <div class="w-full flex items-center justify-between">
                                            <span class="px-1.5 py-0.5 rounded text-[8px] sm:text-[9px] font-bold font-mono"
                                                  :class="assistModal.hoveredJersey === String(teammate.jersey_number) ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-800 text-slate-300'"
                                                  x-text="teammate.position || 'G/F'">
                                            </span>
                                            <span class="text-[8px] sm:text-[9px] font-mono font-bold uppercase tracking-wider"
                                                  :class="assistModal.hoveredJersey === String(teammate.jersey_number) ? 'text-amber-300 animate-pulse' : 'text-slate-500'">
                                                <span x-text="assistModal.hoveredJersey === String(teammate.jersey_number) ? 'TARGET' : 'AST'"></span>
                                            </span>
                                        </div>

                                        <!-- Center: Large Jersey Number -->
                                        <div class="font-mono text-3xl sm:text-4xl lg:text-5xl font-black leading-none my-1 tracking-tight transition-transform"
                                             :class="assistModal.hoveredJersey === String(teammate.jersey_number) ? 'text-amber-300 scale-110 drop-shadow-[0_0_10px_rgba(251,191,36,0.6)]' : 'text-white'"
                                             x-text="'#' + teammate.jersey_number">
                                        </div>

                                        <!-- Bottom: Player Name & Action Prompt -->
                                        <div class="w-full">
                                            <div class="text-xs sm:text-sm font-bold truncate leading-tight"
                                                 :class="assistModal.hoveredJersey === String(teammate.jersey_number) ? 'text-white' : 'text-slate-200'"
                                                 x-text="teammate.player_name || ('#' + teammate.jersey_number)">
                                            </div>
                                            <div class="text-[9px] sm:text-[10px] font-mono font-bold uppercase tracking-wider mt-0.5"
                                                 :class="assistModal.hoveredJersey === String(teammate.jersey_number) ? 'text-amber-300 font-black' : 'text-slate-400'">
                                                <span x-text="assistModal.hoveredJersey === String(teammate.jersey_number) ? '✓ RELEASE TO ASSIST' : 'Drag or Tap'"></span>
                                            </div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Overlay Bottom Actions: Solo Basket vs Cancel -->
                        <div class="pt-2 border-t border-slate-800 flex items-center justify-between gap-2 shrink-0">
                            <button type="button"
                                    :data-assist-jersey="'none'"
                                    @click="executeAction(assistModal.actionCode); closeAssistModal();"
                                    class="flex-1 py-2 px-3 rounded-xl font-mono text-xs sm:text-sm font-bold border transition-all flex items-center justify-center gap-2"
                                    :class="assistModal.hoveredJersey === 'none'
                                        ? 'bg-emerald-600 text-white border-emerald-400 ring-2 ring-emerald-400 scale-[1.02]'
                                        : 'bg-slate-800/90 hover:bg-slate-700 text-slate-300 border-slate-700'">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span>Solo Basket (No Assist)</span>
                            </button>

                            <button type="button"
                                    @click="closeAssistModal()"
                                    class="py-2 px-3 rounded-xl font-mono text-xs sm:text-sm font-bold bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white border border-slate-700 transition">
                                Cancel
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- LINEUP & CHECK-IN MODAL (APPEARS INSIDE CENTER TIER WINDOW) -->
        <template x-if="showLineupModal">
            <div class="w-full h-full bg-slate-900 border-2 border-indigo-500/80 rounded-2xl p-2 sm:p-2.5 shadow-2xl flex flex-col justify-between overflow-hidden select-none relative animate-in fade-in duration-100"
                 style="box-shadow: 0 8px 30px -4px rgba(99, 102, 241, 0.4);">
                
                <!-- Header (Shrink-0) -->
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-800 shrink-0">
                    <div class="flex items-center space-x-2 sm:space-x-2.5 min-w-0">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-indigo-600/30 border border-indigo-500/50 flex items-center justify-center text-indigo-400 shrink-0 shadow-inner">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="text-xs sm:text-sm font-black uppercase text-white tracking-wide truncate">Substitutions: Scorer's Table Line-Up</h2>
                                <span class="px-2 py-0.5 rounded-lg text-[10px] sm:text-xs font-mono font-black tracking-wide shrink-0"
                                      :class="pendingSubCount() > 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/50 animate-pulse' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                                      x-text="pendingSubCount() + ' Lined Up'">
                                </span>
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-slate-400 truncate hidden sm:block">
                                Tap bench players at table to line up. Press <strong class="text-emerald-400 font-black">SEND IN NOW (↵ Enter)</strong> when waved into the game.
                            </p>
                        </div>
                    </div>

                    <button @click="showLineupModal = false" 
                            class="p-1 sm:p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white hover:bg-rose-900/80 border border-slate-700 hover:border-rose-500 transition shadow shrink-0" 
                            title="Close (Esc)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Dual Team Bench Grids Side-by-Side (Adaptive to Court Flip) -->
                <div class="grid grid-cols-2 gap-2 sm:gap-3 flex-1 min-h-0 overflow-hidden my-1">
                    
                    <!-- HOME BENCH -->
                    <div class="flex flex-col bg-slate-950/80 border-2 rounded-xl p-2 transition-all min-h-0 overflow-hidden"
                         :class="isFlipped ? 'order-2' : 'order-1'"
                         :style="{ borderColor: homeTeamColor + '60' }">
                        <div class="flex items-center justify-between pb-1 border-b shrink-0" :style="{ borderColor: homeTeamColor + '30' }">
                            <span class="text-xs sm:text-sm font-black uppercase truncate flex items-center gap-1.5" :style="{ color: homeTeamColor }">
                                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: homeTeamColor }"></span>
                                <span x-text="homeTeamName + ' (Bench)'"></span>
                            </span>
                            <span class="text-[10px] sm:text-xs font-mono font-bold px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-300" x-text="homeBench.length + ' Avail'"></span>
                        </div>

                        <div class="flex-1 min-h-0 overflow-y-auto pr-1 mt-1">
                            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-1.5 sm:gap-2">
                                <template x-for="p in homeBench" :key="p.id || p.jersey_number">
                                    <button @click="togglePendingSub('home', p)" 
                                            type="button"
                                            class="min-h-[75px] sm:min-h-[85px] w-full rounded-xl border-2 p-1.5 flex flex-col justify-between items-center text-center touch-active transition-all group select-none shadow-md relative overflow-hidden"
                                            :class="isPlayerPending('home', p.jersey_number) 
                                                ? 'text-white border-white ring-2 ring-emerald-400 shadow-xl scale-[1.02]' 
                                                : 'bg-slate-900/90 text-slate-200 border-slate-800 hover:border-slate-600 hover:bg-slate-850'"
                                            :style="isPlayerPending('home', p.jersey_number) ? { backgroundColor: homeTeamColor } : {}">
                                        
                                        <!-- Top Row: Position & Fouls -->
                                        <div class="w-full flex items-center justify-between text-[9px] font-mono leading-tight">
                                            <span class="font-bold uppercase tracking-wider" :class="isPlayerPending('home', p.jersey_number) ? 'text-white/90' : 'text-slate-400'" x-text="p.position || 'BENCH'"></span>
                                            <span class="px-1 rounded text-[8px] font-bold"
                                                  :class="getPlayerStat('home', p.jersey_number).total_fouls >= {{ $game->player_foul_limit }} ? 'bg-rose-600 text-white animate-pulse' : (isPlayerPending('home', p.jersey_number) ? 'bg-black/30 text-white' : 'bg-slate-800 text-slate-400')"
                                                  x-text="getPlayerStat('home', p.jersey_number).total_fouls + 'F'"></span>
                                        </div>

                                        <!-- Big Jersey Number -->
                                        <div class="font-mono text-2xl sm:text-3xl lg:text-4xl font-black leading-none tracking-tight my-0.5 group-hover:scale-105 transition-transform"
                                             :class="isPlayerPending('home', p.jersey_number) ? 'text-amber-300 drop-shadow' : 'text-white'"
                                             x-text="'#' + p.jersey_number"></div>

                                        <!-- Player Name -->
                                        <div class="w-full text-[11px] sm:text-xs font-black truncate leading-tight"
                                             :class="isPlayerPending('home', p.jersey_number) ? 'text-white' : 'text-slate-200'"
                                             x-text="p.player_name || ('#' + p.jersey_number)"></div>

                                        <!-- Bottom Status Indicator -->
                                        <div class="w-full pt-0.5 mt-0.5 border-t flex items-center justify-center text-[8px] sm:text-[9px] font-mono font-black uppercase tracking-wider"
                                             :class="isPlayerPending('home', p.jersey_number) ? 'border-white/30 text-white' : 'border-slate-800 text-slate-400 group-hover:text-emerald-400'">
                                            <span x-text="isPlayerPending('home', p.jersey_number) ? '✓ LINED UP (IN)' : '+ TAP TO ADD'"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <div x-show="homeBench.length === 0" class="text-xs text-slate-500 text-center py-8 font-bold">
                                No bench players available.
                            </div>
                        </div>
                    </div>

                    <!-- AWAY BENCH -->
                    <div class="flex flex-col bg-slate-950/80 border-2 rounded-xl p-2 transition-all min-h-0 overflow-hidden"
                         :class="isFlipped ? 'order-1' : 'order-2'"
                         :style="{ borderColor: awayTeamColor + '60' }">
                        <div class="flex items-center justify-between pb-1 border-b shrink-0" :style="{ borderColor: awayTeamColor + '30' }">
                            <span class="text-xs sm:text-sm font-black uppercase truncate flex items-center gap-1.5" :style="{ color: awayTeamColor }">
                                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: awayTeamColor }"></span>
                                <span x-text="awayTeamName + ' (Bench)'"></span>
                            </span>
                            <span class="text-[10px] sm:text-xs font-mono font-bold px-1.5 py-0.2 rounded bg-slate-900 border border-slate-800 text-slate-300" x-text="awayBench.length + ' Avail'"></span>
                        </div>

                        <div class="flex-1 min-h-0 overflow-y-auto pr-1 mt-1">
                            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-1.5 sm:gap-2">
                                <template x-for="p in awayBench" :key="p.id || p.jersey_number">
                                    <button @click="togglePendingSub('away', p)" 
                                            type="button"
                                            class="min-h-[75px] sm:min-h-[85px] w-full rounded-xl border-2 p-1.5 flex flex-col justify-between items-center text-center touch-active transition-all group select-none shadow-md relative overflow-hidden"
                                            :class="isPlayerPending('away', p.jersey_number) 
                                                ? 'text-white border-white ring-2 ring-emerald-400 shadow-xl scale-[1.02]' 
                                                : 'bg-slate-900/90 text-slate-200 border-slate-800 hover:border-slate-600 hover:bg-slate-850'"
                                            :style="isPlayerPending('away', p.jersey_number) ? { backgroundColor: awayTeamColor } : {}">
                                        
                                        <!-- Top Row: Position & Fouls -->
                                        <div class="w-full flex items-center justify-between text-[9px] font-mono leading-tight">
                                            <span class="font-bold uppercase tracking-wider" :class="isPlayerPending('away', p.jersey_number) ? 'text-white/90' : 'text-slate-400'" x-text="p.position || 'BENCH'"></span>
                                            <span class="px-1 rounded text-[8px] font-bold"
                                                  :class="getPlayerStat('away', p.jersey_number).total_fouls >= {{ $game->player_foul_limit }} ? 'bg-rose-600 text-white animate-pulse' : (isPlayerPending('away', p.jersey_number) ? 'bg-black/30 text-white' : 'bg-slate-800 text-slate-400')"
                                                  x-text="getPlayerStat('away', p.jersey_number).total_fouls + 'F'"></span>
                                        </div>

                                        <!-- Big Jersey Number -->
                                        <div class="font-mono text-2xl sm:text-3xl lg:text-4xl font-black leading-none tracking-tight my-0.5 group-hover:scale-105 transition-transform"
                                             :class="isPlayerPending('away', p.jersey_number) ? 'text-amber-300 drop-shadow' : 'text-white'"
                                             x-text="'#' + p.jersey_number"></div>

                                        <!-- Player Name -->
                                        <div class="w-full text-[11px] sm:text-xs font-black truncate leading-tight"
                                             :class="isPlayerPending('away', p.jersey_number) ? 'text-white' : 'text-slate-200'"
                                             x-text="p.player_name || ('#' + p.jersey_number)"></div>

                                        <!-- Bottom Status Indicator -->
                                        <div class="w-full pt-0.5 mt-0.5 border-t flex items-center justify-center text-[8px] sm:text-[9px] font-mono font-black uppercase tracking-wider"
                                             :class="isPlayerPending('away', p.jersey_number) ? 'border-white/30 text-white' : 'border-slate-800 text-slate-400 group-hover:text-emerald-400'">
                                            <span x-text="isPlayerPending('away', p.jersey_number) ? '✓ LINED UP (IN)' : '+ TAP TO ADD'"></span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                            <div x-show="awayBench.length === 0" class="text-xs text-slate-500 text-center py-8 font-bold">
                                No bench players available.
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Controls (Shrink-0) -->
                <div class="pt-1.5 border-t border-slate-800 flex items-center justify-between shrink-0">
                    <button x-show="pendingSubCount() > 0" 
                            @click="clearPendingSubs()" 
                            class="h-9 sm:h-10 px-3 sm:px-4 rounded-xl bg-slate-800 hover:bg-rose-950 border border-slate-700 hover:border-rose-500 text-slate-300 hover:text-white font-bold text-xs transition flex items-center gap-1.5 touch-active">
                        <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <span>Clear Queue</span>
                        <span class="px-1.5 py-0.2 rounded bg-slate-950 border border-slate-600 text-[8px] font-mono text-amber-300 font-bold">C</span>
                    </button>
                    <div x-show="pendingSubCount() === 0"></div>

                    <div class="flex items-center space-x-2">
                        <button @click="showLineupModal = false" class="h-9 sm:h-10 px-3 sm:px-4 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 hover:text-white font-bold text-xs transition flex items-center gap-1.5 touch-active">
                            <span>Done (Keep Lined Up)</span>
                            <span class="px-1.5 py-0.2 rounded bg-slate-950 border border-slate-600 text-[8px] font-mono text-slate-300">Esc</span>
                        </button>
                        <button x-show="pendingSubCount() > 0" 
                                @click="openExecuteSubModal()" 
                                class="h-9 sm:h-10 px-4 sm:px-6 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-[0.98] text-white font-black text-xs sm:text-sm shadow-lg shadow-emerald-950/80 ring-2 ring-emerald-400 flex items-center gap-2 transition touch-active">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                            <span>Send In Now</span>
                            <span class="px-1.5 py-0.2 rounded-md bg-emerald-950/80 border border-emerald-400 text-amber-300 font-mono text-xs" x-text="pendingSubCount()"></span>
                            <span class="px-1.5 py-0.2 rounded bg-emerald-950 border border-emerald-400 text-[9px] font-mono text-amber-300 font-bold">↵ Enter</span>
                        </button>
                    </div>
                </div>

            </div>
        </template>

        <!-- EXECUTE SUBSTITUTIONS: ASSIGN OUTGOING (APPEARS INSIDE CENTER TIER WINDOW) -->
        <template x-if="showExecuteSubModal">
            <div class="w-full h-full bg-slate-900 border-2 border-emerald-500/80 rounded-2xl p-2 sm:p-2.5 shadow-2xl flex flex-col justify-between overflow-hidden select-none relative animate-in fade-in duration-100"
                 style="box-shadow: 0 8px 30px -4px rgba(16, 185, 129, 0.4);">
                
                <!-- Header (Shrink-0) -->
                <div class="flex items-center justify-between pb-1.5 border-b border-slate-800 shrink-0">
                    <div class="flex items-center space-x-2 sm:space-x-2.5 min-w-0">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-600/30 border border-emerald-500/50 flex items-center justify-center text-emerald-400 shrink-0 shadow-inner">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h2 class="text-xs sm:text-sm font-black uppercase text-white tracking-wide truncate">Assign Outgoing Players</h2>
                                <span class="px-2 py-0.5 rounded-lg text-[10px] sm:text-xs font-mono font-black tracking-wide bg-emerald-500/20 text-emerald-300 border border-emerald-500/50 shrink-0"
                                      x-text="pendingSubCount() + ' Pending Sub(s)'">
                                </span>
                            </div>
                            <p class="text-[10px] sm:text-[11px] text-slate-400 truncate hidden sm:block">
                                Tap on-court players coming <strong class="text-rose-400 font-black">OUT</strong> to match incoming substitutes, then confirm.
                            </p>
                        </div>
                    </div>

                    <button @click="showExecuteSubModal = false" 
                            class="p-1 sm:p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white hover:bg-rose-900/80 border border-slate-700 hover:border-rose-500 transition shadow shrink-0" 
                            title="Close (Esc)">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Dual Team Court Assign Grids Side-by-Side (Adaptive to Court Flip) -->
                <div class="grid grid-cols-2 gap-2 sm:gap-3 flex-1 min-h-0 overflow-hidden my-1">
                    
                    <!-- HOME TEAM SUBS -->
                    <div class="flex flex-col bg-slate-950/80 border-2 rounded-xl p-2 space-y-1.5 transition-all min-h-0 overflow-hidden"
                         :class="isFlipped ? 'order-2' : 'order-1'"
                         :style="{ borderColor: homeTeamColor + '60' }">
                        <div class="flex items-center justify-between pb-1 border-b shrink-0" :style="{ borderColor: homeTeamColor + '30' }">
                            <span class="text-xs sm:text-sm font-black uppercase truncate flex items-center gap-1.5" :style="{ color: homeTeamColor }">
                                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: homeTeamColor }"></span>
                                <span x-text="homeTeamName"></span>
                            </span>
                            <span class="text-[10px] sm:text-xs font-mono font-black px-2 py-0.2 rounded-lg transition-all"
                                  :class="(selectedOutSubs.home?.length || 0) === (pendingSubs.home?.length || 0) && (pendingSubs.home?.length || 0) > 0 ? 'bg-emerald-500/25 text-emerald-300 border border-emerald-400 shadow ring-1 ring-emerald-400/40' : 'bg-slate-900 text-slate-400 border border-slate-800'"
                                  x-text="(selectedOutSubs.home?.length || 0) + ' / ' + (pendingSubs.home?.length || 0) + ' OUT Selected'">
                            </span>
                        </div>

                        <template x-if="!pendingSubs.home || pendingSubs.home.length === 0">
                            <div class="flex-1 flex flex-col items-center justify-center text-center p-4 space-y-1">
                                <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-slate-400 font-bold text-xs" x-text="'No pending substitutes for ' + homeTeamName"></p>
                            </div>
                        </template>

                        <div class="space-y-1.5 flex-1 flex flex-col min-h-0 overflow-hidden" x-show="pendingSubs.home && pendingSubs.home.length > 0">
                            <!-- Incoming Players Pill List -->
                            <div class="p-1.5 bg-slate-900/90 border rounded-xl shrink-0" :style="{ borderColor: homeTeamColor + '50' }">
                                <span class="text-[10px] font-black uppercase tracking-wider block text-emerald-400 flex items-center gap-1 mb-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>Incoming (Entering Court):</span>
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="inP in pendingSubs.home" :key="inP.id || inP.jersey_number">
                                        <div class="inline-flex items-center space-x-1.5 px-2 py-0.5 rounded-lg border text-xs font-mono font-bold shadow"
                                             :style="{ backgroundColor: homeTeamColor + '30', borderColor: homeTeamColor + '80', color: '#ffffff' }">
                                            <span class="px-1 rounded bg-emerald-500 text-slate-950 font-black text-[9px]">IN</span>
                                            <span class="font-black text-amber-300" x-text="'#' + inP.jersey_number"></span>
                                            <span class="truncate max-w-[100px] text-[11px]" x-text="inP.player_name"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- On-Court Players Selection Grid -->
                            <div class="flex-1 flex flex-col min-h-0 space-y-1">
                                <div class="flex items-center justify-between text-[10px] font-black text-slate-300 uppercase tracking-wider shrink-0 px-0.5">
                                    <span>Tap Players Coming OUT:</span>
                                    <span class="font-mono text-[10px] text-slate-400" x-text="'Need ' + ((pendingSubs.home?.length || 0) - (selectedOutSubs.home?.length || 0)) + ' more'"></span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 flex-1 min-h-0 overflow-y-auto pr-1">
                                    <template x-for="courtP in homeCourt" :key="courtP.id || courtP.jersey_number">
                                        <button @click="toggleSelectedOut('home', courtP.jersey_number)"
                                                type="button"
                                                class="w-full min-h-[75px] sm:min-h-[85px] p-1.5 rounded-xl border-2 text-center flex flex-col justify-between items-center transition-all touch-active shadow-md group relative overflow-hidden"
                                                :class="isPlayerSelectedOut('home', courtP.jersey_number)
                                                    ? 'bg-rose-600 text-white border-white ring-2 ring-rose-400 shadow-xl scale-[1.02]'
                                                    : 'bg-slate-900/90 text-slate-200 border-slate-800 hover:border-slate-600 hover:bg-slate-850'">
                                            
                                            <!-- Top: Position & Fouls -->
                                            <div class="w-full flex items-center justify-between text-[9px] font-mono leading-tight">
                                                <span class="font-bold uppercase tracking-wider" :class="isPlayerSelectedOut('home', courtP.jersey_number) ? 'text-white/90' : 'text-slate-400'" x-text="courtP.position || 'COURT'"></span>
                                                <span class="px-1 rounded text-[8px] font-bold"
                                                      :class="getPlayerStat('home', courtP.jersey_number).total_fouls >= {{ $game->player_foul_limit }} ? 'bg-rose-950 text-rose-300 border border-rose-500' : (isPlayerSelectedOut('home', courtP.jersey_number) ? 'bg-black/30 text-white' : 'bg-slate-800 text-slate-400')"
                                                      x-text="getPlayerStat('home', courtP.jersey_number).total_fouls + 'F'"></span>
                                            </div>

                                            <!-- Big Jersey -->
                                            <div class="font-mono text-2xl sm:text-3xl lg:text-4xl font-black leading-none tracking-tight my-0.5 group-hover:scale-105 transition-transform"
                                                 :class="isPlayerSelectedOut('home', courtP.jersey_number) ? 'text-white drop-shadow' : 'text-white'"
                                                 x-text="'#' + courtP.jersey_number"></div>

                                            <!-- Name -->
                                            <div class="w-full text-[11px] sm:text-xs font-black truncate leading-tight"
                                                 :class="isPlayerSelectedOut('home', courtP.jersey_number) ? 'text-white' : 'text-slate-200'"
                                                 x-text="courtP.player_name || ('#' + courtP.jersey_number)"></div>

                                            <!-- Bottom indicator -->
                                            <div class="w-full pt-0.5 mt-0.5 border-t flex items-center justify-center text-[8px] sm:text-[9px] font-mono font-black uppercase tracking-wider"
                                                 :class="isPlayerSelectedOut('home', courtP.jersey_number) ? 'border-white/40 text-white' : 'border-slate-800 text-slate-400 group-hover:text-rose-400'">
                                                <span x-text="isPlayerSelectedOut('home', courtP.jersey_number) ? '✕ OUT (LEAVING)' : 'TAP TO SUB OUT'"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AWAY TEAM SUBS -->
                    <div class="flex flex-col bg-slate-950/80 border-2 rounded-xl p-2 space-y-1.5 transition-all min-h-0 overflow-hidden"
                         :class="isFlipped ? 'order-1' : 'order-2'"
                         :style="{ borderColor: awayTeamColor + '60' }">
                        <div class="flex items-center justify-between pb-1 border-b shrink-0" :style="{ borderColor: awayTeamColor + '30' }">
                            <span class="text-xs sm:text-sm font-black uppercase truncate flex items-center gap-1.5" :style="{ color: awayTeamColor }">
                                <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: awayTeamColor }"></span>
                                <span x-text="awayTeamName"></span>
                            </span>
                            <span class="text-[10px] sm:text-xs font-mono font-black px-2 py-0.2 rounded-lg transition-all"
                                  :class="(selectedOutSubs.away?.length || 0) === (pendingSubs.away?.length || 0) && (pendingSubs.away?.length || 0) > 0 ? 'bg-emerald-500/25 text-emerald-300 border border-emerald-400 shadow ring-1 ring-emerald-400/40' : 'bg-slate-900 text-slate-400 border border-slate-800'"
                                  x-text="(selectedOutSubs.away?.length || 0) + ' / ' + (pendingSubs.away?.length || 0) + ' OUT Selected'">
                            </span>
                        </div>

                        <template x-if="!pendingSubs.away || pendingSubs.away.length === 0">
                            <div class="flex-1 flex flex-col items-center justify-center text-center p-4 space-y-1">
                                <svg class="w-8 h-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p class="text-slate-400 font-bold text-xs" x-text="'No pending substitutes for ' + awayTeamName"></p>
                            </div>
                        </template>

                        <div class="space-y-1.5 flex-1 flex flex-col min-h-0 overflow-hidden" x-show="pendingSubs.away && pendingSubs.away.length > 0">
                            <!-- Incoming Players Pill List -->
                            <div class="p-1.5 bg-slate-900/90 border rounded-xl shrink-0" :style="{ borderColor: awayTeamColor + '50' }">
                                <span class="text-[10px] font-black uppercase tracking-wider block text-emerald-400 flex items-center gap-1 mb-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>Incoming (Entering Court):</span>
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="inP in pendingSubs.away" :key="inP.id || inP.jersey_number">
                                        <div class="inline-flex items-center space-x-1.5 px-2 py-0.5 rounded-lg border text-xs font-mono font-bold shadow"
                                             :style="{ backgroundColor: awayTeamColor + '30', borderColor: awayTeamColor + '80', color: '#ffffff' }">
                                            <span class="px-1 rounded bg-emerald-500 text-slate-950 font-black text-[9px]">IN</span>
                                            <span class="font-black text-amber-300" x-text="'#' + inP.jersey_number"></span>
                                            <span class="truncate max-w-[100px] text-[11px]" x-text="inP.player_name"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- On-Court Players Selection Grid -->
                            <div class="flex-1 flex flex-col min-h-0 space-y-1">
                                <div class="flex items-center justify-between text-[10px] font-black text-slate-300 uppercase tracking-wider shrink-0 px-0.5">
                                    <span>Tap Players Coming OUT:</span>
                                    <span class="font-mono text-[10px] text-slate-400" x-text="'Need ' + ((pendingSubs.away?.length || 0) - (selectedOutSubs.away?.length || 0)) + ' more'"></span>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 flex-1 min-h-0 overflow-y-auto pr-1">
                                    <template x-for="courtP in awayCourt" :key="courtP.id || courtP.jersey_number">
                                        <button @click="toggleSelectedOut('away', courtP.jersey_number)"
                                                type="button"
                                                class="w-full min-h-[75px] sm:min-h-[85px] p-1.5 rounded-xl border-2 text-center flex flex-col justify-between items-center transition-all touch-active shadow-md group relative overflow-hidden"
                                                :class="isPlayerSelectedOut('away', courtP.jersey_number)
                                                    ? 'bg-rose-600 text-white border-white ring-2 ring-rose-400 shadow-xl scale-[1.02]'
                                                    : 'bg-slate-900/90 text-slate-200 border-slate-800 hover:border-slate-600 hover:bg-slate-850'">
                                            
                                            <!-- Top: Position & Fouls -->
                                            <div class="w-full flex items-center justify-between text-[9px] font-mono leading-tight">
                                                <span class="font-bold uppercase tracking-wider" :class="isPlayerSelectedOut('away', courtP.jersey_number) ? 'text-white/90' : 'text-slate-400'" x-text="courtP.position || 'COURT'"></span>
                                                <span class="px-1 rounded text-[8px] font-bold"
                                                      :class="getPlayerStat('away', courtP.jersey_number).total_fouls >= {{ $game->player_foul_limit }} ? 'bg-rose-950 text-rose-300 border border-rose-500' : (isPlayerSelectedOut('away', courtP.jersey_number) ? 'bg-black/30 text-white' : 'bg-slate-800 text-slate-400')"
                                                      x-text="getPlayerStat('away', courtP.jersey_number).total_fouls + 'F'"></span>
                                            </div>

                                            <!-- Big Jersey -->
                                            <div class="font-mono text-2xl sm:text-3xl lg:text-4xl font-black leading-none tracking-tight my-0.5 group-hover:scale-105 transition-transform"
                                                 :class="isPlayerSelectedOut('away', courtP.jersey_number) ? 'text-white drop-shadow' : 'text-white'"
                                                 x-text="'#' + courtP.jersey_number"></div>

                                            <!-- Name -->
                                            <div class="w-full text-[11px] sm:text-xs font-black truncate leading-tight"
                                                 :class="isPlayerSelectedOut('away', courtP.jersey_number) ? 'text-white' : 'text-slate-200'"
                                                 x-text="courtP.player_name || ('#' + courtP.jersey_number)"></div>

                                            <!-- Bottom indicator -->
                                            <div class="w-full pt-0.5 mt-0.5 border-t flex items-center justify-center text-[8px] sm:text-[9px] font-mono font-black uppercase tracking-wider"
                                                 :class="isPlayerSelectedOut('away', courtP.jersey_number) ? 'border-white/40 text-white' : 'border-slate-800 text-slate-400 group-hover:text-rose-400'">
                                                <span x-text="isPlayerSelectedOut('away', courtP.jersey_number) ? '✕ OUT (LEAVING)' : 'TAP TO SUB OUT'"></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer Controls (Shrink-0) -->
                <div class="pt-1.5 border-t border-slate-800 flex items-center justify-between shrink-0">
                    <button @click="openLineupModal()" class="h-9 sm:h-10 px-3 sm:px-4 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 hover:text-white font-bold text-xs transition flex items-center gap-1.5 touch-active">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        <span>Edit Lineup Table</span>
                        <span class="px-1.5 py-0.2 rounded bg-slate-950 border border-slate-600 text-[8px] font-mono text-amber-300 font-bold">B</span>
                    </button>

                    <div class="flex items-center space-x-2">
                        <button @click="showExecuteSubModal = false" class="h-9 sm:h-10 px-3 sm:px-4 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 hover:text-white font-bold text-xs transition flex items-center gap-1.5 touch-active">
                            <span>Cancel (Keep Waiting)</span>
                            <span class="px-1.5 py-0.2 rounded bg-slate-950 border border-slate-600 text-[8px] font-mono text-slate-300">Esc</span>
                        </button>
                        <button @click="executeAllPendingSubs()" 
                                :disabled="!canExecutePendingSubs()"
                                class="h-9 sm:h-10 px-4 sm:px-6 rounded-xl font-black text-xs sm:text-sm shadow-lg transition flex items-center gap-2 touch-active"
                                :class="canExecutePendingSubs() 
                                    ? 'bg-emerald-600 hover:bg-emerald-500 text-white ring-2 ring-emerald-400/80 shadow-emerald-950/80 animate-pulse hover:scale-[1.02] cursor-pointer' 
                                    : 'bg-slate-800 border border-slate-700 text-slate-500 opacity-60 cursor-not-allowed'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>Confirm Substitutions</span>
                            <span class="px-1.5 py-0.2 rounded bg-emerald-950 border border-emerald-400 text-[9px] font-mono text-amber-300 font-bold">↵ Enter</span>
                        </button>
                    </div>
                </div>

            </div>
        </template>

    </div>

    <!-- 3. BOTTOM TIER: GAME-LEVEL ACTIONS DOCK (Single Layer, Even Horizontal Distribution) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-2 shrink-0 shadow-xl mt-auto">
        <div class="grid grid-cols-6 gap-1.5 sm:gap-2 text-xs font-bold items-stretch">
            
            <!-- TOGGLE POSSESSION ARROW -->
            <button @click="togglePossession()" 
                    class="h-full min-h-[48px] py-1.5 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-700 text-amber-300 flex flex-col items-center justify-center shadow transition min-w-0"
                    title="Toggle Possession (Hotkey: P)">
                <div class="flex items-center gap-1 sm:gap-1.5 max-w-full truncate">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider truncate">FLIP POSS</span>
                    <span class="px-1.5 py-0.2 rounded bg-slate-950/80 border border-slate-600 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300 shrink-0">P</span>
                </div>
                <span class="text-[9px] sm:text-[10px] font-mono text-white mt-0.5 truncate max-w-full" x-text="possession.toUpperCase() + ' ARROW'"></span>
            </button>

            <!-- FLIP COURT SIDES -->
            <button @click="toggleFlipCourt()" 
                    class="h-full min-h-[48px] py-1.5 px-2 rounded-xl bg-slate-800 hover:bg-slate-700 touch-active border border-slate-700 text-cyan-300 flex flex-col items-center justify-center shadow transition min-w-0"
                    title="Flip Court Sides (Hotkey: \ or Shift+X)">
                <div class="flex items-center gap-1 sm:gap-1.5 max-w-full truncate">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider truncate">FLIP SIDES</span>
                    <span class="px-1.5 py-0.2 rounded bg-slate-950/80 border border-slate-600 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300 shrink-0">\</span>
                </div>
                <span class="text-[9px] sm:text-[10px] font-mono text-slate-300 mt-0.5 truncate max-w-full" x-text="isFlipped ? 'Away (L) / Home (R)' : 'Home (L) / Away (R)'"></span>
            </button>

            <!-- ADVANCE PERIOD (MAX 6) -->
            <button @click="nextPeriodFast()" 
                    :disabled="currentPeriod >= 6"
                    class="h-full min-h-[48px] py-1.5 px-2 rounded-xl touch-active text-white flex flex-col items-center justify-center transition min-w-0"
                    :class="currentPeriod >= 6 ? 'bg-slate-800 text-slate-500 cursor-not-allowed opacity-60 border border-slate-700' : 'bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/30'"
                    title="Advance to Next Period (Hotkey: N)">
                <div class="flex items-center gap-1 sm:gap-1.5 max-w-full truncate">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider truncate">+ NEXT PERIOD</span>
                    <span class="px-1.5 py-0.2 rounded bg-emerald-950/80 border border-emerald-400/50 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300 shrink-0" x-show="currentPeriod < 6">N</span>
                </div>
                <span class="text-[9px] sm:text-[10px] font-mono mt-0.5 truncate max-w-full" :class="currentPeriod >= 6 ? 'text-slate-400' : 'text-emerald-100'" x-text="currentPeriod >= 6 ? 'Max (Period 6)' : ((currentPeriod + 1) <= 4 ? 'Period ' + (currentPeriod + 1) : 'OT' + (currentPeriod + 1 - 4))"></span>
            </button>

            <!-- SUBSTITUTION DOCK BUTTON (LINE UP & SEND IN) - SPANS 2 COLUMNS FOR PROMINENCE -->
            <div class="col-span-2 h-full min-w-0 flex">
                <!-- State A: 0 pending subs -> Large Prominent SUBSTITUTE Button -->
                <button x-show="pendingSubCount() === 0"
                        @click="openLineupModal()" 
                        class="w-full h-full min-h-[48px] py-2 px-3 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 touch-active text-white flex flex-col items-center justify-center shadow-lg shadow-indigo-600/40 border border-indigo-400/50 transition min-w-0 group"
                        title="Substitutions (Hotkey: S)">
                    <div class="flex items-center gap-2 max-w-full truncate">
                        <svg class="w-4 h-4 text-indigo-200 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span class="text-xs sm:text-sm font-black uppercase tracking-wider truncate">SUBSTITUTE PLAYERS</span>
                        <span class="px-2 py-0.5 rounded bg-indigo-950 border border-indigo-300/60 text-[9px] sm:text-[10px] font-mono font-bold text-amber-300 shrink-0 shadow">S</span>
                    </div>
                    <span class="text-[9px] sm:text-[10px] text-indigo-200 font-mono mt-0.5 truncate max-w-full">Line Up Bench Check-Ins</span>
                </button>

                <!-- State B: Pending subs ready -> Split into large + LINE UP [S] and SEND IN [E] -->
                <div x-show="pendingSubCount() > 0"
                     class="w-full h-full p-1 rounded-xl bg-indigo-950/90 border-2 border-amber-400 shadow-xl shadow-indigo-950/80 grid grid-cols-2 gap-1.5 items-stretch min-w-0">
                    <!-- Left: Open Lineup to add/modify pending subs (Hotkey: S) -->
                    <button @click="openLineupModal()" 
                            class="py-1 px-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 touch-active text-white flex flex-col items-center justify-center transition border border-indigo-400/60 min-w-0"
                            title="Add more subs (Hotkey: S)">
                        <div class="flex items-center gap-1 max-w-full truncate">
                            <span class="text-[10px] sm:text-xs font-black uppercase truncate">+ LINE UP</span>
                            <span class="px-1.5 py-0.2 rounded bg-indigo-950 border border-indigo-300 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300 shrink-0">S</span>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-mono text-amber-300 font-bold truncate max-w-full" x-text="pendingSubCount() + ' Lined Up'"></span>
                    </button>

                    <!-- Right: Dedicated SEND IN Action (Hotkey: E) -->
                    <button @click.stop="openExecuteSubModal()" 
                            class="py-1 px-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 active:scale-[0.98] touch-active text-white flex flex-col items-center justify-center transition shadow-lg shadow-emerald-600/50 border border-emerald-300 ring-2 ring-emerald-400/80 animate-pulse min-w-0"
                            title="Execute Pending Subs onto Court (Hotkey: E)">
                        <div class="flex items-center gap-1 max-w-full truncate">
                            <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider text-white truncate">SEND IN</span>
                            <span class="px-1.5 py-0.2 rounded bg-emerald-950 border border-emerald-300 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300 shrink-0">E</span>
                        </div>
                        <span class="text-[8px] sm:text-[9px] font-bold text-emerald-100 uppercase tracking-tight truncate max-w-full" x-text="'Apply ' + pendingSubCount() + ' Sub' + (pendingSubCount() > 1 ? 's' : '')"></span>
                    </button>
                </div>
            </div>

            <!-- UNDO LAST PLAY -->
            <button @click="undo()" 
                    class="h-full min-h-[48px] py-1.5 px-2 rounded-xl bg-rose-950/80 hover:bg-rose-900 touch-active border border-rose-800 text-rose-300 flex flex-col items-center justify-center shadow transition min-w-0"
                    title="Undo Last Action (Hotkey: U)">
                <div class="flex items-center gap-1 sm:gap-1.5 max-w-full truncate">
                    <span class="text-[10px] sm:text-xs font-black uppercase tracking-wider truncate">UNDO LAST</span>
                    <span class="px-1.5 py-0.2 rounded bg-black/60 border border-rose-500/40 text-[8px] sm:text-[9px] font-mono font-bold text-amber-300 shrink-0">U</span>
                </div>
                <span class="text-[9px] sm:text-[10px] text-rose-400 font-mono mt-0.5 truncate max-w-full">Undo Play</span>
            </button>

        </div>
    </div>

    <!-- TIMEOUT CHARGE CONFIRMATION MODAL -->
    <div x-show="pendingTimeout" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 select-none" style="display: none;"
         @click.self="cancelPendingTimeout()">
        <div class="bg-slate-900 border-2 border-amber-500/80 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5 text-center animate-in fade-in zoom-in-95 duration-150">
            <div class="w-16 h-16 rounded-2xl bg-amber-500/20 border border-amber-500/40 text-amber-400 mx-auto flex items-center justify-center text-2xl font-black font-mono shadow-inner tracking-wider">
                T/O
            </div>
            
            <div class="space-y-1">
                <h3 class="text-xl font-black text-white uppercase tracking-wider">Charge Timeout?</h3>
                <p class="text-base font-bold text-amber-300" x-text="pendingTimeout ? (pendingTimeout.teamName + ' • ' + (pendingTimeout.timeoutTypeLabel || pendingTimeout.typeLabel || 'Timeout')) : ''"></p>
                <p class="text-xs text-slate-400 font-mono" x-text="pendingTimeout ? ('Timeouts remaining: ' + (pendingTimeout.formattedRemaining || (pendingTimeout.remFull + ' Full, ' + pendingTimeout.rem30s + ' 30s (' + (pendingTimeout.remFull + pendingTimeout.rem30s) + ' Total)'))) : ''"></p>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <button type="button"
                        @click="cancelPendingTimeout()" 
                        class="py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-sm border border-slate-700 transition flex items-center justify-center gap-2">
                    <span>Cancel</span>
                    <kbd class="px-1.5 py-0.5 rounded bg-slate-950 border border-slate-600 font-mono text-[10px] text-slate-400">Esc</kbd>
                </button>
                <button type="button"
                        @click="executePendingTimeout()" 
                        class="py-3 px-4 rounded-xl bg-amber-600 hover:bg-amber-500 active:scale-[0.98] text-white font-black text-sm border border-amber-400/80 shadow-lg shadow-amber-600/40 transition flex items-center justify-center gap-2">
                    <span>Charge</span>
                    <kbd class="px-1.5 py-0.5 rounded bg-amber-950 border border-amber-300/50 font-mono text-[10px] text-amber-200">Enter</kbd>
                </button>
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
                        <p class="text-[10px] text-slate-400">Complete event history: timeouts, rebounds, fouls, subs, scores & audit edits.</p>
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

                    <!-- Category Filter -->
                    <div class="flex items-center space-x-1">
                        <span class="text-[10px] text-slate-400 font-bold uppercase mr-1">Category:</span>
                        <select x-model="pbpFilterCategory" class="bg-slate-900 border border-slate-700 rounded-lg px-2 py-0.5 text-[10px] font-bold text-slate-200 focus:outline-none">
                            <option value="all">All Events</option>
                            <option value="scoring">Scoring (Makes/Misses)</option>
                            <option value="rebounds">Rebounds</option>
                            <option value="fouls">Fouls & Penalties</option>
                            <option value="ball_movement">Assists & Steals</option>
                            <option value="turnovers">Turnovers</option>
                            <option value="administrative">Timeouts, Subs & Adjustments</option>
                        </select>
                    </div>
                </div>

                <!-- Instant Search Input -->
                <div class="relative">
                    <input type="text" 
                           x-model="pbpSearchQuery" 
                           placeholder="Filter events by player name, jersey number (#23), action type, or notes..."
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
                            
                            <!-- Left: Period & Team Badge & Jersey -->
                            <div class="flex items-center space-x-2 shrink-0">
                                <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[9px] font-black"
                                      x-text="'Q' + (event.period || 1)">
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

                            <!-- Center: Player, Action Badge & Details -->
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
                                    <template x-if="['F', 'R', 'T', 'H'].includes(event.action_code)">
                                        <span class="px-1.5 py-0.2 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[9px] font-bold">FOUL</span>
                                    </template>
                                    <template x-if="['X', 'M', 'B'].includes(event.action_code)">
                                        <span class="px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[9px] font-bold">+PTS</span>
                                    </template>
                                    <template x-if="event.action_code === 'SCORE_ADJ'">
                                        <span class="px-1.5 py-0.2 rounded bg-cyan-500/20 text-cyan-300 border border-cyan-500/30 text-[9px] font-bold">ADJ</span>
                                    </template>
                                    <template x-if="event.assist_jersey_number">
                                        <span class="px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[9px] font-bold" x-text="'AST #' + event.assist_jersey_number"></span>
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
                    No plays matching the selected filters.
                </div>
            </div>

            <!-- Footer: Stats & Close -->
            <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs shrink-0">
                <span class="text-[11px] text-slate-400 font-mono" x-text="recentEvents.length + ' Total Events in Audit History'"></span>
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

                    <!-- Jersey & Period Grid -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Player Jersey # (or blank)</label>
                            <input type="text" 
                                   x-model="editingEvent.jersey_number" 
                                   placeholder="e.g. 23"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Points</label>
                            <input type="number" 
                                   x-model.number="editingEvent.points" 
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Period Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Period / Quarter</label>
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
                            <optgroup label="Scoring Makes & Misses">
                                <option value="X">2pt MAKE (+2)</option>
                                <option value="Z">2pt Miss (0)</option>
                                <option value="M">3pt MAKE (+3)</option>
                                <option value="N">3pt Miss (0)</option>
                                <option value="B">Free Throw MAKE (+1)</option>
                                <option value="V">Free Throw Miss (0)</option>
                            </optgroup>
                            <optgroup label="Rebounds & Defense">
                                <option value="D">Defensive Rebound</option>
                                <option value="O">Offensive Rebound</option>
                                <option value="A">Assist</option>
                                <option value="S">Steal</option>
                                <option value="K">Block</option>
                                <option value="W">Swat</option>
                            </optgroup>
                            <optgroup label="Turnovers">
                                <option value="P">Passing Turnover</option>
                                <option value="U">Fumble Turnover</option>
                                <option value="I">Violation Turnover</option>
                            </optgroup>
                            <optgroup label="Fouls">
                                <option value="F">Personal Foul</option>
                                <option value="R">Offensive Foul</option>
                                <option value="T">Technical Foul</option>
                                <option value="H">Forced Foul</option>
                            </optgroup>
                            <optgroup label="Administrative & Audit">
                                <option value="TIMEOUT">Timeout Charged</option>
                                <option value="SCORE_ADJ">Manual Score Adjustment</option>
                                <option value="SUB">Player Substitution</option>
                                <option value="PERIOD">Period Change</option>
                                <option value="NOTE">Audit Note / Official Scorer Entry</option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Custom Description / Audit Notes -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Description / Scorer Audit Notes</label>
                        <input type="text" 
                               x-model="editingEvent.description" 
                               placeholder="e.g. Foul assessed to #12 after referee conference"
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
                            <optgroup label="Scoring Makes & Misses">
                                <option value="X">2pt MAKE (+2)</option>
                                <option value="Z">2pt Miss (0)</option>
                                <option value="M">3pt MAKE (+3)</option>
                                <option value="N">3pt Miss (0)</option>
                                <option value="B">Free Throw MAKE (+1)</option>
                                <option value="V">Free Throw Miss (0)</option>
                            </optgroup>
                            <optgroup label="Rebounds & Defense">
                                <option value="D">Defensive Rebound</option>
                                <option value="O">Offensive Rebound</option>
                                <option value="A">Assist</option>
                                <option value="S">Steal</option>
                                <option value="K">Block</option>
                                <option value="W">Swat</option>
                            </optgroup>
                            <optgroup label="Turnovers">
                                <option value="P">Passing Turnover</option>
                                <option value="U">Fumble Turnover</option>
                                <option value="I">Violation Turnover</option>
                            </optgroup>
                            <optgroup label="Fouls">
                                <option value="F">Personal Foul</option>
                                <option value="R">Offensive Foul</option>
                                <option value="T">Technical Foul</option>
                                <option value="H">Forced Foul</option>
                            </optgroup>
                            <optgroup label="Administrative & Audit">
                                <option value="TIMEOUT">Timeout Charged</option>
                                <option value="SCORE_ADJ">Manual Score Adjustment</option>
                                <option value="SUB">Player Substitution</option>
                                <option value="PERIOD">Period Change</option>
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
                                   placeholder="e.g. 23 (optional)"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Points</label>
                            <input type="number" 
                                   x-model.number="newEvent.points" 
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold text-sm focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Period Selector -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Period / Quarter</label>
                        <div class="grid grid-cols-6 gap-1.5">
                            <template x-for="p in Math.min(6, Math.max(4, currentPeriod))" :key="p">
                                <button type="button" 
                                        @click="newEvent.period = p"
                                        class="py-1.5 rounded-lg font-mono font-bold text-xs border transition"
                                        :class="newEvent.period == p ? 'bg-emerald-600 text-white border-white' : 'bg-slate-950 text-slate-300 border-slate-800'">
                                    <span x-text="p <= 4 ? 'Q' + p : 'OT' + (p - 4)"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Description / Audit Notes -->
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Custom Description / Audit Notes (Optional)</label>
                        <input type="text" 
                               x-model="newEvent.description" 
                               placeholder="e.g. Timeout granted by head referee"
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
        <div wire:key="in-game-roster-modal-{{ $rosterModalTeam }}-{{ $rosterModalLineups->pluck('id')->join('-') }}"
             class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none animate-fade-in"
             @keydown.window.escape.prevent="$wire.closeRosterModal()"
             wire:keydown.escape="closeRosterModal"
             x-data="inGameRosterSpreadsheet({
                 sport: 'basketball',
                 teamSide: '{{ $rosterModalTeam }}',
                 initialPlayers: {{ Js::from($rosterModalLineups->map(function($l) {
                     return [
                         'id' => $l->id,
                         'jersey_number' => (string)$l->jersey_number,
                         'name' => $l->player_name,
                         'position' => (string)($l->position ?? ''),
                         'is_starter' => (bool)$l->is_starter,
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
                                <th class="w-24 py-2 px-3 text-left border-r border-slate-800 uppercase tracking-wider text-slate-300">
                                    A &bull; Jersey #
                                </th>
                                <th class="py-2 px-3 text-left border-r border-slate-800 uppercase tracking-wider text-slate-300">
                                    B &bull; Player Name (First & Last)
                                </th>
                                <th class="w-20 py-2 px-2 text-left border-r border-slate-800 uppercase tracking-wider text-slate-300">
                                    C &bull; Pos
                                </th>
                                <th class="w-24 py-2 px-2 text-center border-r border-slate-800 uppercase tracking-wider text-slate-300">
                                    D &bull; Starter
                                </th>
                                <th class="w-24 py-2 px-2 text-center border-r border-slate-800 uppercase tracking-wider text-slate-300">
                                    Court
                                </th>
                                <th class="w-10 py-2 px-1 text-center text-slate-600"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-200 bg-slate-950/40">
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr class="hover:bg-slate-800/40 transition group">
                                    <!-- Row Number Header Cell -->
                                    <td class="w-10 text-center py-0 px-2 bg-slate-950/70 border-r border-slate-800 text-slate-500 font-mono text-[11px] select-none font-bold" x-text="idx + 1"></td>

                                    <!-- Jersey Number Cell -->
                                    <td class="w-24 p-0 border-r border-slate-800 relative">
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

                                    <!-- Position Cell -->
                                    <td class="w-20 p-0 border-r border-slate-800 relative">
                                        <input type="text"
                                               :data-row="idx"
                                               data-field="position"
                                               data-grid="ingame-roster-grid"
                                               x-model="row.position"
                                               @keydown="handleKeydown($event, idx, 'position')"
                                               @paste="handlePaste($event, idx, 'position')"
                                               placeholder="PG"
                                               maxlength="6"
                                               class="w-full h-8 px-2 py-1 bg-transparent border-0 outline-none text-amber-300 uppercase font-mono font-semibold text-xs focus:bg-blue-950/40 focus:ring-2 focus:ring-blue-500 focus:ring-inset transition">
                                    </td>

                                    <!-- Starter Toggle -->
                                    <td class="w-24 p-0 border-r border-slate-800 text-center">
                                        <button type="button"
                                                @click="toggleStarter(idx)"
                                                class="w-full h-8 px-1 flex items-center justify-center text-[10px] font-bold uppercase transition cursor-pointer"
                                                :class="row.is_starter ? 'bg-amber-950/70 text-amber-300 hover:bg-amber-900/80 font-black' : 'text-slate-600 hover:text-slate-400'">
                                            <span x-text="row.is_starter ? '★ Starter' : 'Bench'"></span>
                                        </button>
                                    </td>

                                    <!-- Court Status Toggle -->
                                    <td class="w-24 p-0 border-r border-slate-800 text-center">
                                        <button type="button"
                                                @click="toggleCourt(idx)"
                                                class="w-full h-8 px-2 flex items-center justify-center text-[10px] font-bold uppercase transition cursor-pointer"
                                                :class="row.is_on_court ? 'bg-emerald-950/80 text-emerald-300 hover:bg-emerald-900/90' : 'text-slate-500 hover:text-slate-300'">
                                            <span x-text="row.is_on_court ? 'On Court' : 'Bench'"></span>
                                        </button>
                                    </td>

                                    <!-- Actions (Delete) -->
                                    <td class="w-10 p-0 text-center">
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
