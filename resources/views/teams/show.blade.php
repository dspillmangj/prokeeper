@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="rosterSpreadsheet({
    sport: '{{ $team->sport }}',
    saveUrl: '{{ route('teams.roster.batch', $team->id) }}',
    teamSlug: '{{ \Illuminate\Support\Str::slug($team->name) }}',
    initialPlayers: {{ Js::from($team->rosterPlayers->map(function($rp) {
        return [
            'id' => $rp->id,
            'player_id' => $rp->player_id,
            'jersey_number' => (string)$rp->jersey_number,
            'first_name' => $rp->player->first_name ?? '',
            'last_name' => $rp->player->last_name ?? '',
            'position' => $rp->position ?? '',
            'is_starter' => (bool)$rp->is_starter,
            'is_libero' => (bool)$rp->is_libero,
        ];
    })) }}
})">
    <!-- Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <a href="{{ route('teams.index') }}" class="text-xs text-slate-400 hover:text-white flex items-center mb-2 transition">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Teams
            </a>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl sm:text-3xl font-black text-white">{{ $team->name }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase bg-blue-950/80 border border-blue-800/80 text-blue-400">
                    {{ ucfirst($team->sport) }}
                </span>
            </div>
            <p class="text-sm text-slate-400 mt-1">
                {{ ucfirst($team->gender) }} &bull; {{ ucfirst($team->level) }} &bull; Season {{ $team->season }}
            </p>
        </div>

        <!-- Header Actions: Dirty Badge & Save CTA -->
        <div class="flex items-center space-x-3">
            <template x-if="saveSuccessMessage">
                <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5 bg-emerald-950/80 px-3 py-1.5 rounded-xl border border-emerald-800 animate-fade-in">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span x-text="saveSuccessMessage"></span>
                </span>
            </template>

            <button type="button" @click="saveRoster()" :disabled="isSaving"
                    class="px-5 py-2.5 rounded-xl font-bold text-sm text-white shadow-lg transition flex items-center space-x-2"
                    :class="isDirty ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/30 ring-2 ring-emerald-400/50 animate-pulse' : 'bg-blue-600 hover:bg-blue-500 shadow-blue-600/30'">
                <template x-if="!isSaving">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                </template>
                <template x-if="isSaving">
                    <svg class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                </template>
                <span x-text="isSaving ? 'Saving...' : (isDirty ? 'Save Roster Changes *' : 'Save Roster')"></span>
            </button>
        </div>
    </div>

    <!-- Spreadsheet Roster Container Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-4 sm:p-6 shadow-2xl space-y-4">
        
        <!-- Toolbar Bar: Fast Add, Bulk Paste, CSV Export & Stats -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3 pb-3 border-b border-slate-800">
            <!-- Left: Roster Count Stats -->
            <div class="flex items-center space-x-3 text-xs">
                <div class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800">
                    <span class="text-slate-400">Total Players:</span>
                    <strong class="text-white font-mono text-sm" x-text="validPlayerCount"></strong>
                </div>
                <div class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800">
                    <span class="text-slate-400">Starters:</span>
                    <strong class="text-blue-400 font-mono text-sm" x-text="starterCount"></strong>
                </div>
                <template x-if="sport === 'volleyball'">
                    <div class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-800">
                        <span class="text-slate-400">Libero:</span>
                        <strong class="text-amber-400 font-mono text-sm" x-text="rows.filter(r => r.is_libero).length"></strong>
                    </div>
                </template>
            </div>

            <!-- Right: Spreadsheet Actions Toolbar -->
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="addEmptyRow()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Add Row</span>
                </button>

                <button type="button" @click="addMultipleRows(5)" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-xs font-bold transition">
                    <span>+ 5 Rows</span>
                </button>

                <button type="button" @click="showPasteModal = true" class="px-3 py-1.5 rounded-xl bg-indigo-950 hover:bg-indigo-900 border border-indigo-700 text-indigo-200 hover:text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span>Paste from Sheets / Excel</span>
                </button>

                <button type="button" @click="exportCsv()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-bold transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export CSV</span>
                </button>

                <button type="button" @click="cleanEmptyRows()" class="px-2.5 py-1.5 rounded-xl bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-slate-200 text-xs transition" title="Remove blank rows">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>

        <!-- Spreadsheet Grid Table -->
        <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950/60 shadow-inner">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-slate-950 text-slate-400 font-mono uppercase text-[11px] select-none border-b border-slate-800">
                    <tr>
                        <th class="w-10 px-3 py-3 text-center text-slate-600">#</th>
                        <th class="w-24 px-3 py-3">Jersey # *</th>
                        <th class="px-3 py-3">First Name *</th>
                        <th class="px-3 py-3">Last Name *</th>
                        <th class="w-28 px-3 py-3">Position</th>
                        <th class="w-24 px-3 py-3 text-center">Starter</th>
                        <template x-if="sport === 'volleyball'">
                            <th class="w-24 px-3 py-3 text-center">Libero</th>
                        </template>
                        <th class="w-28 px-3 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-200">
                    <template x-for="(row, idx) in rows" :key="idx">
                        <tr class="hover:bg-slate-800/30 transition-colors group">
                            <!-- Row Index Number -->
                            <td class="px-3 py-2 text-center text-slate-600 font-mono select-none" x-text="idx + 1"></td>

                            <!-- Jersey Number -->
                            <td class="px-2 py-1.5">
                                <input type="text"
                                       :data-row="idx"
                                       data-field="jersey_number"
                                       x-model="row.jersey_number"
                                       @keydown="handleKeydown($event, idx, 'jersey_number')"
                                       placeholder="23"
                                       maxlength="5"
                                       class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700/80 focus:border-blue-500 focus:bg-slate-950 text-white font-mono font-bold text-center text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 transition">
                            </td>

                            <!-- First Name -->
                            <td class="px-2 py-1.5">
                                <input type="text"
                                       :data-row="idx"
                                       data-field="first_name"
                                       x-model="row.first_name"
                                       @keydown="handleKeydown($event, idx, 'first_name')"
                                       placeholder="e.g. Michael"
                                       class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700/80 focus:border-blue-500 focus:bg-slate-950 text-white font-medium text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 transition">
                            </td>

                            <!-- Last Name -->
                            <td class="px-2 py-1.5">
                                <input type="text"
                                       :data-row="idx"
                                       data-field="last_name"
                                       x-model="row.last_name"
                                       @keydown="handleKeydown($event, idx, 'last_name')"
                                       placeholder="e.g. Jordan"
                                       class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700/80 focus:border-blue-500 focus:bg-slate-950 text-white font-bold text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 transition">
                            </td>

                            <!-- Position -->
                            <td class="px-2 py-1.5">
                                <input type="text"
                                       :data-row="idx"
                                       data-field="position"
                                       x-model="row.position"
                                       @keydown="handleKeydown($event, idx, 'position')"
                                       placeholder="{{ $team->sport === 'basketball' ? 'PG, SG, SF...' : 'OH, MB, S...' }}"
                                       maxlength="10"
                                       class="w-full px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700/80 focus:border-blue-500 focus:bg-slate-950 text-slate-200 font-mono text-center uppercase text-xs focus:outline-none focus:ring-1 focus:ring-blue-500 transition">
                            </td>

                            <!-- Starter Checkbox -->
                            <td class="px-2 py-1.5 text-center">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           x-model="row.is_starter"
                                           class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                                </label>
                            </td>

                            <!-- Libero Checkbox (Volleyball) -->
                            <template x-if="sport === 'volleyball'">
                                <td class="px-2 py-1.5 text-center">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               x-model="row.is_libero"
                                               class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-0 focus:ring-offset-0 cursor-pointer">
                                    </label>
                                </td>
                            </template>

                            <!-- Actions -->
                            <td class="px-2 py-1.5 text-center">
                                <div class="flex items-center justify-center space-x-1 opacity-70 group-hover:opacity-100 transition">
                                    <button type="button" @click="moveRow(idx, -1)" :disabled="idx === 0" class="p-1 rounded text-slate-500 hover:text-white disabled:opacity-20" title="Move Up">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                    </button>
                                    <button type="button" @click="moveRow(idx, 1)" :disabled="idx === rows.length - 1" class="p-1 rounded text-slate-500 hover:text-white disabled:opacity-20" title="Move Down">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <button type="button" @click="duplicateRow(idx)" class="p-1 rounded text-slate-500 hover:text-blue-400" title="Duplicate Row">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                    <button type="button" @click="removeRow(idx)" class="p-1 rounded text-slate-500 hover:text-rose-400" title="Delete Row">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Spreadsheet Footer / Bottom Helper -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 text-xs text-slate-400">
            <div class="flex items-center space-x-2">
                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono font-bold">Pro-Tip</span>
                <span>Press <kbd class="px-1.5 py-0.5 rounded bg-slate-800 text-white font-mono text-[10px]">Enter</kbd> on the last row to instantly create and jump to a new player row.</span>
            </div>

            <div class="flex items-center space-x-2">
                <button type="button" @click="addEmptyRow()" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition">
                    + Add Row
                </button>
                <button type="button" @click="saveRoster()" :disabled="isSaving" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 font-bold text-white text-xs shadow-lg shadow-blue-600/30 transition">
                    <span x-text="isSaving ? 'Saving...' : 'Save Roster'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Paste From Spreadsheet / Excel Modal -->
    <div x-show="showPasteModal" class="fixed inset-0 bg-black/85 backdrop-blur-sm z-50 flex items-center justify-center p-4 select-none" style="display: none;" @click.self="showPasteModal = false" @keydown.escape.window="showPasteModal = false">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-xl w-full p-5 sm:p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <span class="p-2 rounded-xl bg-indigo-600/20 text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </span>
                    <div>
                        <h3 class="text-base font-black text-white">Paste Roster from Excel / Google Sheets</h3>
                        <p class="text-xs text-slate-400">Copy table rows from your spreadsheet and paste them below.</p>
                    </div>
                </div>
                <button @click="showPasteModal = false" class="p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3">
                <div class="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-[11px] text-slate-300 font-mono space-y-1">
                    <div class="text-indigo-400 font-bold uppercase text-[10px]">Supported Formats:</div>
                    <div>&bull; Tab or Comma Separated: <span class="text-emerald-400">23 [Tab] Michael [Tab] Jordan [Tab] SG [Tab] Starter</span></div>
                    <div>&bull; Space Delimited: <span class="text-emerald-400">#23 Michael Jordan SG</span></div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Paste Raw Roster Data</label>
                    <textarea x-model="pasteRawText" rows="7" placeholder="Paste rows from your clipboard here..." class="w-full p-3 rounded-2xl bg-slate-950 border border-slate-700 text-white font-mono text-xs focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"></textarea>
                </div>

                <div class="flex items-center space-x-4 text-xs text-slate-300">
                    <span class="font-bold text-slate-400">Import Mode:</span>
                    <label class="flex items-center space-x-1.5 cursor-pointer">
                        <input type="radio" name="paste_mode" value="append" x-model="pasteMode" class="text-indigo-600 bg-slate-950 border-slate-700">
                        <span>Append to existing</span>
                    </label>
                    <label class="flex items-center space-x-1.5 cursor-pointer">
                        <input type="radio" name="paste_mode" value="replace" x-model="pasteMode" class="text-indigo-600 bg-slate-950 border-slate-700">
                        <span>Replace existing roster</span>
                    </label>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-800 flex items-center justify-end space-x-2">
                <button type="button" @click="showPasteModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition">
                    Cancel
                </button>
                <button type="button" @click="parseAndImportPastedText()" :disabled="!pasteRawText.trim()" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition">
                    Parse & Import Roster
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function rosterSpreadsheet(config) {
    return {
        sport: config.sport,
        saveUrl: config.saveUrl,
        teamSlug: config.teamSlug,
        rows: Array.isArray(config.initialPlayers) ? config.initialPlayers : [],
        isDirty: false,
        isSaving: false,
        showPasteModal: false,
        pasteRawText: '',
        pasteMode: 'append',
        saveSuccessMessage: '',

        init() {
            if (this.rows.length === 0) {
                this.addEmptyRow();
                this.addEmptyRow();
                this.addEmptyRow();
            }
            this.$watch('rows', () => { this.isDirty = true; }, { deep: true });
        },

        addEmptyRow() {
            this.rows.push({
                id: null,
                player_id: null,
                jersey_number: '',
                first_name: '',
                last_name: '',
                position: '',
                is_starter: false,
                is_libero: false,
            });
            this.isDirty = true;
        },

        addMultipleRows(count = 5) {
            for (let i = 0; i < count; i++) {
                this.rows.push({
                    id: null,
                    player_id: null,
                    jersey_number: '',
                    first_name: '',
                    last_name: '',
                    position: '',
                    is_starter: false,
                    is_libero: false,
                });
            }
            this.isDirty = true;
        },

        removeRow(index) {
            this.rows.splice(index, 1);
            this.isDirty = true;
            if (this.rows.length === 0) {
                this.addEmptyRow();
            }
        },

        duplicateRow(index) {
            const item = this.rows[index];
            this.rows.splice(index + 1, 0, {
                id: null,
                player_id: null,
                jersey_number: '',
                first_name: item.first_name,
                last_name: item.last_name,
                position: item.position,
                is_starter: false,
                is_libero: false,
            });
            this.isDirty = true;
        },

        moveRow(index, delta) {
            const targetIndex = index + delta;
            if (targetIndex < 0 || targetIndex >= this.rows.length) return;
            const temp = this.rows[index];
            this.rows[index] = this.rows[targetIndex];
            this.rows[targetIndex] = temp;
            this.isDirty = true;
        },

        cleanEmptyRows() {
            this.rows = this.rows.filter(r => (r.jersey_number || '').trim() !== '' || (r.first_name || '').trim() !== '' || (r.last_name || '').trim() !== '');
            if (this.rows.length === 0) this.addEmptyRow();
        },

        handleKeydown(e, rowIndex, fieldName) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (rowIndex === this.rows.length - 1) {
                    this.addEmptyRow();
                    this.$nextTick(() => {
                        const inputs = document.querySelectorAll(`[data-row='${rowIndex + 1}'][data-field='jersey_number']`);
                        if (inputs.length) inputs[0].focus();
                    });
                } else {
                    const nextInput = document.querySelector(`[data-row='${rowIndex + 1}'][data-field='${fieldName}']`);
                    if (nextInput) nextInput.focus();
                }
            }
        },

        parseAndImportPastedText() {
            const text = this.pasteRawText.trim();
            if (!text) return;

            const lines = text.split(/\r\n|\r|\n/);
            const newItems = [];

            for (let line of lines) {
                line = line.trim();
                if (!line) continue;

                let parts = [];
                if (line.includes('\t')) {
                    parts = line.split('\t').map(p => p.trim());
                } else if (line.includes(',') && !line.match(/^[0-9]+$/)) {
                    parts = line.split(',').map(p => p.trim());
                } else {
                    parts = line.split(/\s+/).map(p => p.trim());
                }

                if (parts.length === 0) continue;

                let jersey = '';
                let firstName = '';
                let lastName = '';
                let pos = '';
                let isStarter = false;
                let isLibero = false;

                let p0 = parts[0].replace(/^#/, '');
                if (/^\d+$/.test(p0)) {
                    jersey = p0;
                    parts.shift();
                }

                if (parts.length >= 2) {
                    firstName = parts[0];
                    lastName = parts[1];
                    parts.splice(0, 2);
                } else if (parts.length === 1) {
                    firstName = parts[0];
                    lastName = '';
                    parts.shift();
                }

                while (parts.length > 0) {
                    const p = parts.shift();
                    const pLower = p.toLowerCase();
                    if (['starter', 'start'].includes(pLower)) {
                        isStarter = true;
                    } else if (['libero', 'lib'].includes(pLower)) {
                        isLibero = true;
                    } else if (!pos && p.length <= 5) {
                        pos = p.toUpperCase();
                    }
                }

                if (jersey || firstName || lastName) {
                    newItems.push({
                        id: null,
                        player_id: null,
                        jersey_number: jersey,
                        first_name: firstName,
                        last_name: lastName,
                        position: pos,
                        is_starter: isStarter,
                        is_libero: isLibero,
                    });
                }
            }

            if (newItems.length > 0) {
                if (this.pasteMode === 'replace') {
                    this.rows = newItems;
                } else {
                    const validExisting = this.rows.filter(r => r.jersey_number || r.first_name || r.last_name);
                    if (validExisting.length === 0) {
                        this.rows = newItems;
                    } else {
                        this.rows.push(...newItems);
                    }
                }
                this.isDirty = true;
                this.pasteRawText = '';
                this.showPasteModal = false;
            }
        },

        exportCsv() {
            const headers = ['Jersey', 'First Name', 'Last Name', 'Position', 'Starter', 'Libero'];
            const csvRows = [headers.join(',')];

            this.rows.forEach(r => {
                if (r.jersey_number || r.first_name || r.last_name) {
                    csvRows.push([
                        `"${r.jersey_number}"`,
                        `"${(r.first_name || '').replace(/"/g, '""')}"`,
                        `"${(r.last_name || '').replace(/"/g, '""')}"`,
                        `"${r.position || ''}"`,
                        r.is_starter ? 'Yes' : 'No',
                        r.is_libero ? 'Yes' : 'No',
                    ].join(','));
                }
            });

            const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', `${this.teamSlug}-roster.csv`);
            a.click();
        },

        async saveRoster() {
            this.cleanEmptyRows();
            const validRows = this.rows.filter(r => (r.jersey_number || '').trim() !== '' && (r.first_name || '').trim() !== '' && (r.last_name || '').trim() !== '');

            if (validRows.length === 0 && this.rows.length > 0) {
                alert('Please ensure each player has a Jersey #, First Name, and Last Name before saving.');
                return;
            }

            this.isSaving = true;
            this.saveSuccessMessage = '';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(this.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || ''
                    },
                    body: JSON.stringify({ players: validRows })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.isDirty = false;
                    this.saveSuccessMessage = 'Roster successfully saved!';
                    setTimeout(() => { this.saveSuccessMessage = ''; }, 4000);
                } else {
                    alert(data.message || 'Error saving roster.');
                }
            } catch (err) {
                console.error(err);
                alert('A network error occurred while saving.');
            } finally {
                this.isSaving = false;
            }
        },

        get validPlayerCount() {
            return this.rows.filter(r => (r.jersey_number || '').trim() !== '' && ((r.first_name || '').trim() !== '' || (r.last_name || '').trim() !== '')).length;
        },

        get starterCount() {
            return this.rows.filter(r => r.is_starter && (r.jersey_number || '').trim() !== '').length;
        }
    };
}
</script>
@endsection
