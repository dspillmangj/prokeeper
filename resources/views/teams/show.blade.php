@extends('layouts.app')

@section('title', $team->name . ' Roster')

@section('content')
<div class="space-y-4" x-data="rosterSpreadsheet({
    sport: '{{ $team->sport }}',
    saveUrl: '{{ route('teams.roster.batch', $team->id) }}',
    teamSlug: '{{ \Illuminate\Support\Str::slug($team->name) }}',
    initialPlayers: {{ Js::from($team->rosterPlayers->map(function($rp) {
        return [
            'id' => $rp->id,
            'player_id' => $rp->player_id,
            'jersey_number' => (string)$rp->jersey_number,
            'name' => trim(($rp->player->first_name ?? '') . ' ' . ($rp->player->last_name ?? '')),
            'position' => (string)($rp->position ?? ($rp->player->position ?? '')),
            'is_starter' => (bool)$rp->is_starter,
        ];
    })) }}
})"
@keydown.window="handleWindowKeydown($event)">
    <!-- Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <a href="{{ route('teams.index') }}" class="text-xs text-slate-400 hover:text-white flex items-center mb-1.5 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Teams
            </a>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-white tracking-tight">{{ $team->name }}</h1>
                <span class="px-2 py-0.5 rounded-md text-xs font-bold uppercase bg-blue-950 border border-blue-800 text-blue-400 font-mono">
                    {{ ucfirst($team->sport) }}
                </span>
                <span class="text-xs text-slate-400">
                    &bull; {{ ucfirst($team->gender) }} &bull; {{ ucfirst($team->level) }} &bull; {{ $team->season }}
                </span>
            </div>
        </div>

        <!-- Header Actions: Total count, Clear Roster, Delete Team & Quick Save -->
        <div class="flex flex-wrap items-center gap-2.5">
            <template x-if="jumpFeedback">
                <span class="px-2.5 py-1 rounded-lg bg-amber-500/20 border border-amber-500/40 text-amber-300 font-mono text-xs font-bold animate-pulse flex items-center gap-1" x-text="'Jump: ' + jumpFeedback"></span>
            </template>

            <div class="text-xs font-mono bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-800 text-slate-300">
                <span class="text-slate-500 uppercase mr-1">Roster:</span>
                <strong class="text-white text-sm" x-text="validPlayerCount"></strong>
            </div>

            <template x-if="saveSuccessMessage">
                <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5 bg-emerald-950 px-3 py-1.5 rounded-lg border border-emerald-800 animate-fade-in">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="saveSuccessMessage"></span>
                </span>
            </template>

            <!-- Clear Roster Action Form -->
            <form action="{{ route('teams.roster.clear', $team->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to completely clear the roster for {{ addslashes($team->name) }}? All roster player links will be removed.');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 text-slate-300 hover:text-amber-300 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer" title="Clear all players from this roster">
                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Clear Roster</span>
                </button>
            </form>

            <!-- Delete Team Action Form -->
            <form action="{{ route('teams.destroy', $team->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete team \'{{ addslashes($team->name) }}\'? This cannot be undone.');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 rounded-xl bg-rose-950/40 hover:bg-rose-900/60 border border-rose-900/50 hover:border-rose-700 text-rose-300 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer" title="Delete team">
                    <svg class="w-3.5 h-3.5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Delete Team</span>
                </button>
            </form>

            <button type="button" @click="saveRoster()" :disabled="isSaving"
                    class="px-5 py-2 rounded-xl font-bold text-xs text-white shadow transition flex items-center space-x-2 cursor-pointer"
                    :class="isDirty ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/30 ring-2 ring-emerald-400/50 animate-pulse' : 'bg-blue-600 hover:bg-blue-500 shadow-blue-600/30'">
                <template x-if="!isSaving">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                </template>
                <template x-if="isSaving">
                    <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                </template>
                <span x-text="isSaving ? 'Saving...' : (isDirty ? 'Save Roster *' : 'Save Roster')"></span>
            </button>
        </div>
    </div>

    <!-- Literal Spreadsheet Grid Container -->
    <div class="bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl overflow-hidden" data-grid="main-roster-grid">
        <div class="overflow-x-auto max-h-[72vh]">
            <table class="w-full border-collapse text-xs select-none">
                <!-- Spreadsheet Column Headers -->
                <thead class="bg-slate-950 text-slate-400 font-mono text-[11px] sticky top-0 z-10 border-b border-slate-700 select-none">
                    <tr>
                        <th class="w-12 py-2 px-2 text-center bg-slate-950/90 border-r border-slate-800 text-slate-500 font-bold">#</th>
                        <th class="w-32 py-2 px-3 text-left border-r border-slate-800 uppercase tracking-wider text-slate-300">
                            A &bull; Jersey #
                        </th>
                        <th class="py-2 px-3 text-left border-r border-slate-800 uppercase tracking-wider text-slate-300">
                            B &bull; Player Name (First & Last)
                        </th>
                        <th class="w-28 py-2 px-3 text-left border-r border-slate-800 uppercase tracking-wider text-slate-300">
                            C &bull; Position
                        </th>
                        <th class="w-28 py-2 px-2 text-center border-r border-slate-800 uppercase tracking-wider text-slate-300">
                            D &bull; Starter
                        </th>
                        <th class="w-14 py-2 px-2 text-center text-slate-600"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 bg-slate-950/40 text-slate-200">
                    <template x-for="(row, idx) in rows" :key="idx">
                        <tr class="hover:bg-slate-800/40 transition group">
                            <!-- Row Number Header Cell -->
                            <td class="w-12 text-center py-0 px-2 bg-slate-950/70 border-r border-slate-800 text-slate-500 font-mono text-[11px] select-none font-bold" x-text="idx + 1"></td>

                            <!-- Jersey Number Cell -->
                            <td class="w-32 p-0 border-r border-slate-800 relative">
                                <input type="text"
                                       :data-row="idx"
                                       data-field="jersey_number"
                                       data-grid="main-roster-grid"
                                       x-model="row.jersey_number"
                                       @keydown="handleKeydown($event, idx, 'jersey_number')"
                                       @paste="handlePaste($event, idx, 'jersey_number')"
                                       placeholder=""
                                       maxlength="5"
                                       class="w-full h-9 px-3 py-1.5 bg-transparent border-0 outline-none text-white font-mono font-bold text-xs focus:bg-blue-950/40 focus:ring-2 focus:ring-blue-500 focus:ring-inset transition">
                            </td>

                            <!-- Player Name Cell (First & Last) -->
                            <td class="p-0 border-r border-slate-800 relative">
                                <input type="text"
                                       :data-row="idx"
                                       data-field="name"
                                       data-grid="main-roster-grid"
                                       x-model="row.name"
                                       @keydown="handleKeydown($event, idx, 'name')"
                                       @paste="handlePaste($event, idx, 'name')"
                                       placeholder=""
                                       class="w-full h-9 px-3 py-1.5 bg-transparent border-0 outline-none text-white font-medium text-xs focus:bg-blue-950/40 focus:ring-2 focus:ring-blue-500 focus:ring-inset transition">
                            </td>

                            <!-- Position Cell -->
                            <td class="w-28 p-0 border-r border-slate-800 relative">
                                <input type="text"
                                       :data-row="idx"
                                       data-field="position"
                                       data-grid="main-roster-grid"
                                       x-model="row.position"
                                       @keydown="handleKeydown($event, idx, 'position')"
                                       @paste="handlePaste($event, idx, 'position')"
                                       placeholder="e.g. PG"
                                       maxlength="10"
                                       class="w-full h-9 px-3 py-1.5 bg-transparent border-0 outline-none text-amber-300 uppercase font-mono font-semibold text-xs focus:bg-blue-950/40 focus:ring-2 focus:ring-blue-500 focus:ring-inset transition">
                            </td>

                            <!-- Starter Toggle -->
                            <td class="w-28 p-0 border-r border-slate-800 text-center">
                                <button type="button"
                                        @click="toggleStarter(idx)"
                                        class="w-full h-9 px-2 flex items-center justify-center text-xs font-bold uppercase transition cursor-pointer"
                                        :class="row.is_starter ? 'bg-amber-950/60 text-amber-300 hover:bg-amber-900/80 font-black' : 'text-slate-500 hover:text-slate-300'">
                                    <span x-text="row.is_starter ? '★ Starter' : 'Bench'"></span>
                                </button>
                            </td>

                            <!-- Row Action (Delete) -->
                            <td class="w-14 p-0 text-center">
                                <button type="button" @click="removeRow(idx)" class="w-full h-9 flex items-center justify-center text-slate-600 hover:text-rose-400 opacity-0 group-hover:opacity-100 transition cursor-pointer" title="Delete row">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Spreadsheet Footer Bar -->
        <div class="px-4 py-2 bg-slate-950/90 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
            <div class="flex items-center space-x-2">
                <span class="text-blue-400 font-bold">Spreadsheet View:</span>
                <span>Press <kbd class="px-1 py-0.5 rounded bg-slate-800 text-amber-300 font-mono text-[10px]">1-9</kbd> jump to row &bull; <kbd class="px-1 py-0.5 rounded bg-slate-800 text-amber-300 font-mono text-[10px]">0</kbd> first available slot &bull; Arrow keys navigate &bull; Paste directly (<kbd class="px-1 py-0.5 rounded bg-slate-800 text-white font-mono text-[10px]">Ctrl+V</kbd> / <kbd class="px-1 py-0.5 rounded bg-slate-800 text-white font-mono text-[10px]">Cmd+V</kbd>) from Google Sheets or Excel.</span>
            </div>
            <div>
                <button type="button" @click="addEmptyRow()" class="text-xs text-blue-400 hover:text-blue-300 font-bold font-mono transition cursor-pointer">
                    + Add Row
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
