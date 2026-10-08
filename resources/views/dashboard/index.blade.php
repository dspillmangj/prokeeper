@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Top Welcome & Quick Actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-900/60 border border-slate-800 p-6 rounded-2xl">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-white">Athletic Command Center</h1>
            <p class="text-sm text-slate-400 mt-0.5">Manage live games, rosters, official NCAA scorebooks, and public scoreboard broadcasts.</p>
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
                                <span class="px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 font-mono text-xs font-bold">
                                    CODE: {{ $game->access_code }}
                                </span>
                            </div>

                            <!-- Matchup Card -->
                            <div class="space-y-3 my-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                        <span class="font-bold text-base text-slate-100">{{ $game->home_display_name }}</span>
                                    </div>
                                    <span class="font-mono text-2xl font-black text-white">{{ $game->home_score }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                                        <span class="font-bold text-base text-slate-100">{{ $game->away_display_name }}</span>
                                    </div>
                                    <span class="font-mono text-2xl font-black text-white">{{ $game->away_score }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs text-slate-400 py-2 border-t border-slate-800/80">
                                <span>{{ $game->venue ?: 'Main Court' }}</span>
                                @if ($game->sport === 'basketball')
                                    <span class="font-medium text-blue-400">{{ $game->period_name }}</span>
                                @else
                                    <span class="text-slate-300">Server: {{ strtoupper($game->current_server) }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 border-t border-slate-800 grid grid-cols-3 gap-2 text-center text-xs">
                            <a href="{{ route('games.operator', $game->uuid) }}" class="py-2 px-3 rounded-lg bg-blue-600 hover:bg-blue-500 font-semibold text-white transition shadow-sm">
                                Stat Operator
                            </a>
                            <a href="{{ route('public.live', $game->access_code) }}" target="_blank" class="py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 font-medium text-slate-200 transition border border-slate-700">
                                Live Scoreboard
                            </a>
                            @if ($game->sport === 'basketball')
                                <a href="{{ route('public.scorebook', $game->access_code) }}" target="_blank" class="py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 font-medium text-slate-200 transition border border-slate-700">
                                    NCAA Scorebook
                                </a>
                            @else
                                <a href="{{ route('public.live', $game->access_code) }}" target="_blank" class="py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 font-medium text-slate-200 transition border border-slate-700">
                                    Match Stats
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Teams Summary Section -->
    <div class="pt-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-white">Your Teams & Rosters ({{ $teams->count() }})</h2>
            <a href="{{ route('teams.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-semibold">View All Teams &rarr;</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($teams as $team)
                <a href="{{ route('teams.show', $team->id) }}" class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 hover:border-slate-700 transition block group">
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-xs font-semibold uppercase">
                            {{ ucfirst($team->sport) }} &bull; {{ ucfirst($team->gender) }}
                        </span>
                        <span class="text-xs text-slate-500">{{ $team->season }}</span>
                    </div>
                    <h3 class="text-base font-bold text-white group-hover:text-blue-400 transition">{{ $team->name }}</h3>
                    <div class="flex items-center justify-between text-xs text-slate-400 mt-3 pt-3 border-t border-slate-800/80">
                        <span>{{ $team->players_count }} Roster Players</span>
                        <span class="text-blue-400 font-medium">Manage Roster &rarr;</span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
