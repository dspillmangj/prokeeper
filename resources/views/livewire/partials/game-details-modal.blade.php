@if ($showGameDetailsModal)
    <div class="fixed inset-0 bg-black/85 backdrop-blur-md z-50 flex items-center justify-center p-3 select-none animate-fade-in"
         @keydown.window.escape.prevent="$wire.closeGameDetailsModal()"
         wire:keydown.escape="closeGameDetailsModal">
        
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-3xl w-full p-5 shadow-2xl space-y-4 max-h-[92vh] flex flex-col overflow-hidden"
             @click.outside="$wire.closeGameDetailsModal()">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 shrink-0">
                <div class="flex items-center space-x-2.5">
                    <div class="p-2 rounded-xl bg-indigo-600/20 text-indigo-400 border border-indigo-500/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-white">Game Details & Official Setup</h3>
                        <p class="text-[11px] text-slate-400">Configure date, location, referees, scorekeeper, clock, and match metadata</p>
                    </div>
                </div>

                <button wire:click="closeGameDetailsModal" type="button" class="p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white transition" title="Close (Esc)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Form Scrollable Body -->
            <form wire:submit.prevent="saveGameDetails" class="space-y-4 overflow-y-auto pr-1 flex-1">
                <!-- Section 1: Schedule & Venue -->
                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-1.5">
                        <span class="text-[11px] font-black uppercase tracking-wider text-blue-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Match Schedule & Venue
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Game Date</label>
                            <input type="date" wire:model="gameScheduledDate" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Start Time</label>
                            <input type="time" wire:model="gameScheduledTime" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Venue / Gymnasium</label>
                            <input type="text" wire:model="gameVenue" placeholder="e.g. Main Gymnasium" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Event / Tournament</label>
                            <input type="text" wire:model="gameEventName" placeholder="e.g. State Championship" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Officials & Table Crew (Official Scorebook Integration) -->
                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-1.5">
                        <span class="text-[11px] font-black uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            Referees & Table Crew (Official Scorebook)
                        </span>
                        <span class="text-[10px] font-mono text-slate-500">Prints directly on scorebook & PDF</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Lead Referee / Crew Chief</label>
                            <input type="text" wire:model="officialReferee" placeholder="e.g. John Smith" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Umpire 1 / Official 2</label>
                            <input type="text" wire:model="officialUmpire1" placeholder="e.g. Marcus Taylor" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Umpire 2 / Official 3</label>
                            <input type="text" wire:model="officialUmpire2" placeholder="e.g. David Miller" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Official Scorer / Scorekeeper</label>
                            <input type="text" wire:model="officialScorer" placeholder="e.g. Sarah Jenkins" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Timer / Main Clock Operator</label>
                            <input type="text" wire:model="officialTimer" placeholder="e.g. Bob Adams" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Shot Clock Operator</label>
                            <input type="text" wire:model="officialShotClock" placeholder="e.g. Chris Evans" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Game Rules, Timeouts & Scoring Standards -->
                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-1.5">
                        <span class="text-[11px] font-black uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            Timeouts Allocation & Game Rules Standards
                        </span>
                        <span class="text-[10px] font-mono text-amber-300 font-bold">
                            Total Allowed: {{ $timeoutsFull + $timeouts30s }} ({{ $timeoutsFull }} Full + {{ $timeouts30s }} 30s)
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Full Timeouts (60s)</label>
                            <input type="number" wire:model="timeoutsFull" min="0" max="10" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">30s Timeouts</label>
                            <input type="number" wire:model="timeouts30s" min="0" max="10" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">OT Timeouts (+)</label>
                            <input type="number" wire:model="timeoutsOt" min="0" max="5" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-blue-400 uppercase tracking-wider mb-1">Home TOs Left</label>
                            <input type="number" wire:model="homeTimeoutsRemaining" min="0" max="15" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-blue-500/50 text-blue-300 text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-rose-400 uppercase tracking-wider mb-1">Away TOs Left</label>
                            <input type="number" wire:model="awayTimeoutsRemaining" min="0" max="15" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-rose-500/50 text-rose-300 text-xs font-mono font-bold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 text-xs pt-1 border-t border-slate-800/60">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Period Format</label>
                            <select wire:model="periodFormat" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="quarters">4 Quarters</option>
                                <option value="halves">2 Halves (College Men / Standard)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Period Mins</label>
                            <input type="number" wire:model="gamePeriodMinutes" min="1" max="60" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">OT Mins</label>
                            <input type="number" wire:model="otMinutes" min="1" max="30" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Bonus Fouls</label>
                            <input type="number" wire:model="bonusFoulThreshold" min="1" max="20" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none" title="5 for Standard/Women, 7 for College Men">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Double Bonus</label>
                            <input type="number" wire:model="doubleBonusFoulThreshold" min="1" max="20" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none" title="5 for Standard/Women, 10 for College Men">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs pt-1 border-t border-slate-800/60">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Player Disqualification Fouls</label>
                            <input type="number" wire:model="playerFoulLimit" min="1" max="10" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Shot Clock Seconds</label>
                            <select wire:model="shotClockSeconds" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="0">Off / No Shot Clock</option>
                                <option value="35">35 Seconds (NFHS High School)</option>
                                <option value="30">30 Seconds (College Men & Women)</option>
                                <option value="24">24 Seconds (FIBA / Pro)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Period, Clock & Game Status -->
                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-1.5">
                        <span class="text-[11px] font-black uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Match Flow, Period & Clock Setup
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Match Status</label>
                            <select wire:model="gameStatus" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <option value="scheduled">Scheduled / Warmups</option>
                                <option value="in_progress">In Progress (Active)</option>
                                <option value="paused">Paused / Halftime</option>
                                <option value="final">Final (Official Completed)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Current Period</label>
                            <select wire:model="gamePeriod" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <option value="1">1st Quarter / 1st Half</option>
                                <option value="2">2nd Quarter / 2nd Half</option>
                                <option value="3">3rd Quarter / Overtime 1</option>
                                <option value="4">4th Quarter / Overtime 2</option>
                                <option value="5">Overtime (OT 1)</option>
                                <option value="6">2nd Overtime (OT 2)</option>
                            </select>
                        </div>
                        @if (isset($gameClockMinutes))
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Clock Minutes Remaining</label>
                                <input type="number" wire:model="gameClockMinutes" min="0" max="60" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Clock Seconds Remaining</label>
                                <input type="number" wire:model="gameClockSeconds" min="0" max="59" class="w-full px-3 py-1.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 text-xs font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Section 5: Team Display Names & Custom Score Colors -->
                <div class="p-3.5 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-1.5">
                        <span class="text-[11px] font-black uppercase tracking-wider text-rose-400 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                            Team Names, Colors & Layout Perspectives
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <!-- Home Team Name & Color -->
                        <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 text-white">
                                    <span class="w-3 h-3 rounded-full border border-white/30" style="background-color: {{ $homeScoreColor }};"></span>
                                    Home Team Name
                                </label>
                                <span class="text-[10px] font-mono text-slate-400 uppercase">{{ $homeScoreColor }}</span>
                            </div>
                            <input type="text" wire:model="gameHomeName" class="w-full px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs font-bold focus:ring-2 focus:ring-blue-500 focus:outline-none">
                            
                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Home Color Accent</label>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @php
                                        $presets = ['#1e40af', '#1e3a8a', '#0284c7', '#0d9488', '#15803d', '#eab308', '#ea580c', '#dc2626', '#881337', '#7c3aed', '#334155', '#0f172a'];
                                    @endphp
                                    @foreach ($presets as $hex)
                                        <button type="button" 
                                                wire:click="$set('homeScoreColor', '{{ $hex }}')"
                                                class="w-6 h-6 rounded-md border transition-all transform hover:scale-110 flex items-center justify-center {{ strtolower($homeScoreColor) === strtolower($hex) ? 'ring-2 ring-white border-white scale-105' : 'border-slate-700' }}"
                                                style="background-color: {{ $hex }};"
                                                title="{{ $hex }}">
                                            @if (strtolower($homeScoreColor) === strtolower($hex))
                                                <svg class="w-3 h-3 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </button>
                                    @endforeach
                                    <input type="color" wire:model.live="homeScoreColor" class="w-6 h-6 rounded-md cursor-pointer bg-transparent border border-slate-700 p-0.5 ml-1">
                                </div>
                            </div>
                        </div>

                        <!-- Away Team Name & Color -->
                        <div class="p-3 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <label class="block text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 text-white">
                                    <span class="w-3 h-3 rounded-full border border-white/30" style="background-color: {{ $awayScoreColor }};"></span>
                                    Away Team Name
                                </label>
                                <span class="text-[10px] font-mono text-slate-400 uppercase">{{ $awayScoreColor }}</span>
                            </div>
                            <input type="text" wire:model="gameAwayName" class="w-full px-3 py-1.5 rounded-xl bg-slate-950 border border-slate-700 text-slate-100 text-xs font-bold focus:ring-2 focus:ring-rose-500 focus:outline-none">
                            
                            <div>
                                <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-1">Away Color Accent</label>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @foreach ($presets as $hex)
                                        <button type="button" 
                                                wire:click="$set('awayScoreColor', '{{ $hex }}')"
                                                class="w-6 h-6 rounded-md border transition-all transform hover:scale-110 flex items-center justify-center {{ strtolower($awayScoreColor) === strtolower($hex) ? 'ring-2 ring-white border-white scale-105' : 'border-slate-700' }}"
                                                style="background-color: {{ $hex }};"
                                                title="{{ $hex }}">
                                            @if (strtolower($awayScoreColor) === strtolower($hex))
                                                <svg class="w-3 h-3 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            @endif
                                        </button>
                                    @endforeach
                                    <input type="color" wire:model.live="awayScoreColor" class="w-6 h-6 rounded-md cursor-pointer bg-transparent border border-slate-700 p-0.5 ml-1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Broadcast Court Flip Toggle -->
                    <div class="pt-2 border-t border-slate-800/80">
                        <label class="flex items-center space-x-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="broadcastFlip" class="w-4 h-4 rounded text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 focus:ring-offset-slate-900">
                            <div class="text-xs">
                                <span class="font-bold text-slate-200">Broadcast Court Flip to External Scoreboards</span>
                                <span class="text-[11px] text-slate-400 block">When enabled, switching court sides on the operator will also mirror and flip the sides on the public scoreboard and live stream overlays.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="pt-2 border-t border-slate-800 flex items-center justify-between shrink-0">
                    <button wire:click="closeGameDetailsModal" type="button" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs flex items-center gap-1.5 transition">
                        <span>Cancel</span>
                        <span class="px-1 py-0.2 rounded bg-slate-950 border border-slate-700 text-[9px] font-mono text-slate-400">Esc</span>
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center gap-1.5 transition">
                        <span>Save Game Setup</span>
                        <span class="px-1.5 py-0.2 rounded bg-indigo-950 border border-indigo-400 text-[9px] font-mono text-amber-300 font-bold">↵ Enter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
