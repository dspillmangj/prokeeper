<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" wire:poll.3000ms>
    <!-- Game Hero Scoreboard Header -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl overflow-hidden relative">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6 pb-6 border-b border-slate-800/80">
            <div class="flex items-center space-x-3">
                <span class="px-3 py-1 rounded-full bg-blue-950 border border-blue-800 text-blue-400 font-extrabold text-xs tracking-wider uppercase">
                    {{ ucfirst($game->sport) }} &bull; {{ $game->period_name }}
                </span>
                <span class="text-xs text-slate-400 font-medium">{{ $game->venue ?: 'Main Gymnasium' }}</span>
            </div>

            <!-- Scorebook link button if basketball -->
            @if ($game->sport === 'basketball')
                <a href="{{ route('public.scorebook', $game->access_code) }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition">
                    View Official NCAA Scorebook &rarr;
                </a>
            @endif
        </div>

        <!-- Main Score Strip -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
            <!-- Home Team Block -->
            <div class="md:col-span-5 flex items-center justify-between p-4 sm:p-6 rounded-2xl bg-slate-950/80 border border-slate-800">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-xs font-bold uppercase tracking-wider text-blue-400">HOME</span>
                        @if ($game->possession_arrow === 'home')
                            <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[9px] font-bold border border-amber-500/40">POSS</span>
                        @endif
                        @if ($game->sport === 'volleyball' && $game->current_server === 'home')
                            <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[9px] font-bold border border-emerald-500/40">SERVING</span>
                        @endif
                    </div>
                    <h1 class="text-xl sm:text-3xl font-black text-white mt-1.5 tracking-tight">{{ $game->home_display_name }}</h1>
                    
                    @if ($game->sport === 'basketball')
                        <div class="flex items-center space-x-3 text-xs text-slate-400 mt-2 font-mono">
                            <span>Fouls: <strong class="text-white">{{ $game->home_fouls_current_period }}</strong></span>
                            <span>Timeouts: <strong class="text-white">{{ $game->home_timeouts_remaining }}</strong></span>
                        </div>
                    @endif
                </div>

                <div class="text-right">
                    <span class="font-mono text-5xl sm:text-7xl font-black text-white tracking-tighter">{{ $game->home_score }}</span>
                </div>
            </div>

            <!-- Center Period & Match Details -->
            <div class="md:col-span-2 flex flex-col items-center justify-center text-center py-2 space-y-1">
                <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-200 font-extrabold text-xs sm:text-sm uppercase tracking-wider border border-slate-700">
                    {{ $game->period_name }}
                </span>
                <span class="text-[11px] text-slate-400 font-medium">Official Scorebook</span>
            </div>

            <!-- Away Team Block -->
            <div class="md:col-span-5 flex items-center justify-between p-4 sm:p-6 rounded-2xl bg-slate-950/80 border border-slate-800">
                <div class="text-left">
                    <span class="font-mono text-5xl sm:text-7xl font-black text-white tracking-tighter">{{ $game->away_score }}</span>
                </div>

                <div class="text-right">
                    <div class="flex items-center justify-end space-x-2">
                        @if ($game->sport === 'volleyball' && $game->current_server === 'away')
                            <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[9px] font-bold border border-emerald-500/40">SERVING</span>
                        @endif
                        @if ($game->possession_arrow === 'away')
                            <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[9px] font-bold border border-amber-500/40">POSS</span>
                        @endif
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-400">AWAY</span>
                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                    </div>
                    <h1 class="text-xl sm:text-3xl font-black text-white mt-1.5 tracking-tight">{{ $game->away_display_name }}</h1>

                    @if ($game->sport === 'basketball')
                        <div class="flex items-center justify-end space-x-3 text-xs text-slate-400 mt-2 font-mono">
                            <span>Timeouts: <strong class="text-white">{{ $game->away_timeouts_remaining }}</strong></span>
                            <span>Fouls: <strong class="text-white">{{ $game->away_fouls_current_period }}</strong></span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex space-x-2 border-b border-slate-800 pb-2">
        <button wire:click="setTab('summary')" class="px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeTab === 'summary' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
            Match Summary
        </button>
        <button wire:click="setTab('boxscore')" class="px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeTab === 'boxscore' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
            Player Box Score
        </button>
        <button wire:click="setTab('plays')" class="px-4 py-2 rounded-xl text-sm font-semibold transition {{ $activeTab === 'plays' ? 'bg-blue-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
            Live Play-by-Play ({{ $events->count() }})
        </button>
    </div>

    <!-- Tab 1: Match Summary -->
    @if ($activeTab === 'summary')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Period / Set Breakdown Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h3 class="text-sm font-bold text-white mb-4">Period Scoring Summary</h3>
                <table class="w-full text-left text-xs font-mono">
                    <thead class="text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-2">Team</th>
                            @if ($game->sport === 'basketball')
                                <th class="py-2 text-center">Q1</th>
                                <th class="py-2 text-center">Q2</th>
                                <th class="py-2 text-center">Q3</th>
                                <th class="py-2 text-center">Q4</th>
                                <th class="py-2 text-center">TOT</th>
                            @else
                                <th class="py-2 text-center">S1</th>
                                <th class="py-2 text-center">S2</th>
                                <th class="py-2 text-center">S3</th>
                                <th class="py-2 text-center">S4</th>
                                <th class="py-2 text-center">S5</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr>
                            <td class="py-2.5 font-bold font-sans text-blue-400">{{ $game->home_display_name }}</td>
                            @foreach (($game->home_period_scores ?? [0,0,0,0]) as $s)
                                <td class="py-2.5 text-center">{{ $s }}</td>
                            @endforeach
                            @if ($game->sport === 'basketball')
                                <td class="py-2.5 text-center font-bold text-white">{{ $game->home_score }}</td>
                            @endif
                        </tr>
                        <tr>
                            <td class="py-2.5 font-bold font-sans text-rose-400">{{ $game->away_display_name }}</td>
                            @foreach (($game->away_period_scores ?? [0,0,0,0]) as $s)
                                <td class="py-2.5 text-center">{{ $s }}</td>
                            @endforeach
                            @if ($game->sport === 'basketball')
                                <td class="py-2.5 text-center font-bold text-white">{{ $game->away_score }}</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Top Performers Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
                <h3 class="text-sm font-bold text-white mb-4">Top Performers</h3>
                @if ($game->sport === 'basketball')
                    <div class="space-y-3 text-xs">
                        @foreach ($homeBballStats->take(2) as $s)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800/80">
                                <div>
                                    <span class="font-bold text-slate-100">#{{ $s->jersey_number }} {{ $s->player_name }}</span>
                                    <span class="text-blue-400 text-[10px] ml-1">({{ $game->home_display_name }})</span>
                                </div>
                                <div class="font-mono font-bold text-white">{{ $s->points }} PTS &bull; {{ $s->reb }} REB &bull; {{ $s->ast }} AST</div>
                            </div>
                        @endforeach
                        @foreach ($awayBballStats->take(2) as $s)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800/80">
                                <div>
                                    <span class="font-bold text-slate-100">#{{ $s->jersey_number }} {{ $s->player_name }}</span>
                                    <span class="text-rose-400 text-[10px] ml-1">({{ $game->away_display_name }})</span>
                                </div>
                                <div class="font-mono font-bold text-white">{{ $s->points }} PTS &bull; {{ $s->reb }} REB &bull; {{ $s->ast }} AST</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="space-y-3 text-xs">
                        @foreach ($homeVbStats->take(2) as $s)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-950 border border-slate-800/80">
                                <div>
                                    <span class="font-bold text-slate-100">#{{ $s->jersey_number }} {{ $s->player_name }}</span>
                                    <span class="text-blue-400 text-[10px] ml-1">({{ $game->home_display_name }})</span>
                                </div>
                                <div class="font-mono font-bold text-white">{{ $s->kills }} KILLS &bull; {{ $s->total_blocks }} BLK &bull; {{ $s->service_aces }} ACES</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Tab 2: Full Player Box Score -->
    @if ($activeTab === 'boxscore')
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-6">
            @if ($game->sport === 'basketball')
                <!-- Home Basketball Box -->
                <div>
                    <h3 class="text-sm font-bold text-blue-400 mb-2 uppercase">{{ $game->home_display_name }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300 font-mono">
                            <thead class="uppercase bg-slate-950 text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="px-3 py-2">Player</th>
                                    <th class="px-2 py-2 text-center">PTS</th>
                                    <th class="px-2 py-2 text-center">FGM-A</th>
                                    <th class="px-2 py-2 text-center">3PM-A</th>
                                    <th class="px-2 py-2 text-center">FTM-A</th>
                                    <th class="px-2 py-2 text-center">REB</th>
                                    <th class="px-2 py-2 text-center">AST</th>
                                    <th class="px-2 py-2 text-center">STL</th>
                                    <th class="px-2 py-2 text-center">BLK</th>
                                    <th class="px-2 py-2 text-center">PF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @foreach ($homeBballStats as $s)
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-white">#{{ $s->jersey_number }} {{ $s->player_name }}</td>
                                        <td class="px-2 py-2 text-center font-bold text-white">{{ $s->points }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->fgm }}-{{ $s->fga }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->fg3m }}-{{ $s->fg3a }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->ftm }}-{{ $s->fta }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->reb }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->ast }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->stl }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->blk }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->total_fouls }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Away Basketball Box -->
                <div class="pt-4 border-t border-slate-800">
                    <h3 class="text-sm font-bold text-rose-400 mb-2 uppercase">{{ $game->away_display_name }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300 font-mono">
                            <thead class="uppercase bg-slate-950 text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="px-3 py-2">Player</th>
                                    <th class="px-2 py-2 text-center">PTS</th>
                                    <th class="px-2 py-2 text-center">FGM-A</th>
                                    <th class="px-2 py-2 text-center">3PM-A</th>
                                    <th class="px-2 py-2 text-center">FTM-A</th>
                                    <th class="px-2 py-2 text-center">REB</th>
                                    <th class="px-2 py-2 text-center">AST</th>
                                    <th class="px-2 py-2 text-center">STL</th>
                                    <th class="px-2 py-2 text-center">BLK</th>
                                    <th class="px-2 py-2 text-center">PF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @foreach ($awayBballStats as $s)
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-white">#{{ $s->jersey_number }} {{ $s->player_name }}</td>
                                        <td class="px-2 py-2 text-center font-bold text-white">{{ $s->points }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->fgm }}-{{ $s->fga }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->fg3m }}-{{ $s->fg3a }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->ftm }}-{{ $s->fta }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->reb }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->ast }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->stl }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->blk }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->total_fouls }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <!-- Volleyball Box Score -->
                <div>
                    <h3 class="text-sm font-bold text-blue-400 mb-2 uppercase">{{ $game->home_display_name }}</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300 font-mono">
                            <thead class="uppercase bg-slate-950 text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="px-3 py-2">Player</th>
                                    <th class="px-2 py-2 text-center">K</th>
                                    <th class="px-2 py-2 text-center">E</th>
                                    <th class="px-2 py-2 text-center">TA</th>
                                    <th class="px-2 py-2 text-center">HIT %</th>
                                    <th class="px-2 py-2 text-center">AST</th>
                                    <th class="px-2 py-2 text-center">ACE</th>
                                    <th class="px-2 py-2 text-center">DIG</th>
                                    <th class="px-2 py-2 text-center">BLK</th>
                                    <th class="px-2 py-2 text-center">PTS</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                @foreach ($homeVbStats as $s)
                                    <tr>
                                        <td class="px-3 py-2 font-sans font-semibold text-white">#{{ $s->jersey_number }} {{ $s->player_name }}</td>
                                        <td class="px-2 py-2 text-center font-bold text-emerald-400">{{ $s->kills }}</td>
                                        <td class="px-2 py-2 text-center text-rose-400">{{ $s->attack_errors }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->attack_attempts }}</td>
                                        <td class="px-2 py-2 text-center font-bold">{{ number_format($s->hitting_percentage, 3) }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->assists }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->service_aces }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->digs }}</td>
                                        <td class="px-2 py-2 text-center">{{ $s->total_blocks }}</td>
                                        <td class="px-2 py-2 text-center font-bold text-white">{{ $s->total_points }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Tab 3: Play-by-Play Stream -->
    @if ($activeTab === 'plays')
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-2">
            <h3 class="text-sm font-bold text-white mb-4">Chronological Match Events</h3>

            <div class="space-y-2">
                @foreach ($events as $event)
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800/80 text-xs flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] {{ $event->team_side === 'home' ? 'bg-blue-950 text-blue-400' : 'bg-rose-950 text-rose-400' }}">
                                {{ strtoupper($event->team_side) }}
                            </span>
                            <span class="font-semibold text-slate-100">{{ $event->description }}</span>
                        </div>
                        <div class="font-mono text-slate-400 text-xs font-bold">
                            {{ $event->home_score_after }} - {{ $event->away_score_after }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
