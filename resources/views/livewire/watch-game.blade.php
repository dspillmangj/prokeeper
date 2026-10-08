<div>
    @if (!$game)
        <!-- Game Access Code Prompt Screen for Watch View -->
        <div class="min-h-[85vh] flex items-center justify-center p-4">
            <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
                <div class="text-center space-y-2">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600/20 border border-blue-500/30 text-blue-400 mb-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-white">Watch Game Live</h1>
                    <p class="text-xs text-slate-400">Enter a 6-character game access code to watch live play-by-play, box scores, and scoreboard.</p>
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
                        Start Watching &rarr;
                    </button>
                </form>

                @if (isset($liveGames) && $liveGames->isNotEmpty())
                    <div class="pt-4 border-t border-slate-800/80 space-y-2.5">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 text-center">
                            Featured Live Games
                        </div>
                        <div class="space-y-2">
                            @foreach ($liveGames as $lg)
                                <a href="{{ route('public.watch', $lg->access_code) }}"
                                   class="p-2.5 rounded-xl bg-slate-950 hover:bg-slate-800/80 border border-slate-800 hover:border-slate-700 transition flex items-center justify-between group">
                                    <div class="truncate text-xs font-bold text-slate-200 group-hover:text-white">
                                        <span class="text-blue-400">{{ $lg->home_display_name }}</span>
                                        <span class="text-slate-500 text-[10px]">vs</span>
                                        <span class="text-rose-400">{{ $lg->away_display_name }}</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] font-mono font-bold text-slate-300">
                                        {{ $lg->access_code }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <!-- Live Watch Game Spectator Experience -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6" wire:poll.2000ms>
            
            <!-- Hero Scoreboard Card Header -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-7 shadow-2xl overflow-hidden relative">
                <!-- Top Meta Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5 pb-4 border-b border-slate-800/80 text-xs">
                    <div class="flex items-center space-x-2.5">
                        <span class="px-2.5 py-1 rounded-full bg-blue-950 border border-blue-800 text-blue-400 font-extrabold uppercase tracking-wider text-[11px]">
                            {{ ucfirst($game->sport) }} &bull; {{ $game->period_name }}
                        </span>
                        <span class="text-slate-400 font-medium hidden sm:inline">{{ $game->venue ?: 'Main Gymnasium' }}</span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[11px] font-bold">
                            CODE: {{ $game->access_code }}
                        </span>
                        <a href="{{ route('public.scoreboard', $game->access_code) }}" target="_blank"
                           class="px-3 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs border border-slate-700 transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Pure Scoreboard</span>
                        </a>
                        @if ($game->sport === 'basketball')
                            <a href="{{ route('public.scorebook', $game->access_code) }}" target="_blank"
                               class="px-3 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs border border-slate-700 transition hidden sm:inline-flex items-center gap-1">
                                <span>NCAA Book</span>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Matchup Score Display -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6 items-center">
                    <!-- Home Team -->
                    <div class="md:col-span-5 flex items-center justify-between p-4 sm:p-5 rounded-2xl bg-slate-950/90 border border-slate-800">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-full bg-blue-500 shadow-sm shadow-blue-500"></span>
                                <span class="text-xs font-bold uppercase tracking-wider text-blue-400">HOME</span>
                                @if ($game->sport === 'basketball' && $game->possession_arrow === 'home')
                                    <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[9px] font-bold border border-amber-500/40">POSS</span>
                                @elseif ($game->sport === 'volleyball' && $game->current_server === 'home')
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[9px] font-bold border border-emerald-500/40">SERVING</span>
                                @endif
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-white mt-1 tracking-tight">{{ $game->home_display_name }}</h2>
                            @if ($game->sport === 'basketball')
                                <div class="flex items-center space-x-3 text-xs text-slate-400 mt-1.5 font-mono">
                                    <span>Fouls: <strong class="text-white">{{ $game->home_fouls_current_period }}</strong></span>
                                    <span>TO Left: <strong class="text-amber-300">{{ $game->home_timeouts_remaining }}</strong></span>
                                </div>
                            @endif
                        </div>
                        <div class="text-right pl-3">
                            <span class="font-mono text-5xl sm:text-6xl font-black text-white tracking-tighter">{{ $game->home_score }}</span>
                        </div>
                    </div>

                    <!-- Center Period / Match Status -->
                    <div class="md:col-span-2 flex flex-col items-center justify-center text-center py-1 space-y-1">
                        <span class="px-3.5 py-1 rounded-full bg-slate-800 text-white font-mono font-black text-xs sm:text-sm uppercase tracking-wider border border-slate-700 shadow">
                            {{ $game->period_name }}
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">Live Broadcast</span>
                    </div>

                    <!-- Away Team -->
                    <div class="md:col-span-5 flex items-center justify-between p-4 sm:p-5 rounded-2xl bg-slate-950/90 border border-slate-800">
                        <div class="text-left pr-3">
                            <span class="font-mono text-5xl sm:text-6xl font-black text-white tracking-tighter">{{ $game->away_score }}</span>
                        </div>
                        <div class="text-right">
                            <div class="flex items-center justify-end space-x-2">
                                @if ($game->sport === 'basketball' && $game->possession_arrow === 'away')
                                    <span class="px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 text-[9px] font-bold border border-amber-500/40">POSS</span>
                                @elseif ($game->sport === 'volleyball' && $game->current_server === 'away')
                                    <span class="px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-300 text-[9px] font-bold border border-emerald-500/40">SERVING</span>
                                @endif
                                <span class="text-xs font-bold uppercase tracking-wider text-rose-400">AWAY</span>
                                <span class="w-3 h-3 rounded-full bg-rose-500 shadow-sm shadow-rose-500"></span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black text-white mt-1 tracking-tight">{{ $game->away_display_name }}</h2>
                            @if ($game->sport === 'basketball')
                                <div class="flex items-center justify-end space-x-3 text-xs text-slate-400 mt-1.5 font-mono">
                                    <span>TO Left: <strong class="text-amber-300">{{ $game->away_timeouts_remaining }}</strong></span>
                                    <span>Fouls: <strong class="text-white">{{ $game->away_fouls_current_period }}</strong></span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs (Scoreboard, Play-by-Play, Box Score, Match Summary) -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-2.5">
                <div class="flex items-center space-x-2">
                    <button wire:click="setTab('scoreboard')"
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer flex items-center gap-2 {{ $activeTab === 'scoreboard' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Scoreboard</span>
                    </button>

                    <button wire:click="setTab('plays')"
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer flex items-center gap-2 {{ $activeTab === 'plays' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Play-by-Play</span>
                        <span class="px-1.5 py-0.2 rounded-full bg-slate-950 text-[10px] font-mono {{ $activeTab === 'plays' ? 'text-blue-200' : 'text-slate-400' }}">{{ $events->count() }}</span>
                    </button>

                    <button wire:click="setTab('boxscore')"
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer flex items-center gap-2 {{ $activeTab === 'boxscore' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span>Box Score</span>
                    </button>

                    <button wire:click="setTab('summary')"
                            type="button"
                            class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer flex items-center gap-2 {{ $activeTab === 'summary' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        <span>Match Summary</span>
                    </button>
                </div>
            </div>

            <!-- TAB 1: SCOREBOARD TAB -->
            @if ($activeTab === 'scoreboard')
                <div class="space-y-6 animate-fade-in">
                    <!-- Period Scoring Grid -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-300">Period Scoring Breakdown</h3>
                            <span class="text-[10px] font-mono text-emerald-400 font-bold">&bull; Live Sync</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-center text-xs font-mono">
                                <thead>
                                    <tr class="text-slate-400 border-b border-slate-800 text-[10px] uppercase">
                                        <th class="py-1.5 px-3 text-left font-sans font-bold">Team</th>
                                        @foreach ($periodScores as $ps)
                                            <th class="py-1.5 px-3 {{ $ps['is_current'] ? 'text-amber-300 font-bold bg-slate-950/60' : '' }}">
                                                {{ $ps['name'] }}
                                            </th>
                                        @endforeach
                                        <th class="py-1.5 px-3 text-right font-sans font-black text-white">FINAL/TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/70 font-bold">
                                    <tr>
                                        <td class="py-2.5 px-3 text-left font-sans font-black text-blue-400">
                                            {{ $game->home_display_name }}
                                        </td>
                                        @foreach ($periodScores as $ps)
                                            <td class="py-2.5 px-3 text-slate-200 {{ $ps['is_current'] ? 'bg-slate-950/60 text-white font-black' : '' }}">
                                                {{ $ps['home'] }}
                                            </td>
                                        @endforeach
                                        <td class="py-2.5 px-3 text-right text-lg font-black text-white">
                                            {{ $game->home_score }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="py-2.5 px-3 text-left font-sans font-black text-rose-400">
                                            {{ $game->away_display_name }}
                                        </td>
                                        @foreach ($periodScores as $ps)
                                            <td class="py-2.5 px-3 text-slate-200 {{ $ps['is_current'] ? 'bg-slate-950/60 text-white font-black' : '' }}">
                                                {{ $ps['away'] }}
                                            </td>
                                        @endforeach
                                        <td class="py-2.5 px-3 text-right text-lg font-black text-white">
                                            {{ $game->away_score }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Recent Highlights / Latest Plays Preview -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-300">Recent Plays Preview</h3>
                            <button wire:click="setTab('plays')" class="text-xs font-bold text-blue-400 hover:text-blue-300 transition">
                                View Full Play-by-Play &rarr;
                            </button>
                        </div>

                        <div class="space-y-2">
                            @forelse ($events->take(5) as $ev)
                                <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800/80 flex items-center justify-between gap-3 text-xs">
                                    <div class="flex items-center space-x-2.5 truncate">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $ev->team_side === 'home' ? 'bg-blue-500' : 'bg-rose-500' }}"></span>
                                        <span class="font-mono text-[11px] font-bold text-slate-400 shrink-0">
                                            P{{ $ev->period }} &bull; {{ sprintf('%d:%02d', floor(($ev->clock_seconds_remaining ?? 0) / 60), ($ev->clock_seconds_remaining ?? 0) % 60) }}
                                        </span>
                                        <span class="font-bold text-white truncate">{{ $ev->description ?: $ev->action_name }}</span>
                                    </div>
                                    <div class="font-mono text-xs font-bold text-slate-300 shrink-0">
                                        {{ $ev->home_score_after }} - {{ $ev->away_score_after }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-6 text-xs text-slate-500">
                                    No live plays recorded yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            <!-- TAB 2: PLAY-BY-PLAY TAB -->
            @if ($activeTab === 'plays')
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xl space-y-4 animate-fade-in">
                    <!-- Filter Toolbar -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-black uppercase tracking-wider text-white">Live Play-by-Play Feed</h3>
                            <p class="text-[11px] text-slate-400">Complete chronological log of match events.</p>
                        </div>

                        <div class="flex items-center space-x-1.5 text-xs">
                            <span class="text-slate-500 mr-1 text-[11px] font-bold uppercase">Filter:</span>
                            <button wire:click="setPbpFilter('all')"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $pbpFilterTeam === 'all' ? 'bg-slate-700 text-white' : 'bg-slate-950 text-slate-400 hover:text-white' }}">
                                All
                            </button>
                            <button wire:click="setPbpFilter('home')"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $pbpFilterTeam === 'home' ? 'bg-blue-600 text-white' : 'bg-slate-950 text-slate-400 hover:text-white' }}">
                                {{ $game->home_display_name }}
                            </button>
                            <button wire:click="setPbpFilter('away')"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg font-bold transition cursor-pointer {{ $pbpFilterTeam === 'away' ? 'bg-rose-600 text-white' : 'bg-slate-950 text-slate-400 hover:text-white' }}">
                                {{ $game->away_display_name }}
                            </button>
                        </div>
                    </div>

                    <!-- Event Feed Stream -->
                    <div class="space-y-2 max-h-[650px] overflow-y-auto pr-1">
                        @forelse ($events as $ev)
                            <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 hover:border-slate-700 transition flex items-center justify-between gap-3 text-xs">
                                <div class="flex items-center space-x-3 min-w-0">
                                    <span class="w-3 h-3 rounded-full shrink-0 {{ $ev->team_side === 'home' ? 'bg-blue-500' : 'bg-rose-500' }}"></span>
                                    <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 font-mono text-[10px] font-bold text-slate-400 shrink-0">
                                        P{{ $ev->period }} &bull; {{ sprintf('%d:%02d', floor(($ev->clock_seconds_remaining ?? 0) / 60), ($ev->clock_seconds_remaining ?? 0) % 60) }}
                                    </span>
                                    <span class="font-medium text-slate-100 truncate">
                                        {{ $ev->description ?: $ev->action_name }}
                                    </span>
                                </div>

                                <div class="flex items-center space-x-2 shrink-0">
                                    @if ($ev->points > 0)
                                        <span class="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-mono font-bold text-[10px]">
                                            +{{ $ev->points }}
                                        </span>
                                    @endif
                                    <span class="font-mono font-bold text-xs text-slate-300 px-2 py-0.5 rounded bg-slate-900 border border-slate-800">
                                        {{ $ev->home_score_after }} - {{ $ev->away_score_after }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 text-slate-500 text-xs">
                                No events match your filter.
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            <!-- TAB 3: BOX SCORE TAB -->
            @if ($activeTab === 'boxscore')
                <div class="space-y-6 animate-fade-in">
                    <!-- HOME TEAM BOX SCORE -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                <h3 class="text-sm font-black uppercase tracking-wider text-blue-400">{{ $game->home_display_name }} &bull; Box Score</h3>
                            </div>
                            <span class="font-mono text-lg font-black text-white">{{ $game->home_score }} PTS</span>
                        </div>

                        <div class="overflow-x-auto">
                            @if ($game->sport === 'basketball')
                                <table class="w-full text-xs font-mono">
                                    <thead>
                                        <tr class="text-slate-400 text-[10px] uppercase border-b border-slate-800 text-center">
                                            <th class="py-2 px-2 text-left font-sans">#</th>
                                            <th class="py-2 px-3 text-left font-sans">Player</th>
                                            <th class="py-2 px-2 font-black text-white">PTS</th>
                                            <th class="py-2 px-2">FGM-A</th>
                                            <th class="py-2 px-2">3PM-A</th>
                                            <th class="py-2 px-2">FTM-A</th>
                                            <th class="py-2 px-2">REB</th>
                                            <th class="py-2 px-2">AST</th>
                                            <th class="py-2 px-2">STL</th>
                                            <th class="py-2 px-2">BLK</th>
                                            <th class="py-2 px-2">TO</th>
                                            <th class="py-2 px-2">PF</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/70 text-center">
                                        @foreach ($homeBballStats as $st)
                                            <tr class="hover:bg-slate-950/40 transition">
                                                <td class="py-2 px-2 text-left font-black text-blue-400">#{{ $st->jersey_number }}</td>
                                                <td class="py-2 px-3 text-left font-sans font-bold text-white truncate max-w-[140px]">{{ $st->player_name }}</td>
                                                <td class="py-2 px-2 font-black text-white text-sm">{{ $st->points }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->field_goals_made }}-{{ $st->field_goals_attempted }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->three_pointers_made }}-{{ $st->three_pointers_attempted }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->free_throws_made }}-{{ $st->free_throws_attempted }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->rebounds_total }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->assists }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->steals }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->blocks }}</td>
                                                <td class="py-2 px-2 text-slate-400">{{ $st->turnovers }}</td>
                                                <td class="py-2 px-2 text-slate-400">{{ $st->fouls_personal }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t-2 border-slate-700 bg-slate-950/80 font-black text-center">
                                        <tr>
                                            <td colspan="2" class="py-2.5 px-3 text-left font-sans uppercase text-slate-300">Team Totals</td>
                                            <td class="py-2.5 px-2 text-white text-sm">{{ $homeBballStats->sum('points') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $homeBballStats->sum('field_goals_made') }}-{{ $homeBballStats->sum('field_goals_attempted') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $homeBballStats->sum('three_pointers_made') }}-{{ $homeBballStats->sum('three_pointers_attempted') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $homeBballStats->sum('free_throws_made') }}-{{ $homeBballStats->sum('free_throws_attempted') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $homeBballStats->sum('rebounds_total') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $homeBballStats->sum('assists') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $homeBballStats->sum('steals') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $homeBballStats->sum('blocks') }}</td>
                                            <td class="py-2.5 px-2 text-slate-300">{{ $homeBballStats->sum('turnovers') }}</td>
                                            <td class="py-2.5 px-2 text-slate-300">{{ $homeBballStats->sum('fouls_personal') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @else
                                <table class="w-full text-xs font-mono">
                                    <thead>
                                        <tr class="text-slate-400 text-[10px] uppercase border-b border-slate-800 text-center">
                                            <th class="py-2 px-2 text-left font-sans">#</th>
                                            <th class="py-2 px-3 text-left font-sans">Player</th>
                                            <th class="py-2 px-2 font-black text-white">PTS</th>
                                            <th class="py-2 px-2">Kills</th>
                                            <th class="py-2 px-2">Errors</th>
                                            <th class="py-2 px-2">Attacks</th>
                                            <th class="py-2 px-2">Assists</th>
                                            <th class="py-2 px-2">Aces</th>
                                            <th class="py-2 px-2">Digs</th>
                                            <th class="py-2 px-2">Blocks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/70 text-center">
                                        @foreach ($homeVbStats as $st)
                                            <tr class="hover:bg-slate-950/40 transition">
                                                <td class="py-2 px-2 text-left font-black text-blue-400">#{{ $st->jersey_number }}</td>
                                                <td class="py-2 px-3 text-left font-sans font-bold text-white truncate max-w-[140px]">{{ $st->player_name }}</td>
                                                <td class="py-2 px-2 font-black text-white text-sm">{{ $st->total_points }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->kills }}</td>
                                                <td class="py-2 px-2 text-slate-400">{{ $st->attack_errors }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->attack_attempts }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->assists }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->service_aces }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->digs }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->blocks_solo + $st->blocks_assisted }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>

                    <!-- AWAY TEAM BOX SCORE -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                                <h3 class="text-sm font-black uppercase tracking-wider text-rose-400">{{ $game->away_display_name }} &bull; Box Score</h3>
                            </div>
                            <span class="font-mono text-lg font-black text-white">{{ $game->away_score }} PTS</span>
                        </div>

                        <div class="overflow-x-auto">
                            @if ($game->sport === 'basketball')
                                <table class="w-full text-xs font-mono">
                                    <thead>
                                        <tr class="text-slate-400 text-[10px] uppercase border-b border-slate-800 text-center">
                                            <th class="py-2 px-2 text-left font-sans">#</th>
                                            <th class="py-2 px-3 text-left font-sans">Player</th>
                                            <th class="py-2 px-2 font-black text-white">PTS</th>
                                            <th class="py-2 px-2">FGM-A</th>
                                            <th class="py-2 px-2">3PM-A</th>
                                            <th class="py-2 px-2">FTM-A</th>
                                            <th class="py-2 px-2">REB</th>
                                            <th class="py-2 px-2">AST</th>
                                            <th class="py-2 px-2">STL</th>
                                            <th class="py-2 px-2">BLK</th>
                                            <th class="py-2 px-2">TO</th>
                                            <th class="py-2 px-2">PF</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/70 text-center">
                                        @foreach ($awayBballStats as $st)
                                            <tr class="hover:bg-slate-950/40 transition">
                                                <td class="py-2 px-2 text-left font-black text-rose-400">#{{ $st->jersey_number }}</td>
                                                <td class="py-2 px-3 text-left font-sans font-bold text-white truncate max-w-[140px]">{{ $st->player_name }}</td>
                                                <td class="py-2 px-2 font-black text-white text-sm">{{ $st->points }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->field_goals_made }}-{{ $st->field_goals_attempted }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->three_pointers_made }}-{{ $st->three_pointers_attempted }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->free_throws_made }}-{{ $st->free_throws_attempted }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->rebounds_total }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->assists }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->steals }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->blocks }}</td>
                                                <td class="py-2 px-2 text-slate-400">{{ $st->turnovers }}</td>
                                                <td class="py-2 px-2 text-slate-400">{{ $st->fouls_personal }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="border-t-2 border-slate-700 bg-slate-950/80 font-black text-center">
                                        <tr>
                                            <td colspan="2" class="py-2.5 px-3 text-left font-sans uppercase text-slate-300">Team Totals</td>
                                            <td class="py-2.5 px-2 text-white text-sm">{{ $awayBballStats->sum('points') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $awayBballStats->sum('field_goals_made') }}-{{ $awayBballStats->sum('field_goals_attempted') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $awayBballStats->sum('three_pointers_made') }}-{{ $awayBballStats->sum('three_pointers_attempted') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $awayBballStats->sum('free_throws_made') }}-{{ $awayBballStats->sum('free_throws_attempted') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $awayBballStats->sum('rebounds_total') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $awayBballStats->sum('assists') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $awayBballStats->sum('steals') }}</td>
                                            <td class="py-2.5 px-2 text-slate-200">{{ $awayBballStats->sum('blocks') }}</td>
                                            <td class="py-2.5 px-2 text-slate-300">{{ $awayBballStats->sum('turnovers') }}</td>
                                            <td class="py-2.5 px-2 text-slate-300">{{ $awayBballStats->sum('fouls_personal') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @else
                                <table class="w-full text-xs font-mono">
                                    <thead>
                                        <tr class="text-slate-400 text-[10px] uppercase border-b border-slate-800 text-center">
                                            <th class="py-2 px-2 text-left font-sans">#</th>
                                            <th class="py-2 px-3 text-left font-sans">Player</th>
                                            <th class="py-2 px-2 font-black text-white">PTS</th>
                                            <th class="py-2 px-2">Kills</th>
                                            <th class="py-2 px-2">Errors</th>
                                            <th class="py-2 px-2">Attacks</th>
                                            <th class="py-2 px-2">Assists</th>
                                            <th class="py-2 px-2">Aces</th>
                                            <th class="py-2 px-2">Digs</th>
                                            <th class="py-2 px-2">Blocks</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/70 text-center">
                                        @foreach ($awayVbStats as $st)
                                            <tr class="hover:bg-slate-950/40 transition">
                                                <td class="py-2 px-2 text-left font-black text-rose-400">#{{ $st->jersey_number }}</td>
                                                <td class="py-2 px-3 text-left font-sans font-bold text-white truncate max-w-[140px]">{{ $st->player_name }}</td>
                                                <td class="py-2 px-2 font-black text-white text-sm">{{ $st->total_points }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->kills }}</td>
                                                <td class="py-2 px-2 text-slate-400">{{ $st->attack_errors }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->attack_attempts }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->assists }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->service_aces }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->digs }}</td>
                                                <td class="py-2 px-2 text-slate-300">{{ $st->blocks_solo + $st->blocks_assisted }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- TAB 4: MATCH SUMMARY TAB -->
            @if ($activeTab === 'summary')
                <div class="space-y-6 animate-fade-in">
                    <!-- Team Head-to-Head Comparison Bars -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 shadow-xl space-y-5">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <h3 class="text-sm font-black uppercase tracking-wider text-white">Team Statistical Matchup</h3>
                            <div class="flex items-center space-x-4 text-xs font-bold font-mono">
                                <span class="text-blue-400">{{ $game->home_display_name }}</span>
                                <span class="text-slate-500">VS</span>
                                <span class="text-rose-400">{{ $game->away_display_name }}</span>
                            </div>
                        </div>

                        @if ($game->sport === 'basketball')
                            <div class="space-y-4 text-xs font-mono">
                                <!-- Field Goal % -->
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-slate-300 font-bold">
                                        <span class="text-blue-400">{{ $homeTeamStats['fg_pct'] }}% ({{ $homeTeamStats['fgm'] }}/{{ $homeTeamStats['fga'] }})</span>
                                        <span class="text-slate-400 font-sans uppercase text-[11px]">Field Goal %</span>
                                        <span class="text-rose-400">{{ $awayTeamStats['fg_pct'] }}% ({{ $awayTeamStats['fgm'] }}/{{ $awayTeamStats['fga'] }})</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-slate-950 overflow-hidden flex">
                                        <div class="bg-blue-500 transition-all" style="width: {{ $homeTeamStats['fg_pct'] }}%"></div>
                                        <div class="flex-1"></div>
                                        <div class="bg-rose-500 transition-all" style="width: {{ $awayTeamStats['fg_pct'] }}%"></div>
                                    </div>
                                </div>

                                <!-- 3-Point % -->
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-slate-300 font-bold">
                                        <span class="text-blue-400">{{ $homeTeamStats['three_pct'] }}% ({{ $homeTeamStats['three_pm'] }}/{{ $homeTeamStats['three_pa'] }})</span>
                                        <span class="text-slate-400 font-sans uppercase text-[11px]">3-Point %</span>
                                        <span class="text-rose-400">{{ $awayTeamStats['three_pct'] }}% ({{ $awayTeamStats['three_pm'] }}/{{ $awayTeamStats['three_pa'] }})</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-slate-950 overflow-hidden flex">
                                        <div class="bg-blue-500 transition-all" style="width: {{ $homeTeamStats['three_pct'] }}%"></div>
                                        <div class="flex-1"></div>
                                        <div class="bg-rose-500 transition-all" style="width: {{ $awayTeamStats['three_pct'] }}%"></div>
                                    </div>
                                </div>

                                <!-- Free Throw % -->
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-slate-300 font-bold">
                                        <span class="text-blue-400">{{ $homeTeamStats['ft_pct'] }}% ({{ $homeTeamStats['ftm'] }}/{{ $homeTeamStats['fta'] }})</span>
                                        <span class="text-slate-400 font-sans uppercase text-[11px]">Free Throw %</span>
                                        <span class="text-rose-400">{{ $awayTeamStats['ft_pct'] }}% ({{ $awayTeamStats['ftm'] }}/{{ $awayTeamStats['fta'] }})</span>
                                    </div>
                                    <div class="h-2.5 rounded-full bg-slate-950 overflow-hidden flex">
                                        <div class="bg-blue-500 transition-all" style="width: {{ $homeTeamStats['ft_pct'] }}%"></div>
                                        <div class="flex-1"></div>
                                        <div class="bg-rose-500 transition-all" style="width: {{ $awayTeamStats['ft_pct'] }}%"></div>
                                    </div>
                                </div>

                                <!-- Other Key Counts -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                                    <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center space-y-1">
                                        <div class="text-[10px] font-bold uppercase text-slate-500">Rebounds</div>
                                        <div class="text-base font-black text-white">
                                            <span class="text-blue-400">{{ $homeTeamStats['reb'] }}</span> - <span class="text-rose-400">{{ $awayTeamStats['reb'] }}</span>
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center space-y-1">
                                        <div class="text-[10px] font-bold uppercase text-slate-500">Assists</div>
                                        <div class="text-base font-black text-white">
                                            <span class="text-blue-400">{{ $homeTeamStats['ast'] }}</span> - <span class="text-rose-400">{{ $awayTeamStats['ast'] }}</span>
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center space-y-1">
                                        <div class="text-[10px] font-bold uppercase text-slate-500">Steals / Blocks</div>
                                        <div class="text-base font-black text-white">
                                            <span class="text-blue-400">{{ $homeTeamStats['stl'] }}/{{ $homeTeamStats['blk'] }}</span> - <span class="text-rose-400">{{ $awayTeamStats['stl'] }}/{{ $awayTeamStats['blk'] }}</span>
                                        </div>
                                    </div>
                                    <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center space-y-1">
                                        <div class="text-[10px] font-bold uppercase text-slate-500">Turnovers</div>
                                        <div class="text-base font-black text-white">
                                            <span class="text-blue-400">{{ $homeTeamStats['to'] }}</span> - <span class="text-rose-400">{{ $awayTeamStats['to'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Game Metadata & Venue Information -->
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 shadow-xl space-y-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-300 pb-2 border-b border-slate-800">
                            Game & Event Information
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs font-mono">
                            <div class="space-y-1">
                                <span class="text-slate-500 uppercase text-[10px] block">Venue</span>
                                <strong class="text-white">{{ $game->venue ?: 'Main Court / Gymnasium' }}</strong>
                            </div>
                            <div class="space-y-1">
                                <span class="text-slate-500 uppercase text-[10px] block">Game Status</span>
                                <strong class="text-emerald-400 uppercase">{{ str_replace('_', ' ', $game->status) }}</strong>
                            </div>
                            <div class="space-y-1">
                                <span class="text-slate-500 uppercase text-[10px] block">Scheduled</span>
                                <strong class="text-slate-300">{{ $game->scheduled_at ? $game->scheduled_at->format('M d, Y • g:i A') : 'Live Event' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    @endif
</div>
