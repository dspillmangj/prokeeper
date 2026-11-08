@extends('layouts.app')

@section('title', 'Teams & Rosters')

@section('content')
<div class="space-y-6" x-data="{
    shareModalOpen: false,
    shareTeamId: null,
    shareTeamName: '',
    shareShares: [],
    notify: true,
    showMessage: false,
    openShare(id, name, shares) {
        this.shareTeamId = id;
        this.shareTeamName = name;
        this.shareShares = shares || [];
        this.shareModalOpen = true;
    }
}">
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

                    <button type="button" @click="openShare({{ $team->id }}, '{{ addslashes($team->name) }}', {{ Js::from($team->rosterShares) }})" class="p-2.5 rounded-xl bg-slate-800 hover:bg-blue-600/30 border border-slate-700 hover:border-blue-500/50 text-slate-300 hover:text-blue-300 transition cursor-pointer" title="Share Roster with collaborators">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </button>

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

    @if (isset($sharedRosters) && $sharedRosters->isNotEmpty())
        <div class="pt-8 border-t border-slate-800">
            <h2 class="text-xl font-bold text-white mb-1">Shared Rosters</h2>
            <p class="text-xs text-slate-400 mb-4">Rosters shared with you by other programs.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($sharedRosters as $share)
                    @php $t = $share->team; @endphp
                    @if ($t)
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-2.5 py-1 rounded-md bg-indigo-950/80 border border-indigo-700/60 text-indigo-300 text-xs font-bold uppercase tracking-wider">
                                        {{ ucfirst($t->sport) }} &bull; Shared ({{ ucfirst($share->permission) }})
                                    </span>
                                    <span class="text-xs text-slate-400 font-mono">{{ $t->season }}</span>
                                </div>

                                <h3 class="text-xl font-black text-white mb-1">{{ $t->name }}</h3>
                                @if (auth()->user()->isEnterprise() && $t->organization)
                                    <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">{{ $t->organization->name }}</p>
                                @endif

                                <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-300">
                                    <span>Roster Size</span>
                                    <span class="font-bold text-white font-mono">{{ $t->roster_players_count ?? $t->rosterPlayers()->count() }} Players</span>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-800">
                                <a href="{{ route('teams.show', $t->id) }}" class="w-full py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold transition text-center block border border-slate-700">
                                    View Roster
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <!-- Share Roster Modal (Google Drive Style) -->
    <div x-show="shareModalOpen" class="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4" style="display: none;" @keydown.escape.window="shareModalOpen = false">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4" @click.outside="shareModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div>
                    <h3 class="text-base font-black text-white">Share Roster</h3>
                    <p class="text-xs text-slate-400" x-text="shareTeamName"></p>
                </div>
                <button @click="shareModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'/teams/' + shareTeamId + '/share'" method="POST" class="space-y-3">
                @csrf
                <div class="flex flex-col sm:flex-row items-center gap-2">
                    <div class="w-full sm:flex-1">
                        <input type="email" name="email" required placeholder="collaborator@school.edu"
                               class="w-full py-2 px-3 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="w-full sm:w-auto">
                        <select name="permission" class="w-full py-2 px-3 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white focus:outline-none focus:border-blue-500">
                            <option value="edit">Can Edit</option>
                            <option value="view">View Only</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full sm:w-auto py-2 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs transition shadow shrink-0">
                        Share
                    </button>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-slate-300 cursor-pointer select-none">
                        <input type="checkbox" name="notify" value="1" x-model="notify" class="rounded bg-slate-950 border-slate-700 text-blue-600 focus:ring-0">
                        <span>Notify person via email</span>
                    </label>

                    <button type="button" @click="showMessage = !showMessage" class="text-blue-400 hover:text-blue-300 text-[11px] font-semibold" x-show="notify">
                        <span x-text="showMessage ? '- Remove note' : '+ Add message'"></span>
                    </button>
                </div>

                <div x-show="notify && showMessage" x-transition>
                    <textarea name="message" rows="2" placeholder="Add an optional message..."
                              class="w-full py-2 px-3 rounded-xl bg-slate-950 border border-slate-700 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 resize-none"></textarea>
                </div>
            </form>

            <template x-if="shareShares && shareShares.length > 0">
                <div class="pt-3 border-t border-slate-800 space-y-2">
                    <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500">People with Access:</div>
                    <div class="space-y-1.5 max-h-36 overflow-y-auto">
                        <template x-for="sh in shareShares" :key="sh.id">
                            <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-slate-950 border border-slate-800 text-xs">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="truncate text-slate-200" x-text="sh.shared_with_email"></span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-mono uppercase font-bold"
                                          :class="sh.permission === 'edit' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-slate-800 text-slate-400'"
                                          x-text="sh.permission === 'edit' ? 'Editor' : 'Viewer'"></span>
                                </div>
                                <form :action="'/teams/' + shareTeamId + '/share/' + sh.id" method="POST" onsubmit="return confirm('Revoke roster access?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-400 hover:text-rose-300 text-[11px] font-bold">Revoke</button>
                                </form>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection
