@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('teams.index') }}" class="text-xs text-slate-400 hover:text-white flex items-center mb-2">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Teams
        </a>
        <h1 class="text-2xl font-black text-white">Create New Team</h1>
        <p class="text-sm text-slate-400 mt-1">Set up a team program for your organization.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <form method="POST" action="{{ route('teams.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Full Team Name</label>
                <input type="text" name="name" id="name" required placeholder="e.g. LCA Eagles Boys Varsity" class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <label for="short_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Scoreboard Short Name</label>
                <input type="text" name="short_name" id="short_name" placeholder="e.g. EAGLES" maxlength="10" class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none uppercase">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="sport" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Sport</label>
                    <select name="sport" id="sport" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                        <option value="basketball">Basketball</option>
                        <option value="volleyball">Volleyball</option>
                    </select>
                </div>

                <div>
                    <label for="gender" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Gender</label>
                    <select name="gender" id="gender" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                        <option value="boys">Boys</option>
                        <option value="girls">Girls</option>
                        <option value="coed">Co-ed</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="level" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Competition Level</label>
                    <select name="level" id="level" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                        <option value="varsity">Varsity</option>
                        <option value="jv">Junior Varsity (JV)</option>
                        <option value="freshman">Freshman</option>
                        <option value="middle_school">Middle School</option>
                        <option value="club">Club / Travel</option>
                    </select>
                </div>

                <div>
                    <label for="season" class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Season</label>
                    <input type="text" name="season" id="season" value="2026-2027" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
                <a href="{{ route('teams.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition shadow-lg shadow-blue-600/30">
                    Create Team & Roster
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
