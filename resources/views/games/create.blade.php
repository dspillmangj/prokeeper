@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto" x-data="gameSetup()">
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-xs text-slate-400 hover:text-white flex items-center mb-2 transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Dashboard
        </a>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Create & Start Match</h1>
                <p class="text-sm text-slate-400 mt-0.5">Configure teams, match rules, timeouts, and launch the operator engine.</p>
            </div>
            <div class="hidden sm:flex items-center space-x-2">
                <span class="px-2.5 py-1 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-bold uppercase tracking-wider">
                    <span x-text="sport === 'basketball' ? 'Basketball Mode' : 'Volleyball Mode'"></span>
                </span>
            </div>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        <form method="POST" action="{{ route('games.store') }}" class="space-y-6">
            @csrf

            <!-- Sport Selection -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center justify-between">
                    <span>1. Select Sport</span>
                    <span class="text-[10px] text-slate-500 font-normal">Keyboard stat engine & rules configure automatically</span>
                </label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition"
                           :class="sport === 'basketball' ? 'border-blue-500 bg-blue-950/30 ring-2 ring-blue-500/20' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'">
                        <input type="radio" name="sport" value="basketball" x-model="sport" @change="onSportChange('basketball')" class="sr-only">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-base text-white flex items-center">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 mr-2 shadow-sm shadow-amber-500/50"></span>
                                Basketball
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">19 Hotkeys</span>
                        </div>
                        <span class="text-xs text-slate-400">Quarters/halves, configurable timeouts (Full/30s), fouls, and official scorebook.</span>
                    </label>

                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition"
                           :class="sport === 'volleyball' ? 'border-blue-500 bg-blue-950/30 ring-2 ring-blue-500/20' : 'border-slate-800 bg-slate-950/60 hover:border-slate-700'">
                        <input type="radio" name="sport" value="volleyball" x-model="sport" @change="onSportChange('volleyball')" class="sr-only">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-base text-white flex items-center">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 mr-2 shadow-sm shadow-indigo-500/50"></span>
                                Volleyball
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-mono">Rally Scoring</span>
                        </div>
                        <span class="text-xs text-slate-400">Rally scoring, 6-position rotation, libero tracking, hitting %, and sets.</span>
                    </label>
                </div>
            </div>

            <!-- Match Rules & Timeout Configuration Section -->
            <div class="border-t border-slate-800 pt-5 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center">
                            <svg class="w-4 h-4 mr-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                            2. Game Rules & Timeout Standards
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Customize rulebook standards, full & 30s timeout allocations, period clock, and bonus thresholds.</p>
                    </div>
                </div>

                <!-- Basketball Presets -->
                <div x-show="sport === 'basketball'" class="space-y-3">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quick Preset Standard</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        <template x-for="p in basketballPresets" :key="p.id">
                            <button type="button" @click="applyPreset(p)" 
                                    class="p-2.5 rounded-xl border text-left transition flex flex-col justify-between"
                                    :class="presetId === p.id ? 'border-indigo-500 bg-indigo-950/40 ring-1 ring-indigo-500 text-white' : 'border-slate-800 bg-slate-950/40 text-slate-300 hover:border-slate-700'">
                                <div class="font-bold text-xs" x-text="p.name"></div>
                                <div class="text-[10px] text-slate-400 mt-1" x-text="p.summary"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Volleyball Presets -->
                <div x-show="sport === 'volleyball'" class="space-y-3">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quick Preset Standard</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                        <template x-for="p in volleyballPresets" :key="p.id">
                            <button type="button" @click="applyPreset(p)" 
                                    class="p-2.5 rounded-xl border text-left transition flex flex-col justify-between"
                                    :class="presetId === p.id ? 'border-indigo-500 bg-indigo-950/40 ring-1 ring-indigo-500 text-white' : 'border-slate-800 bg-slate-950/40 text-slate-300 hover:border-slate-700'">
                                <div class="font-bold text-xs" x-text="p.name"></div>
                                <div class="text-[10px] text-slate-400 mt-1" x-text="p.summary"></div>
                            </button>
                        </template>
                    </div>
                </div>

                <input type="hidden" name="preset" :value="presetId">

                <!-- Detailed Config Inputs Card -->
                <div class="p-4 rounded-2xl bg-slate-950/80 border border-slate-800 space-y-4">
                    <!-- Basketball Specific Settings -->
                    <div x-show="sport === 'basketball'" class="space-y-4">
                        <!-- Timeouts Grid -->
                        <div>
                            <span class="block text-[11px] font-bold text-amber-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Timeouts Allocation (Per Team)
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Full / 60s Timeouts</label>
                                    <input type="number" name="timeouts_full" x-model.number="rules.timeouts_full" min="0" max="10" 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">Standard 60s / 75s timeouts</span>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">30-Second Timeouts</label>
                                    <input type="number" name="timeouts_30s" x-model.number="rules.timeouts_30s" min="0" max="10" 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">Short 30s charge timeouts</span>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Overtime Bonus TOs</label>
                                    <input type="number" name="timeouts_ot" x-model.number="rules.timeouts_ot" min="0" max="5" 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">+Timeouts added per OT</span>
                                </div>
                            </div>
                            <div class="mt-2 text-[11px] text-slate-400 flex items-center gap-1.5">
                                <span class="font-bold text-white">Total Timeouts per Team:</span> 
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-amber-300 font-mono font-bold" x-text="(rules.timeouts_full + rules.timeouts_30s) + ' Total (' + rules.timeouts_full + ' Full + ' + rules.timeouts_30s + ' 30s)'"></span>
                            </div>
                        </div>

                        <!-- Timing & Periods -->
                        <div class="border-t border-slate-800/80 pt-3">
                            <span class="block text-[11px] font-bold text-blue-400 uppercase tracking-wider mb-2">Period & Clock Structure</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Game Structure</label>
                                    <select name="period_format" x-model="rules.period_format" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white text-sm focus:border-indigo-500 focus:outline-none">
                                        <option value="quarters">4 Quarters</option>
                                        <option value="halves">2 Halves (College Men / Standard)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Period Length (Mins)</label>
                                    <input type="number" name="period_minutes" x-model.number="rules.period_minutes" min="1" max="60" 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Overtime Length (Mins)</label>
                                    <input type="number" name="ot_minutes" x-model.number="rules.ot_minutes" min="1" max="30" 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Fouls & Shot Clock -->
                        <div class="border-t border-slate-800/80 pt-3">
                            <span class="block text-[11px] font-bold text-emerald-400 uppercase tracking-wider mb-2">Fouls, Bonus & Disqualification</span>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Bonus Threshold (1-and-1 / 2-FT)</label>
                                    <input type="number" name="bonus_foul_threshold" x-model.number="rules.bonus_foul_threshold" min="1" max="20" 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">5 for NFHS/Women, 7 for Men</span>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Double Bonus Threshold</label>
                                    <input type="number" name="double_bonus_foul_threshold" x-model.number="rules.double_bonus_foul_threshold" min="1" max="20" 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">5 for NFHS/Women, 10 for Men</span>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Player Foul Limit</label>
                                    <input type="number" name="player_foul_limit" x-model.number="rules.player_foul_limit" min="1" max="10" 
                                           class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                                    <span class="text-[10px] text-slate-500 mt-0.5 block">5 for Standard/College, 6 for Pro</span>
                                </div>
                            </div>
                        </div>

                        <!-- Shot Clock -->
                        <div class="border-t border-slate-800/80 pt-3">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Shot Clock Duration</span>
                            <div class="grid grid-cols-4 gap-2">
                                <label class="p-2 rounded-xl border text-center cursor-pointer text-xs"
                                       :class="rules.shot_clock_seconds === 0 ? 'border-indigo-500 bg-indigo-950/40 text-white font-bold' : 'border-slate-800 bg-slate-900 text-slate-400'">
                                    <input type="radio" name="shot_clock_seconds" value="0" x-model.number="rules.shot_clock_seconds" class="sr-only">
                                    Off / No Clock
                                </label>
                                <label class="p-2 rounded-xl border text-center cursor-pointer text-xs"
                                       :class="rules.shot_clock_seconds === 35 ? 'border-indigo-500 bg-indigo-950/40 text-white font-bold' : 'border-slate-800 bg-slate-900 text-slate-400'">
                                    <input type="radio" name="shot_clock_seconds" value="35" x-model.number="rules.shot_clock_seconds" class="sr-only">
                                    35 Seconds (HS)
                                </label>
                                <label class="p-2 rounded-xl border text-center cursor-pointer text-xs"
                                       :class="rules.shot_clock_seconds === 30 ? 'border-indigo-500 bg-indigo-950/40 text-white font-bold' : 'border-slate-800 bg-slate-900 text-slate-400'">
                                    <input type="radio" name="shot_clock_seconds" value="30" x-model.number="rules.shot_clock_seconds" class="sr-only">
                                    30 Seconds (College)
                                </label>
                                <label class="p-2 rounded-xl border text-center cursor-pointer text-xs"
                                       :class="rules.shot_clock_seconds === 24 ? 'border-indigo-500 bg-indigo-950/40 text-white font-bold' : 'border-slate-800 bg-slate-900 text-slate-400'">
                                    <input type="radio" name="shot_clock_seconds" value="24" x-model.number="rules.shot_clock_seconds" class="sr-only">
                                    24 Seconds (FIBA)
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Volleyball Specific Settings -->
                    <div x-show="sport === 'volleyball'" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Timeouts Per Set</label>
                                <input type="number" name="timeouts_per_set" x-model.number="rules.timeouts_per_set" min="0" max="10" 
                                       class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Standard Set Points</label>
                                <input type="number" name="set_target_score" x-model.number="rules.set_target_score" min="1" max="100" 
                                       class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Deciding Set Points</label>
                                <input type="number" name="deciding_set_target_score" x-model.number="rules.deciding_set_target_score" min="1" max="100" 
                                       class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-white font-mono text-sm focus:border-indigo-500 focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Home Team -->
            <div class="border-t border-slate-800 pt-5">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center justify-between">
                    <span class="flex items-center">
                        <span class="w-3.5 h-3.5 rounded-full mr-2 shadow-sm border border-white/30" :style="'background-color: ' + homeColor"></span>
                        3. Home Team Configuration
                    </span>
                    <span class="text-[11px] font-mono text-slate-400" x-text="'Team Color: ' + homeColor"></span>
                </h3>
                <div class="space-y-3">
                    <div>
                        <label for="home_team_id" class="block text-xs text-slate-400 mb-1">Select Saved Team Roster</label>
                        <select name="home_team_id" id="home_team_id" @change="onTeamSelect('home', $event.target.value)" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                            <option value="">-- Custom / Ad-hoc Opponent Name --</option>
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}" data-home-color="{{ $t->home_jersey_color }}" data-away-color="{{ $t->away_jersey_color }}">{{ $t->name }} ({{ ucfirst($t->sport) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="home_team_name" class="block text-xs text-slate-400 mb-1">Or Custom Home Team Name</label>
                        <input type="text" name="home_team_name" id="home_team_name" placeholder="e.g. LCA Eagles" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                    </div>

                    <!-- Home Color Selector -->
                    <div>
                        <label class="block text-xs text-slate-400 mb-1.5 font-bold uppercase tracking-wider">Home Team Color</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <template x-for="c in colorPresets" :key="c.hex">
                                <button type="button" 
                                        @click="homeColor = c.hex"
                                        class="w-7 h-7 rounded-lg border transition-all transform hover:scale-110 flex items-center justify-center"
                                        :class="homeColor.toLowerCase() === c.hex.toLowerCase() ? 'ring-2 ring-white ring-offset-2 ring-offset-slate-900 scale-105 border-white' : 'border-slate-700 hover:border-slate-500'"
                                        :style="'background-color: ' + c.hex"
                                        :title="c.name + ' (' + c.hex + ')'">
                                    <svg x-show="homeColor.toLowerCase() === c.hex.toLowerCase()" class="w-3.5 h-3.5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                            <div class="flex items-center gap-1.5 ml-2 pl-2 border-l border-slate-800">
                                <input type="color" x-model="homeColor" class="w-8 h-8 rounded-lg cursor-pointer bg-transparent border border-slate-700 p-0.5">
                                <span class="font-mono text-xs text-slate-300 uppercase" x-text="homeColor"></span>
                            </div>
                        </div>
                        <input type="hidden" name="home_team_score_color" :value="homeColor">
                    </div>
                </div>
            </div>

            <!-- Away Team -->
            <div class="border-t border-slate-800 pt-5">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center justify-between">
                    <span class="flex items-center">
                        <span class="w-3.5 h-3.5 rounded-full mr-2 shadow-sm border border-white/30" :style="'background-color: ' + awayColor"></span>
                        4. Away Team Configuration
                    </span>
                    <span class="text-[11px] font-mono text-slate-400" x-text="'Team Color: ' + awayColor"></span>
                </h3>
                <div class="space-y-3">
                    <div>
                        <label for="away_team_id" class="block text-xs text-slate-400 mb-1">Select Saved Team Roster</label>
                        <select name="away_team_id" id="away_team_id" @change="onTeamSelect('away', $event.target.value)" class="w-full px-3 py-2.5 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                            <option value="">-- Custom / Ad-hoc Opponent Name --</option>
                            @foreach ($teams as $t)
                                <option value="{{ $t->id }}" data-home-color="{{ $t->home_jersey_color }}" data-away-color="{{ $t->away_jersey_color }}">{{ $t->name }} ({{ ucfirst($t->sport) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="away_team_name" class="block text-xs text-slate-400 mb-1">Or Custom Away Team Name</label>
                        <input type="text" name="away_team_name" id="away_team_name" placeholder="e.g. Heritage Warriors" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                    </div>

                    <!-- Away Color Selector -->
                    <div>
                        <label class="block text-xs text-slate-400 mb-1.5 font-bold uppercase tracking-wider">Away Team Color</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <template x-for="c in colorPresets" :key="c.hex">
                                <button type="button" 
                                        @click="awayColor = c.hex"
                                        class="w-7 h-7 rounded-lg border transition-all transform hover:scale-110 flex items-center justify-center"
                                        :class="awayColor.toLowerCase() === c.hex.toLowerCase() ? 'ring-2 ring-white ring-offset-2 ring-offset-slate-900 scale-105 border-white' : 'border-slate-700 hover:border-slate-500'"
                                        :style="'background-color: ' + c.hex"
                                        :title="c.name + ' (' + c.hex + ')'">
                                    <svg x-show="awayColor.toLowerCase() === c.hex.toLowerCase()" class="w-3.5 h-3.5 text-white drop-shadow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            </template>
                            <div class="flex items-center gap-1.5 ml-2 pl-2 border-l border-slate-800">
                                <input type="color" x-model="awayColor" class="w-8 h-8 rounded-lg cursor-pointer bg-transparent border border-slate-700 p-0.5">
                                <span class="font-mono text-xs text-slate-300 uppercase" x-text="awayColor"></span>
                            </div>
                        </div>
                        <input type="hidden" name="away_team_score_color" :value="awayColor">
                    </div>
                </div>
            </div>

            <!-- Venue & Event Details -->
            <div class="border-t border-slate-800 pt-5">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 mr-2 shadow-sm shadow-emerald-500/50"></span>
                    5. Venue & Schedule Metadata
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="venue" class="block text-xs text-slate-400 mb-1">Venue / Gymnasium Location</label>
                        <input type="text" name="venue" id="venue" placeholder="e.g. Main Court, Fieldhouse" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label for="event_name" class="block text-xs text-slate-400 mb-1">Tournament / Event Name (Optional)</label>
                        <input type="text" name="event_name" id="event_name" placeholder="e.g. Eagle Holiday Classic" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-white text-sm focus:border-blue-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold transition shadow-lg shadow-blue-600/30 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Launch Match Operator
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function gameSetup() {
    return {
        sport: 'basketball',
        presetId: 'nfhs_hs',
        homeColor: '#1e40af',
        awayColor: '#b91c1c',
        colorPresets: [
            { name: 'Royal Blue', hex: '#1e40af' },
            { name: 'Navy Blue', hex: '#1e3a8a' },
            { name: 'Sky Blue', hex: '#0284c7' },
            { name: 'Teal / Aqua', hex: '#0d9488' },
            { name: 'Forest Green', hex: '#15803d' },
            { name: 'Gold / Yellow', hex: '#eab308' },
            { name: 'Orange', hex: '#ea580c' },
            { name: 'Crimson Red', hex: '#dc2626' },
            { name: 'Maroon / Scarlet', hex: '#881337' },
            { name: 'Purple', hex: '#7c3aed' },
            { name: 'Charcoal / Slate', hex: '#334155' },
            { name: 'Black', hex: '#0f172a' }
        ],
        onTeamSelect(side, teamId) {
            if (!teamId) return;
            const selectEl = document.getElementById(side === 'home' ? 'home_team_id' : 'away_team_id');
            if (selectEl && selectEl.selectedOptions && selectEl.selectedOptions[0]) {
                const opt = selectEl.selectedOptions[0];
                const jerseyColor = side === 'home' ? opt.getAttribute('data-home-color') : opt.getAttribute('data-away-color');
                if (jerseyColor && jerseyColor !== '#ffffff') {
                    if (side === 'home') this.homeColor = jerseyColor;
                    else this.awayColor = jerseyColor;
                }
            }
        },
        rules: {
            timeouts_full: 3,
            timeouts_30s: 2,
            timeouts_ot: 1,
            period_format: 'quarters',
            period_minutes: 8,
            ot_minutes: 4,
            shot_clock_seconds: 0,
            bonus_foul_threshold: 5,
            double_bonus_foul_threshold: 5,
            player_foul_limit: 5,
            timeouts_per_set: 2,
            set_target_score: 25,
            deciding_set_target_score: 15,
        },
        basketballPresets: [
            {
                id: 'nfhs_hs',
                name: 'NFHS High School',
                summary: '4x8m Qtrs • 3 Full + 2 30s • 5 Fouls 2-FT Bonus',
                rules: {
                    timeouts_full: 3,
                    timeouts_30s: 2,
                    timeouts_ot: 1,
                    period_format: 'quarters',
                    period_minutes: 8,
                    ot_minutes: 4,
                    shot_clock_seconds: 0,
                    bonus_foul_threshold: 5,
                    double_bonus_foul_threshold: 5,
                    player_foul_limit: 5,
                }
            },
            {
                id: 'ncaa_men',
                name: "College Men's (Halves)",
                summary: '2x20m Halves • 4 Full + 2 30s • 7/10 Bonus • 30s Shot',
                rules: {
                    timeouts_full: 4,
                    timeouts_30s: 2,
                    timeouts_ot: 1,
                    period_format: 'halves',
                    period_minutes: 20,
                    ot_minutes: 5,
                    shot_clock_seconds: 30,
                    bonus_foul_threshold: 7,
                    double_bonus_foul_threshold: 10,
                    player_foul_limit: 5,
                }
            },
            {
                id: 'ncaa_women',
                name: "College Women's (Quarters)",
                summary: '4x10m Qtrs • 3 Full + 2 30s • 5 Fouls 2-FT • 30s Shot',
                rules: {
                    timeouts_full: 3,
                    timeouts_30s: 2,
                    timeouts_ot: 1,
                    period_format: 'quarters',
                    period_minutes: 10,
                    ot_minutes: 5,
                    shot_clock_seconds: 30,
                    bonus_foul_threshold: 5,
                    double_bonus_foul_threshold: 5,
                    player_foul_limit: 5,
                }
            },
            {
                id: 'fiba_pro',
                name: 'FIBA / Pro Rules',
                summary: '4x10m Qtrs • 5 Timeouts • 24s Shot • 5 Fouls Penalty',
                rules: {
                    timeouts_full: 3,
                    timeouts_30s: 2,
                    timeouts_ot: 1,
                    period_format: 'quarters',
                    period_minutes: 10,
                    ot_minutes: 5,
                    shot_clock_seconds: 24,
                    bonus_foul_threshold: 5,
                    double_bonus_foul_threshold: 5,
                    player_foul_limit: 5,
                }
            },
            {
                id: 'youth_ms',
                name: 'Middle School / Youth',
                summary: '4x6m Qtrs • 3 Full + 2 30s • No Shot Clock',
                rules: {
                    timeouts_full: 3,
                    timeouts_30s: 2,
                    timeouts_ot: 1,
                    period_format: 'quarters',
                    period_minutes: 6,
                    ot_minutes: 3,
                    shot_clock_seconds: 0,
                    bonus_foul_threshold: 5,
                    double_bonus_foul_threshold: 5,
                    player_foul_limit: 5,
                }
            },
            {
                id: 'custom_bball',
                name: 'Custom Basketball Rules',
                summary: 'Fully configurable timeouts, period format & limits',
                rules: {}
            }
        ],
        volleyballPresets: [
            {
                id: 'vb_best_of_5',
                name: 'Varsity (Best of 5)',
                summary: 'Sets 1-4 to 25 pts, Set 5 to 15 • 2 Timeouts/Set',
                rules: {
                    timeouts_per_set: 2,
                    set_target_score: 25,
                    deciding_set_target_score: 15,
                }
            },
            {
                id: 'vb_best_of_3',
                name: 'JV / Middle School (Best of 3)',
                summary: 'Sets 1-2 to 25 pts, Set 3 to 15 • 2 Timeouts/Set',
                rules: {
                    timeouts_per_set: 2,
                    set_target_score: 25,
                    deciding_set_target_score: 15,
                }
            },
            {
                id: 'custom_vb',
                name: 'Custom Volleyball Rules',
                summary: 'Fully configurable sets, points & timeouts',
                rules: {}
            }
        ],
        onSportChange(newSport) {
            if (newSport === 'basketball') {
                this.applyPreset(this.basketballPresets[0]);
            } else {
                this.applyPreset(this.volleyballPresets[0]);
            }
        },
        applyPreset(preset) {
            this.presetId = preset.id;
            if (preset.rules && Object.keys(preset.rules).length > 0) {
                this.rules = { ...this.rules, ...preset.rules };
            }
        }
    };
}
</script>
@endsection
