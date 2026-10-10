<div class="min-h-screen bg-slate-950 text-slate-100 font-sans pb-20 selection:bg-blue-600 selection:text-white" @if($game) wire:poll.2500ms @endif>
    @if (!$game)
        <!-- Access Code Prompt Screen -->
        <div class="min-h-[85vh] flex items-center justify-center p-4 sm:p-6">
            <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6 text-center">
                <div class="inline-flex p-3 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>

                <div class="space-y-1.5">
                    <h1 class="text-2xl font-black tracking-tight text-white">Coach Live Stats</h1>
                    <p class="text-xs sm:text-sm text-slate-400">
                        Enter your 6-character game access code to view full quarter-by-quarter player statistics.
                    </p>
                </div>

                @if ($errorMessage)
                    <div class="p-3.5 rounded-xl bg-rose-950/80 border border-rose-800/80 text-rose-300 text-xs font-semibold flex items-center gap-2 text-left">
                        <svg class="w-4 h-4 shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <form wire:submit.prevent="submitCode" class="space-y-4">
                    <div>
                        <input
                            type="text"
                            wire:model.defer="inputCode"
                            placeholder="GAME CODE"
                            maxlength="10"
                            autocomplete="off"
                            autofocus
                            class="w-full text-center text-2xl font-black font-mono tracking-widest uppercase py-3.5 px-4 rounded-2xl bg-slate-950 border border-slate-700 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3.5 px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-500 font-extrabold text-white text-sm tracking-wide shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2"
                    >
                        <span>Open Coach Box Score</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>

                <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                    <a href="{{ route('public.scoreboard.prompt') }}" class="hover:text-cyan-400 transition">Stadium Board</a>
                    <span>&bull;</span>
                    <a href="{{ route('public.scorebook.prompt') }}" class="hover:text-amber-400 transition">Scorebook</a>
                    <span>&bull;</span>
                    <a href="{{ route('public.watch.prompt') }}" class="hover:text-blue-400 transition">Watch Live</a>
                </div>
            </div>
        </div>
    @else
        <!-- Live Coach Box Score Dashboard -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-6">
            
            <!-- Top Match Header & Actions -->
            <div class="rounded-3xl bg-slate-900 border border-slate-800 p-5 sm:p-6 shadow-2xl space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-800">
                    <div class="flex items-center space-x-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-indigo-950/90 border border-indigo-700/60 text-indigo-300 font-mono text-xs font-black uppercase tracking-wide">
                                COACH LIVE STATS &bull; {{ $game->access_code }}
                            </span>
                            <span class="text-xs text-slate-400 font-medium">
                                {{ $game->venue ?: 'Main Court' }}
                            </span>
                        </div>
                    </div>

                    <!-- PDF Download & Other Views -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a
                            href="{{ route('games.stats.pdf', $game->access_code) }}"
                            target="_blank"
                            class="py-2 px-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-bold text-white text-xs transition flex items-center gap-2 shadow-md shadow-indigo-600/20"
                            title="Download 2-Page Coach PDF (Landscape)"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Download PDF</span>
                        </a>

                        <a href="{{ route('public.scoreboard', $game->access_code) }}" target="_blank" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-semibold text-slate-300 text-xs border border-slate-700 transition">
                            Scoreboard
                        </a>

                        <a href="{{ route('public.scorebook', $game->access_code) }}" target="_blank" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 font-semibold text-slate-300 text-xs border border-slate-700 transition">
                            Scorebook
                        </a>
                    </div>
                </div>

                <!-- Matchup Score Cards & Line Score Strip -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
                    <!-- Home & Away Score Cards -->
                    <div class="lg:col-span-5 grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- Home Team Card -->
                        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 flex flex-col justify-between space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $game->home_team_score_color ?? '#1e40af' }}"></span>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-blue-400">HOME</span>
                                </div>
                                <span class="text-xs font-mono text-slate-400">Fouls: <strong class="text-white">{{ $game->home_fouls_current_period }}</strong></span>
                            </div>
                            <div class="font-extrabold text-base text-white leading-snug">
                                {{ $game->home_display_name }}
                            </div>
                            <div class="text-3xl sm:text-4xl font-black font-mono text-white tracking-tight pt-1">
                                {{ $game->home_score }}
                            </div>
                        </div>

                        <!-- Away Team Card -->
                        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 flex flex-col justify-between space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $game->away_team_score_color ?? '#b91c1c' }}"></span>
                                    <span class="text-[10px] font-black uppercase tracking-wider text-rose-400">AWAY</span>
                                </div>
                                <span class="text-xs font-mono text-slate-400">Fouls: <strong class="text-white">{{ $game->away_fouls_current_period }}</strong></span>
                            </div>
                            <div class="font-extrabold text-base text-white leading-snug">
                                {{ $game->away_display_name }}
                            </div>
                            <div class="text-3xl sm:text-4xl font-black font-mono text-white tracking-tight pt-1">
                                {{ $game->away_score }}
                            </div>
                        </div>
                    </div>

                    <!-- Line Score Strip -->
                    <div class="lg:col-span-7 bg-slate-950 border border-slate-800/80 rounded-2xl p-4 flex flex-col justify-center overflow-x-auto">
                        <div class="text-[10px] font-black uppercase tracking-wider text-slate-400 mb-2">Quarter-by-Quarter Line Score</div>
                        <table class="w-full text-center text-xs">
                            <thead>
                                <tr class="text-[10px] font-black uppercase text-slate-400 border-b border-slate-800/80">
                                    <th class="text-left py-1.5 px-3">Team</th>
                                    @foreach ($lineScore['periods'] as $p)
                                        <th class="py-1.5 px-3 {{ $p['is_current'] ? 'text-amber-400' : '' }}">{{ $p['label'] }}</th>
                                    @endforeach
                                    <th class="py-1.5 px-3 text-white font-black bg-slate-900/60 rounded">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody class="font-mono divide-y divide-slate-800/60">
                                <tr>
                                    <td class="text-left font-bold text-slate-200 py-2 px-3">{{ $game->home_display_name }}</td>
                                    @foreach ($lineScore['periods'] as $p)
                                        <td class="py-2 px-3 {{ $p['is_current'] ? 'text-amber-300 font-bold bg-amber-500/10 rounded' : 'text-slate-300' }}">{{ $p['home_pts'] }}</td>
                                    @endforeach
                                    <td class="py-2 px-3 font-black text-white bg-slate-900/80 rounded">{{ $lineScore['home_total'] }}</td>
                                </tr>
                                <tr>
                                    <td class="text-left font-bold text-slate-200 py-2 px-3">{{ $game->away_display_name }}</td>
                                    @foreach ($lineScore['periods'] as $p)
                                        <td class="py-2 px-3 {{ $p['is_current'] ? 'text-amber-300 font-bold bg-amber-500/10 rounded' : 'text-slate-300' }}">{{ $p['away_pts'] }}</td>
                                    @endforeach
                                    <td class="py-2 px-3 font-black text-white bg-slate-900/80 rounded">{{ $lineScore['away_total'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Interactive Period Navigation Bar & Team View Switcher -->
                <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 pt-3 border-t border-slate-800">
                    <!-- Period Filter Pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 mr-1.5">Breakdown:</span>
                        
                        <button
                            type="button"
                            wire:click="setPeriod('all')"
                            class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === 'all' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                        >
                            Full Game
                        </button>

                        @if ($isQuarters)
                            <button
                                type="button"
                                wire:click="setPeriod('1')"
                                class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === '1' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                            >
                                Q1
                            </button>
                            <button
                                type="button"
                                wire:click="setPeriod('2')"
                                class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === '2' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                            >
                                Q2
                            </button>
                            <button
                                type="button"
                                wire:click="setPeriod('3')"
                                class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === '3' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                            >
                                Q3
                            </button>
                            <button
                                type="button"
                                wire:click="setPeriod('4')"
                                class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === '4' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                            >
                                Q4
                            </button>
                            @if ($maxPeriod >= 5)
                                <button
                                    type="button"
                                    wire:click="setPeriod('5')"
                                    class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === '5' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                                >
                                    OT
                                </button>
                            @endif
                        @else
                            <button
                                type="button"
                                wire:click="setPeriod('1')"
                                class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === '1' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                            >
                                1st Half
                            </button>
                            <button
                                type="button"
                                wire:click="setPeriod('2')"
                                class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === '2' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                            >
                                2nd Half
                            </button>
                            @if ($maxPeriod >= 3)
                                <button
                                    type="button"
                                    wire:click="setPeriod('3')"
                                    class="py-1.5 px-3 rounded-xl text-xs font-bold transition {{ $selectedPeriod === '3' ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30' : 'bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-800' }}"
                                >
                                    OT
                                </button>
                            @endif
                        @endif
                    </div>

                    <!-- Team View Filter -->
                    <div class="flex items-center gap-1.5 self-start md:self-auto">
                        <button
                            type="button"
                            wire:click="setTeamView('both')"
                            class="py-1.5 px-3 rounded-xl text-xs font-semibold transition {{ $teamView === 'both' ? 'bg-slate-800 text-white border border-slate-700' : 'text-slate-400 hover:text-slate-200' }}"
                        >
                            Both Teams
                        </button>
                        <button
                            type="button"
                            wire:click="setTeamView('home')"
                            class="py-1.5 px-3 rounded-xl text-xs font-semibold transition {{ $teamView === 'home' ? 'bg-blue-900/70 text-blue-200 border border-blue-700/80 font-bold' : 'text-slate-400 hover:text-slate-200' }}"
                        >
                            {{ $game->home_display_name }}
                        </button>
                        <button
                            type="button"
                            wire:click="setTeamView('away')"
                            class="py-1.5 px-3 rounded-xl text-xs font-semibold transition {{ $teamView === 'away' ? 'bg-rose-900/70 text-rose-200 border border-rose-700/80 font-bold' : 'text-slate-400 hover:text-slate-200' }}"
                        >
                            {{ $game->away_display_name }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Home Team Box Score Section -->
            @if ($teamView === 'both' || $teamView === 'home')
                <div class="rounded-3xl bg-slate-900 border border-slate-800 p-5 sm:p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0" style="background-color: {{ $game->home_team_score_color ?? '#1e40af' }}"></span>
                            <h2 class="text-lg font-black tracking-tight text-white flex items-center gap-2">
                                <span>{{ $game->home_display_name }}</span>
                                <span class="text-xs font-bold text-blue-400 uppercase font-mono tracking-wider">(HOME)</span>
                            </h2>
                        </div>

                        <!-- Quick Shooting Badges -->
                        <div class="flex flex-wrap items-center gap-2 text-xs font-mono">
                            <span class="px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                                FG: <strong class="text-white">{{ $home['totals']['fg_str'] }}</strong> ({{ $home['totals']['fg_pct'] }}%)
                            </span>
                            <span class="px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                                2PT: <strong class="text-white">{{ $home['totals']['fg2_str'] }}</strong> ({{ $home['totals']['fg2_pct'] }}%)
                            </span>
                            <span class="px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                                3PT: <strong class="text-white">{{ $home['totals']['fg3_str'] }}</strong> ({{ $home['totals']['fg3_pct'] }}%)
                            </span>
                            <span class="px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                                FT: <strong class="text-white">{{ $home['totals']['ft_str'] }}</strong> ({{ $home['totals']['ft_pct'] }}%)
                            </span>
                        </div>
                    </div>

                    <!-- Complete Player Stats Table with Full Responsive Horizontal Scroll -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950">
                        <table class="min-w-[820px] w-full text-left text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-950 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                    <th class="py-3 px-3 text-center w-10">#</th>
                                    <th class="py-3 px-4 min-w-[170px]">Player</th>
                                    <th class="py-3 px-2.5 text-center w-12">Pos</th>
                                    <th class="py-3 px-3 text-center font-black text-amber-300 bg-amber-500/10 w-16">PTS</th>
                                    <th class="py-3 px-3 text-center">FGM-A</th>
                                    <th class="py-3 px-2.5 text-center">FG%</th>
                                    <th class="py-3 px-3 text-center">2PM-A</th>
                                    <th class="py-3 px-2.5 text-center">2P%</th>
                                    <th class="py-3 px-3 text-center">3PM-A</th>
                                    <th class="py-3 px-2.5 text-center">3P%</th>
                                    <th class="py-3 px-3 text-center">FTM-A</th>
                                    <th class="py-3 px-2.5 text-center">FT%</th>
                                    <th class="py-3 px-2.5 text-center">OFF</th>
                                    <th class="py-3 px-2.5 text-center">DEF</th>
                                    <th class="py-3 px-3 text-center font-bold text-white">REB</th>
                                    <th class="py-3 px-2.5 text-center">AST</th>
                                    <th class="py-3 px-2.5 text-center">STL</th>
                                    <th class="py-3 px-2.5 text-center">BLK</th>
                                    <th class="py-3 px-2.5 text-center">TO</th>
                                    <th class="py-3 px-2.5 text-center">PF</th>
                                    <th class="py-3 px-2.5 text-center">TF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 font-mono">
                                @forelse ($home['players'] as $p)
                                    <tr class="hover:bg-slate-900/60 transition {{ $p['is_starter'] ? 'bg-slate-900/30' : '' }}">
                                        <td class="py-2.5 px-3 text-center font-bold text-blue-400">#{{ $p['jersey'] }}</td>
                                        <td class="py-2.5 px-4 font-sans font-semibold text-white">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $p['name'] }}</span>
                                                @if ($p['is_starter'])
                                                    <span class="text-[9px] font-mono font-bold text-blue-400 bg-blue-950 border border-blue-800 px-1.5 py-0.2 rounded" title="Starter">START</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400 font-sans">{{ $p['position'] }}</td>
                                        <td class="py-2.5 px-3 text-center font-black text-amber-300 bg-amber-500/10 text-sm">{{ $p['pts'] }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-200">{{ $p['fg_str'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['fga'] > 0 ? $p['fg_pct'].'%' : '—' }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-200">{{ $p['fg2_str'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['fg2a'] > 0 ? $p['fg2_pct'].'%' : '—' }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-200">{{ $p['fg3_str'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['fg3a'] > 0 ? $p['fg3_pct'].'%' : '—' }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-200">{{ $p['ft_str'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['fta'] > 0 ? $p['ft_pct'].'%' : '—' }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['oreb'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['dreb'] }}</td>
                                        <td class="py-2.5 px-3 text-center font-bold text-white">{{ $p['reb'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-300">{{ $p['ast'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-300">{{ $p['stl'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-300">{{ $p['blk'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['to'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center font-semibold {{ $p['pf'] >= 4 ? 'text-rose-400 font-bold' : 'text-slate-300' }}">{{ $p['pf'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['tf'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="21" class="py-6 text-center text-slate-400 italic font-sans">No players recorded for this team.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-slate-950 font-mono font-bold text-white border-t-2 border-slate-700">
                                <tr>
                                    <td colspan="3" class="py-3 px-4 uppercase tracking-wider text-[11px] font-black text-slate-300">TOTALS</td>
                                    <td class="py-3 px-3 text-center font-black text-amber-300 bg-amber-500/20 text-sm">{{ $home['totals']['pts'] }}</td>
                                    <td class="py-3 px-3 text-center">{{ $home['totals']['fg_str'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['fg_pct'] }}%</td>
                                    <td class="py-3 px-3 text-center">{{ $home['totals']['fg2_str'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['fg2_pct'] }}%</td>
                                    <td class="py-3 px-3 text-center">{{ $home['totals']['fg3_str'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['fg3_pct'] }}%</td>
                                    <td class="py-3 px-3 text-center">{{ $home['totals']['ft_str'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['ft_pct'] }}%</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['oreb'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['dreb'] }}</td>
                                    <td class="py-3 px-3 text-center font-black">{{ $home['totals']['reb'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['ast'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['stl'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['blk'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['to'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['pf'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $home['totals']['tf'] }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Team Summary Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono pt-1">
                        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-sans block mb-0.5">Starters Points</span>
                            <span class="font-bold text-white text-sm">{{ $home['starter_pts'] }} pts</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-sans block mb-0.5">Bench Points</span>
                            <span class="font-bold text-white text-sm">{{ $home['bench_pts'] }} pts</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-sans block mb-0.5">Rebounds Breakdown</span>
                            <span class="font-bold text-white text-sm">{{ $home['totals']['oreb'] }} Off / {{ $home['totals']['dreb'] }} Def</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-sans block mb-0.5">Timeouts Remaining</span>
                            <span class="font-bold text-white text-sm">{{ $game->home_timeouts_remaining }} ({{ $homeBreakdown['rem_full'] }} Full, {{ $homeBreakdown['rem_30s'] }} 30s)</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Away Team Box Score Section -->
            @if ($teamView === 'both' || $teamView === 'away')
                <div class="rounded-3xl bg-slate-900 border border-slate-800 p-5 sm:p-6 shadow-xl space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <span class="w-3.5 h-3.5 rounded-full shrink-0" style="background-color: {{ $game->away_team_score_color ?? '#b91c1c' }}"></span>
                            <h2 class="text-lg font-black tracking-tight text-white flex items-center gap-2">
                                <span>{{ $game->away_display_name }}</span>
                                <span class="text-xs font-bold text-rose-400 uppercase font-mono tracking-wider">(AWAY)</span>
                            </h2>
                        </div>

                        <!-- Quick Shooting Badges -->
                        <div class="flex flex-wrap items-center gap-2 text-xs font-mono">
                            <span class="px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                                FG: <strong class="text-white">{{ $away['totals']['fg_str'] }}</strong> ({{ $away['totals']['fg_pct'] }}%)
                            </span>
                            <span class="px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                                2PT: <strong class="text-white">{{ $away['totals']['fg2_str'] }}</strong> ({{ $away['totals']['fg2_pct'] }}%)
                            </span>
                            <span class="px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                                3PT: <strong class="text-white">{{ $away['totals']['fg3_str'] }}</strong> ({{ $away['totals']['fg3_pct'] }}%)
                            </span>
                            <span class="px-3 py-1 rounded-xl bg-slate-950 border border-slate-800 text-slate-300">
                                FT: <strong class="text-white">{{ $away['totals']['ft_str'] }}</strong> ({{ $away['totals']['ft_pct'] }}%)
                            </span>
                        </div>
                    </div>

                    <!-- Complete Player Stats Table with Full Responsive Horizontal Scroll -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950">
                        <table class="min-w-[820px] w-full text-left text-xs whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-950 text-[10px] font-black uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                    <th class="py-3 px-3 text-center w-10">#</th>
                                    <th class="py-3 px-4 min-w-[170px]">Player</th>
                                    <th class="py-3 px-2.5 text-center w-12">Pos</th>
                                    <th class="py-3 px-3 text-center font-black text-amber-300 bg-amber-500/10 w-16">PTS</th>
                                    <th class="py-3 px-3 text-center">FGM-A</th>
                                    <th class="py-3 px-2.5 text-center">FG%</th>
                                    <th class="py-3 px-3 text-center">2PM-A</th>
                                    <th class="py-3 px-2.5 text-center">2P%</th>
                                    <th class="py-3 px-3 text-center">3PM-A</th>
                                    <th class="py-3 px-2.5 text-center">3P%</th>
                                    <th class="py-3 px-3 text-center">FTM-A</th>
                                    <th class="py-3 px-2.5 text-center">FT%</th>
                                    <th class="py-3 px-2.5 text-center">OFF</th>
                                    <th class="py-3 px-2.5 text-center">DEF</th>
                                    <th class="py-3 px-3 text-center font-bold text-white">REB</th>
                                    <th class="py-3 px-2.5 text-center">AST</th>
                                    <th class="py-3 px-2.5 text-center">STL</th>
                                    <th class="py-3 px-2.5 text-center">BLK</th>
                                    <th class="py-3 px-2.5 text-center">TO</th>
                                    <th class="py-3 px-2.5 text-center">PF</th>
                                    <th class="py-3 px-2.5 text-center">TF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 font-mono">
                                @forelse ($away['players'] as $p)
                                    <tr class="hover:bg-slate-900/60 transition {{ $p['is_starter'] ? 'bg-slate-900/30' : '' }}">
                                        <td class="py-2.5 px-3 text-center font-bold text-rose-400">#{{ $p['jersey'] }}</td>
                                        <td class="py-2.5 px-4 font-sans font-semibold text-white">
                                            <div class="flex items-center gap-2">
                                                <span>{{ $p['name'] }}</span>
                                                @if ($p['is_starter'])
                                                    <span class="text-[9px] font-mono font-bold text-rose-400 bg-rose-950 border border-rose-800 px-1.5 py-0.2 rounded" title="Starter">START</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400 font-sans">{{ $p['position'] }}</td>
                                        <td class="py-2.5 px-3 text-center font-black text-amber-300 bg-amber-500/10 text-sm">{{ $p['pts'] }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-200">{{ $p['fg_str'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['fga'] > 0 ? $p['fg_pct'].'%' : '—' }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-200">{{ $p['fg2_str'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['fg2a'] > 0 ? $p['fg2_pct'].'%' : '—' }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-200">{{ $p['fg3_str'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['fg3a'] > 0 ? $p['fg3_pct'].'%' : '—' }}</td>
                                        <td class="py-2.5 px-3 text-center text-slate-200">{{ $p['ft_str'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['fta'] > 0 ? $p['ft_pct'].'%' : '—' }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['oreb'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['dreb'] }}</td>
                                        <td class="py-2.5 px-3 text-center font-bold text-white">{{ $p['reb'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-300">{{ $p['ast'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-300">{{ $p['stl'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-300">{{ $p['blk'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['to'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center font-semibold {{ $p['pf'] >= 4 ? 'text-rose-400 font-bold' : 'text-slate-300' }}">{{ $p['pf'] }}</td>
                                        <td class="py-2.5 px-2.5 text-center text-slate-400">{{ $p['tf'] ?: '—' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="21" class="py-6 text-center text-slate-400 italic font-sans">No players recorded for this team.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-slate-950 font-mono font-bold text-white border-t-2 border-slate-700">
                                <tr>
                                    <td colspan="3" class="py-3 px-4 uppercase tracking-wider text-[11px] font-black text-slate-300">TOTALS</td>
                                    <td class="py-3 px-3 text-center font-black text-amber-300 bg-amber-500/20 text-sm">{{ $away['totals']['pts'] }}</td>
                                    <td class="py-3 px-3 text-center">{{ $away['totals']['fg_str'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['fg_pct'] }}%</td>
                                    <td class="py-3 px-3 text-center">{{ $away['totals']['fg2_str'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['fg2_pct'] }}%</td>
                                    <td class="py-3 px-3 text-center">{{ $away['totals']['fg3_str'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['fg3_pct'] }}%</td>
                                    <td class="py-3 px-3 text-center">{{ $away['totals']['ft_str'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['ft_pct'] }}%</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['oreb'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['dreb'] }}</td>
                                    <td class="py-3 px-3 text-center font-black">{{ $away['totals']['reb'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['ast'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['stl'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['blk'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['to'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['pf'] }}</td>
                                    <td class="py-3 px-2.5 text-center">{{ $away['totals']['tf'] }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Team Summary Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-mono pt-1">
                        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-sans block mb-0.5">Starters Points</span>
                            <span class="font-bold text-white text-sm">{{ $away['starter_pts'] }} pts</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-sans block mb-0.5">Bench Points</span>
                            <span class="font-bold text-white text-sm">{{ $away['bench_pts'] }} pts</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-sans block mb-0.5">Rebounds Breakdown</span>
                            <span class="font-bold text-white text-sm">{{ $away['totals']['oreb'] }} Off / {{ $away['totals']['dreb'] }} Def</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                            <span class="text-[10px] text-slate-400 uppercase font-sans block mb-0.5">Timeouts Remaining</span>
                            <span class="font-bold text-white text-sm">{{ $game->away_timeouts_remaining }} ({{ $awayBreakdown['rem_full'] }} Full, {{ $awayBreakdown['rem_30s'] }} 30s)</span>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    @endif
</div>
