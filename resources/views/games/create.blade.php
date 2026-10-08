@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-xs text-slate-400 hover:text-white flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>
        <h1 class="text-2xl font-black text-white">Create & Start Game</h1>
        <p class="text-sm text-slate-400 mt-1">Configure teams, sport rules, and launch the real-time fast stat operator.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <form method="POST" action="{{ route('games.store') }}" class="space-y-6">
            @csrf

            <!-- Sport Selection -->
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Select Sport</label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative flex flex-col p-4 rounded-xl border border-slate-700 bg-slate-950 cursor-pointer hover:border-blue-500 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-950/30">
                        <input type="radio" name="sport" value="basketball" checked class="sr-only">
                        <span class="font-bold text-base text-white">Basketball</span>
                        <span class="text-xs text-slate-400 mt-1">19-action keyboard engine, quarters, fouls, auto-playing time & NCAA scorebook.</span>
                    </label>

                    <label class="relative flex flex-col p-4 rounded-xl border border-slate-700 bg-slate-950 cursor-pointer hover:border-blue-500 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-950/30">
                        <input type="radio" name="sport" value="volleyball" class="sr-only">
                        <span class="font-bold text-base text-white">Volleyball</span>
                        <span class="text-xs text-slate-400 mt-1">Rally scoring, 6-position court rotation, libero tracker & hit % stats.</span>
                    </label>
                </div>
            </div>

            <!-- Home Team -->
            <div class="border-t border-slate-800 pt-5">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 mr-2"></span>
                    Home Team Configuration
                </h3>
                <div class="space-y-3">
                    <div>
                        <label for="home_team_id" class="block text-xs text-slate-400 mb-1">Select Existing Team Roster</label>
                        <select name="home_team_id" id="home_team_id" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                            <option value="">-- Custom / Opponent Name --</option>
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ ucfirst($t->sport) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="home_team_name" class="block text-xs text-slate-400 mb-1">Or Custom Home Team Name</label>
                        <input type="text" name="home_team_name" id="home_team_name" placeholder="e.g. LCA Eagles" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Away Team -->
            <div class="border-t border-slate-800 pt-5">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 mr-2"></span>
                    Away Team Configuration
                </h3>
                <div class="space-y-3">
                    <div>
                        <label for="away_team_id" class="block text-xs text-slate-400 mb-1">Select Existing Team Roster</label>
                        <select name="away_team_id" id="away_team_id" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                            <option value="">-- Custom / Opponent Name --</option>
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ ucfirst($t->sport) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="away_team_name" class="block text-xs text-slate-400 mb-1">Or Custom Away Team Name</label>
                        <input type="text" name="away_team_name" id="away_team_name" placeholder="e.g. Heritage Warriors" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Venue & Details -->
            <div class="border-t border-slate-800 pt-5">
                <div>
                    <label for="venue" class="block text-xs text-slate-400 mb-1">Venue / Gymnasium Location</label>
                    <input type="text" name="venue" id="venue" placeholder="e.g. Main Court, Fieldhouse" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition shadow-lg shadow-blue-600/30">
                    Launch Game Operator
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
