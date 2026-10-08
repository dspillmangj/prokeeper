<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" wire:poll.3000ms>
    <!-- Header with Print & Live Links -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden bg-slate-900 border border-slate-800 p-6 rounded-2xl">
        <div>
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-1 rounded bg-amber-950/80 border border-amber-800/80 text-amber-300 text-xs font-bold uppercase tracking-wider">
                    OFFICIAL NCAA DIGITAL SCOREBOOK
                </span>
                <span class="text-xs text-slate-400 font-mono">CODE: {{ $game->access_code }}</span>
            </div>
            <h1 class="text-xl font-black text-white mt-1.5">{{ $game->home_display_name }} vs {{ $game->away_display_name }}</h1>
            <p class="text-xs text-slate-400">{{ $game->venue ?: 'Main Gymnasium' }} &bull; {{ $game->scheduled_at?->format('F j, Y') ?? date('F j, Y') }}</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('public.live', $game->access_code) }}" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition">
                Live Scoreboard View
            </a>
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold transition shadow-lg shadow-blue-600/30 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print / Save PDF
            </button>
            <a href="{{ route('games.pdf', $game->access_code) }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition shadow-lg shadow-emerald-600/30">
                Download PDF
            </a>
        </div>
    </div>

    <!-- NCAA SCOREBOOK PAPER SHEET SIMULATION -->
    <div class="bg-white text-slate-900 rounded-2xl p-6 sm:p-8 shadow-2xl border border-slate-300 font-sans print:p-0 print:border-none print:shadow-none print:bg-transparent">
        
        <!-- Scorebook Top Header Grid -->
        <div class="border-b-2 border-black pb-4 mb-6">
            <div class="flex justify-between items-center text-xs font-mono uppercase tracking-wider mb-2">
                <span>Official Basketball Scorebook</span>
                <span>NCAA Standard Format</span>
            </div>
            <div class="grid grid-cols-3 text-sm">
                <div><strong>Home Team:</strong> {{ $game->home_display_name }}</div>
                <div class="text-center"><strong>Date:</strong> {{ $game->scheduled_at?->format('m/d/Y') ?? date('m/d/Y') }}</div>
                <div class="text-right"><strong>Visiting Team:</strong> {{ $game->away_display_name }}</div>
            </div>
        </div>

        <!-- RUNNING SCORE SECTION (AUTHENTIC NCAA NUMBER MATRIX) -->
        <div class="mb-8">
            <h3 class="text-xs font-black uppercase tracking-widest bg-black text-white px-2 py-1 mb-2">
                Running Score Progression
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Home Team Running Score -->
                <div class="border border-slate-400 p-2 rounded">
                    <div class="text-xs font-bold uppercase text-blue-800 border-b border-slate-300 pb-1 mb-2 flex justify-between">
                        <span>{{ $game->home_display_name }}</span>
                        <span>Total: {{ $game->home_score }} PTS</span>
                    </div>

                    <div class="grid grid-cols-10 gap-1 text-[10px] font-mono text-center">
                        @for ($i = 1; $i <= max(100, $game->home_score + 10); $i++)
                            @php
                                $pointData = $homeRunningScore[$i] ?? null;
                            @endphp
                            <div class="border {{ $pointData ? 'bg-blue-100 border-blue-600 font-bold text-blue-900' : 'border-slate-300 text-slate-400' }} h-7 flex flex-col items-center justify-center relative overflow-hidden">
                                <span class="leading-none text-[8px]">{{ $i }}</span>
                                @if ($pointData)
                                    <span class="leading-none font-bold text-[9px]">#{{ $pointData['jersey'] }}</span>
                                    <div class="absolute inset-0 border-t border-blue-600 rotate-45 pointer-events-none opacity-40"></div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>

                <!-- Away Team Running Score -->
                <div class="border border-slate-400 p-2 rounded">
                    <div class="text-xs font-bold uppercase text-rose-800 border-b border-slate-300 pb-1 mb-2 flex justify-between">
                        <span>{{ $game->away_display_name }}</span>
                        <span>Total: {{ $game->away_score }} PTS</span>
                    </div>

                    <div class="grid grid-cols-10 gap-1 text-[10px] font-mono text-center">
                        @for ($i = 1; $i <= max(100, $game->away_score + 10); $i++)
                            @php
                                $pointData = $awayRunningScore[$i] ?? null;
                            @endphp
                            <div class="border {{ $pointData ? 'bg-rose-100 border-rose-600 font-bold text-rose-900' : 'border-slate-300 text-slate-400' }} h-7 flex flex-col items-center justify-center relative overflow-hidden">
                                <span class="leading-none text-[8px]">{{ $i }}</span>
                                @if ($pointData)
                                    <span class="leading-none font-bold text-[9px]">#{{ $pointData['jersey'] }}</span>
                                    <div class="absolute inset-0 border-t border-rose-600 rotate-45 pointer-events-none opacity-40"></div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        <!-- INDIVIDUAL FOUL & SCORING MATRIX FOR HOME TEAM -->
        <div class="mb-8">
            <h3 class="text-xs font-black uppercase tracking-widest bg-blue-900 text-white px-2 py-1 mb-1">
                {{ $game->home_display_name }} - Official Roster & Fouls
            </h3>

            <table class="w-full text-left text-xs border border-collapse border-black font-mono">
                <thead class="bg-slate-200 border-b border-black text-[11px]">
                    <tr>
                        <th class="border border-black px-2 py-1 w-12">#</th>
                        <th class="border border-black px-2 py-1">Player Name</th>
                        <th class="border border-black px-2 py-1 text-center" colspan="5">Personal Fouls</th>
                        <th class="border border-black px-2 py-1 text-center">TF</th>
                        <th class="border border-black px-2 py-1 text-center">2PT</th>
                        <th class="border border-black px-2 py-1 text-center">3PT</th>
                        <th class="border border-black px-2 py-1 text-center">FTM-A</th>
                        <th class="border border-black px-2 py-1 text-center font-bold">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($homeLineup as $lp)
                        @php
                            $st = $homeStats[$lp->jersey_number] ?? null;
                            $fouls = $playerFouls["home_{$lp->jersey_number}"] ?? [];
                            $pfCount = count($fouls);
                        @endphp
                        <tr class="border-b border-slate-400 hover:bg-slate-50">
                            <td class="border border-black px-2 py-1 font-bold">#{{ $lp->jersey_number }}</td>
                            <td class="border border-black px-2 py-1 font-sans font-semibold">{{ $lp->player_name }}</td>
                            
                            <!-- 5 Personal Foul Boxes -->
                            @for ($f = 1; $f <= 5; $f++)
                                <td class="border border-black px-1 py-1 text-center w-8 {{ $pfCount >= $f ? 'bg-black text-white font-bold' : '' }}">
                                    {{ $pfCount >= $f ? 'X' : $f }}
                                </td>
                            @endfor

                            <!-- Tech Foul -->
                            <td class="border border-black px-1 py-1 text-center w-8">
                                {{ $st?->fouls_tech ? $st->fouls_tech : '—' }}
                            </td>

                            <td class="border border-black px-2 py-1 text-center">{{ $st?->fgm ?? 0 }}</td>
                            <td class="border border-black px-2 py-1 text-center">{{ $st?->fg3m ?? 0 }}</td>
                            <td class="border border-black px-2 py-1 text-center">{{ $st?->ftm ?? 0 }}-{{ $st?->fta ?? 0 }}</td>
                            <td class="border border-black px-2 py-1 text-center font-bold text-sm bg-slate-100">{{ $st?->points ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- INDIVIDUAL FOUL & SCORING MATRIX FOR AWAY TEAM -->
        <div class="mb-8">
            <h3 class="text-xs font-black uppercase tracking-widest bg-rose-900 text-white px-2 py-1 mb-1">
                {{ $game->away_display_name }} - Official Roster & Fouls
            </h3>

            <table class="w-full text-left text-xs border border-collapse border-black font-mono">
                <thead class="bg-slate-200 border-b border-black text-[11px]">
                    <tr>
                        <th class="border border-black px-2 py-1 w-12">#</th>
                        <th class="border border-black px-2 py-1">Player Name</th>
                        <th class="border border-black px-2 py-1 text-center" colspan="5">Personal Fouls</th>
                        <th class="border border-black px-2 py-1 text-center">TF</th>
                        <th class="border border-black px-2 py-1 text-center">2PT</th>
                        <th class="border border-black px-2 py-1 text-center">3PT</th>
                        <th class="border border-black px-2 py-1 text-center">FTM-A</th>
                        <th class="border border-black px-2 py-1 text-center font-bold">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($awayLineup as $lp)
                        @php
                            $st = $awayStats[$lp->jersey_number] ?? null;
                            $fouls = $playerFouls["away_{$lp->jersey_number}"] ?? [];
                            $pfCount = count($fouls);
                        @endphp
                        <tr class="border-b border-slate-400 hover:bg-slate-50">
                            <td class="border border-black px-2 py-1 font-bold">#{{ $lp->jersey_number }}</td>
                            <td class="border border-black px-2 py-1 font-sans font-semibold">{{ $lp->player_name }}</td>
                            
                            <!-- 5 Personal Foul Boxes -->
                            @for ($f = 1; $f <= 5; $f++)
                                <td class="border border-black px-1 py-1 text-center w-8 {{ $pfCount >= $f ? 'bg-black text-white font-bold' : '' }}">
                                    {{ $pfCount >= $f ? 'X' : $f }}
                                </td>
                            @endfor

                            <!-- Tech Foul -->
                            <td class="border border-black px-1 py-1 text-center w-8">
                                {{ $st?->fouls_tech ? $st->fouls_tech : '—' }}
                            </td>

                            <td class="border border-black px-2 py-1 text-center">{{ $st?->fgm ?? 0 }}</td>
                            <td class="border border-black px-2 py-1 text-center">{{ $st?->fg3m ?? 0 }}</td>
                            <td class="border border-black px-2 py-1 text-center">{{ $st?->ftm ?? 0 }}-{{ $st?->fta ?? 0 }}</td>
                            <td class="border border-black px-2 py-1 text-center font-bold text-sm bg-slate-100">{{ $st?->points ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Official Signoff & Certification -->
        <div class="border-t-2 border-black pt-4 grid grid-cols-3 gap-4 text-xs font-mono">
            <div>
                <span class="text-slate-500">Official Scorer Signature:</span>
                <div class="border-b border-black mt-6"></div>
            </div>
            <div>
                <span class="text-slate-500">Referee Signature:</span>
                <div class="border-b border-black mt-6"></div>
            </div>
            <div>
                <span class="text-slate-500">Umpire Signature:</span>
                <div class="border-b border-black mt-6"></div>
            </div>
        </div>
    </div>
</div>
