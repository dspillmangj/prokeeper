@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    <!-- Top Welcome & Quick Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-900/60 border border-slate-800 p-6 rounded-2xl">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-white">Athletic Command Center</h1>
            <p class="text-sm text-slate-400 mt-0.5">Manage live games, rosters, official scorebooks, and public scoreboard broadcasts.</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('teams.create') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold transition border border-slate-700 flex items-center">
                <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Team
            </a>
            <a href="{{ route('games.create') }}" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition shadow-lg shadow-blue-600/30 flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Start New Game
            </a>
        </div>
    </div>

    <!-- Active In-Progress Games -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-white flex items-center">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse mr-2.5"></span>
                Active Games ({{ $activeGames->count() }})
            </h2>
        </div>

        @if ($activeGames->isEmpty())
            <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-8 text-center">
                <p class="text-slate-400 text-sm">No games currently active.</p>
                <a href="{{ route('games.create') }}" class="inline-block mt-3 text-blue-400 hover:text-blue-300 text-sm font-semibold">Start a game now &rarr;</a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($activeGames as $game)
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-2.5 py-1 rounded-md bg-blue-950/80 border border-blue-800/60 text-blue-400 text-xs font-bold uppercase tracking-wider">
                                    {{ ucfirst($game->sport) }} &bull; {{ $game->period_name }}
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 font-mono text-xs font-bold">
                                        CODE: {{ $game->access_code }}
                                    </span>
                                    <form action="{{ route('games.destroy', $game->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this game? All recorded statistics, lineups, and play logs will be removed.');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded-lg bg-slate-800/80 hover:bg-rose-950/80 border border-slate-700/80 hover:border-rose-800/80 text-slate-400 hover:text-rose-400 transition" title="Delete Game">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Matchup Card -->
                            <div class="space-y-3 my-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $game->home_team_score_color ?? '#1e40af' }}"></div>
                                        <span class="font-bold text-base text-slate-100">{{ $game->home_display_name }}</span>
                                    </div>
                                    <span class="font-mono text-2xl font-black text-white">{{ $game->home_score }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $game->away_team_score_color ?? '#b91c1c' }}"></div>
                                        <span class="font-bold text-base text-slate-100">{{ $game->away_display_name }}</span>
                                    </div>
                                    <span class="font-mono text-2xl font-black text-white">{{ $game->away_score }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-slate-400 py-2 border-t border-slate-800/80">
                                <span>{{ $game->venue ?: 'Main Court' }}</span>
                                @if ($game->sport === 'basketball')
                                    <span class="font-medium text-blue-400">{{ $game->period_name }} &bull; {{ $game->formatted_clock }}</span>
                                @else
                                    <span class="text-slate-300">Server: {{ strtoupper($game->current_server) }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 border-t border-slate-800 grid grid-cols-2 sm:grid-cols-5 gap-2 text-center text-xs">
                            <a href="{{ route('games.operator', $game->uuid) }}" class="py-2 px-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 font-bold text-white transition shadow-md shadow-blue-600/30 flex items-center justify-center">
                                Operator
                            </a>
                            <a href="{{ route('public.stats', $game->access_code) }}" target="_blank" class="py-2 px-2.5 rounded-xl bg-indigo-950/80 hover:bg-indigo-900/80 text-indigo-300 font-bold transition border border-indigo-700/60 flex items-center justify-center">
                                Coach Stats
                            </a>
                            <a href="{{ route('public.scoreboard', $game->access_code) }}" target="_blank" class="py-2 px-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold text-slate-200 transition border border-slate-700 flex items-center justify-center">
                                Scoreboard
                            </a>
                            <a href="{{ route('public.watch', $game->access_code) }}" target="_blank" class="py-2 px-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold text-slate-200 transition border border-slate-700 flex items-center justify-center">
                                Watch Live
                            </a>
                            @if ($game->sport === 'basketball')
                                <a href="{{ route('public.scorebook', $game->access_code) }}" target="_blank" class="py-2 px-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold text-slate-200 transition border border-slate-700 flex items-center justify-center">
                                    Scorebook
                                </a>
                            @else
                                <a href="{{ route('public.watch', $game->access_code) }}?tab=boxscore" target="_blank" class="py-2 px-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold text-slate-200 transition border border-slate-700 flex items-center justify-center">
                                    Box Score
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Scheduled Games Section -->
    @if ($scheduledGames->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-white flex items-center">
                    <svg class="w-5 h-5 text-amber-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Scheduled Games ({{ $scheduledGames->count() }})
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($scheduledGames as $game)
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-2 py-0.5 rounded bg-amber-950/80 border border-amber-800/60 text-amber-400 text-xs font-semibold uppercase">
                                    {{ ucfirst($game->sport) }} &bull; Scheduled
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-mono text-slate-400 font-bold">CODE: {{ $game->access_code }}</span>
                                    <form action="{{ route('games.destroy', $game->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this scheduled game?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded bg-slate-800 hover:bg-rose-950/80 text-slate-400 hover:text-rose-400 transition" title="Delete Game">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <h3 class="text-base font-bold text-white">{{ $game->home_display_name }} <span class="text-xs font-normal text-slate-400">vs</span> {{ $game->away_display_name }}</h3>
                            <div class="text-xs text-slate-400 mt-2 space-y-0.5">
                                <div>{{ $game->scheduled_at ? $game->scheduled_at->format('M j, Y - g:i A') : 'Time TBD' }}</div>
                                <div>{{ $game->venue ?: 'Venue TBD' }}</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                            <a href="{{ route('games.operator', $game->uuid) }}" class="text-xs font-bold text-blue-400 hover:text-blue-300">
                                Launch Operator &rarr;
                            </a>
                            <a href="{{ route('public.scoreboard', $game->access_code) }}" target="_blank" class="text-xs text-slate-400 hover:text-slate-200">
                                Scoreboard Preview
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recent Completed Games -->
    @if ($recentGames->isNotEmpty())
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-white flex items-center">
                    <svg class="w-5 h-5 text-indigo-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Recent Completed Games ({{ $recentGames->count() }})
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($recentGames as $game)
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-5 shadow-lg flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-xs font-semibold uppercase">
                                    {{ ucfirst($game->sport) }} &bull; Final
                                </span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-mono text-slate-500 font-bold">CODE: {{ $game->access_code }}</span>
                                    <form action="{{ route('games.destroy', $game->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this completed game and its statistics?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded bg-slate-800 hover:bg-rose-950/80 text-slate-400 hover:text-rose-400 transition" title="Delete Game">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="my-3 space-y-1 text-sm">
                                <div class="flex items-center justify-between {{ $game->home_score >= $game->away_score ? 'font-bold text-white' : 'text-slate-400' }}">
                                    <span>{{ $game->home_display_name }}</span>
                                    <span class="font-mono text-base">{{ $game->home_score }}</span>
                                </div>
                                <div class="flex items-center justify-between {{ $game->away_score >= $game->home_score ? 'font-bold text-white' : 'text-slate-400' }}">
                                    <span>{{ $game->away_display_name }}</span>
                                    <span class="font-mono text-base">{{ $game->away_score }}</span>
                                </div>
                            </div>
                            <div class="text-xs text-slate-500">{{ $game->updated_at->format('M j, Y') }} &bull; {{ $game->venue ?: 'Main Court' }}</div>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-2 text-xs font-semibold">
                            <a href="{{ route('games.operator', $game->uuid) }}" class="text-slate-400 hover:text-slate-200">
                                Operator
                            </a>
                            <a href="{{ route('public.stats', $game->access_code) }}" target="_blank" class="text-indigo-400 hover:text-indigo-300">
                                Coach Stats
                            </a>
                            @if ($game->sport === 'basketball')
                                <a href="{{ route('public.scorebook', $game->access_code) }}" target="_blank" class="text-amber-400 hover:text-amber-300">
                                    Scorebook
                                </a>
                                <a href="{{ route('games.stats.pdf', $game->access_code) }}" class="text-blue-400 hover:text-blue-300">
                                    Stats PDF
                                </a>
                                <a href="{{ route('games.pdf', $game->access_code) }}" class="text-slate-400 hover:text-slate-200">
                                    Official PDF
                                </a>
                            @else
                                <a href="{{ route('public.watch', $game->access_code) }}?tab=boxscore" target="_blank" class="text-blue-400 hover:text-blue-300">
                                    Box Score
                                </a>
                            @endif
                            <a href="{{ route('public.watch', $game->access_code) }}" target="_blank" class="text-slate-400 hover:text-slate-200">
                                Watch
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Teams Summary Section -->
    <div class="pt-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-white">Your Teams & Rosters ({{ $teams->count() }})</h2>
            <a href="{{ route('teams.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">View All Teams &rarr;</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($teams as $team)
                <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 hover:border-slate-700 transition flex flex-col justify-between group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-xs font-semibold uppercase">
                                {{ ucfirst($team->sport) }} &bull; {{ ucfirst($team->gender) }}
                            </span>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-slate-500">{{ $team->season }}</span>
                                <form action="{{ route('teams.destroy', $team->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete {{ addslashes($team->name) }}?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1 rounded bg-slate-800 hover:bg-rose-950 text-slate-400 hover:text-rose-400 transition" title="Delete Team">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <h3 class="text-base font-bold text-white group-hover:text-blue-400 transition">
                            <a href="{{ route('teams.show', $team->id) }}">{{ $team->name }}</a>
                        </h3>
                    </div>
                    <div class="flex items-center justify-between text-xs text-slate-400 mt-4 pt-3 border-t border-slate-800/80">
                        <span>{{ $team->players_count }} Roster Players</span>
                        <a href="{{ route('teams.show', $team->id) }}" class="text-blue-400 font-medium hover:underline">Manage Roster &rarr;</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
