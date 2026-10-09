@extends('layouts.app')

@section('title', 'Teams & Rosters')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-white">Teams & Rosters</h1>
            <p class="text-sm text-slate-400 mt-1">Manage athletic rosters, jersey numbers, and team profiles.</p>
        </div>
        <a href="{{ route('teams.create') }}" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition shadow-lg shadow-blue-600/30 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create New Team
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($teams as $team)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 text-xs font-bold uppercase tracking-wider">
                            {{ ucfirst($team->sport) }} &bull; {{ ucfirst($team->gender) }}
                        </span>
                        <span class="text-xs text-slate-400 font-mono">{{ $team->season }}</span>
                    </div>

                    <h2 class="text-xl font-black text-white mb-1">{{ $team->name }}</h2>
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">{{ ucfirst($team->level) }} Level</p>

                    <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-300">
                        <span>Roster Size</span>
                        <span class="font-bold text-white font-mono">{{ $team->roster_players_count }} Players</span>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-800 flex items-center space-x-2">
                    <a href="{{ route('teams.show', $team->id) }}" class="flex-1 py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold transition text-center block border border-slate-700">
                        View & Edit Roster
                    </a>
                    <form action="{{ route('teams.destroy', $team->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete {{ addslashes($team->name) }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-2.5 rounded-xl bg-slate-900 hover:bg-rose-950/60 border border-slate-800 hover:border-rose-800/80 text-slate-400 hover:text-rose-400 transition cursor-pointer" title="Delete Team">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
