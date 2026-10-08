<div class="space-y-4">
    <!-- Top Official Banner -->
    <div class="ncaa-border-thick p-2.5 bg-slate-100 flex items-center justify-between">
        <div>
            <div class="text-[10px] font-mono uppercase tracking-widest text-slate-600 font-bold">
                NATIONAL COLLEGIATE ATHLETIC ASSOCIATION &bull; OFFICIAL MEN'S BASKETBALL SCOREBOOK
            </div>
            <h2 class="text-base font-black uppercase tracking-tight text-black mt-0.5">
                {{ $sheetTitle }} &mdash; <span class="{{ $isHome ? 'text-blue-900' : 'text-rose-900' }}">{{ $team['name'] }}</span>
            </h2>
        </div>
        <div class="text-right font-mono">
            <span class="inline-block px-2 py-0.5 bg-black text-white text-[11px] font-bold uppercase rounded-sm">
                PAGE {{ $pageNumber }} OF 2
            </span>
            <div class="text-[10px] text-slate-700 font-bold mt-0.5">
                CODE: {{ $game->access_code }}
            </div>
        </div>
    </div>

    <!-- Matchup Information & Officials Metadata Box -->
    <div class="grid grid-cols-12 gap-2 text-[11px] font-mono">
        <!-- Left: Game Details -->
        <div class="col-span-7 ncaa-border p-2 space-y-1">
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <strong class="text-slate-600">TEAM:</strong> <span class="font-bold text-black">{{ $team['name'] }} ({{ $isHome ? 'HOME' : 'VISITOR' }})</span>
                </div>
                <div>
                    <strong class="text-slate-600">OPPONENT:</strong> <span class="font-bold text-black">{{ $oppTeam['name'] }}</span>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 pt-1 border-t border-slate-300">
                <div>
                    <strong class="text-slate-600">DATE:</strong> {{ $game->scheduled_at?->format('m/d/Y') ?? date('m/d/Y') }} {{ $game->scheduled_at ? $game->scheduled_at->format('g:i A') : '' }}
                </div>
                <div>
                    <strong class="text-slate-600">VENUE:</strong> {{ $game->venue ?: 'Main Gym' }}
                </div>
                <div>
                    <strong class="text-slate-600">STATUS:</strong> {{ strtoupper($game->status ?? 'ACTIVE') }}
                </div>
            </div>
            @php
                $officials = $game->settings['officials'] ?? [];
            @endphp
            @if (!empty($officials['referee']) || !empty($officials['official_scorer']))
                <div class="pt-1 border-t border-slate-300 text-[10px] text-slate-700 flex flex-wrap gap-x-3">
                    @if (!empty($officials['referee']))
                        <span><strong>REF:</strong> {{ $officials['referee'] }}</span>
                    @endif
                    @if (!empty($officials['umpire1']))
                        <span><strong>U1:</strong> {{ $officials['umpire1'] }}</span>
                    @endif
                    @if (!empty($officials['umpire2']))
                        <span><strong>U2:</strong> {{ $officials['umpire2'] }}</span>
                    @endif
                    @if (!empty($officials['official_scorer']))
                        <span><strong>SCORER:</strong> {{ $officials['official_scorer'] }}</span>
                    @endif
                    @if (!empty($officials['timer']))
                        <span><strong>TIMER:</strong> {{ $officials['timer'] }}</span>
                    @endif
                </div>
            @endif
        </div>

        <!-- Right: Score Summary Box -->
        <div class="col-span-5 ncaa-border p-2">
            <div class="text-[10px] font-bold text-center uppercase tracking-wider bg-slate-200 py-0.5 border-b border-black mb-1">
                Official Score by Halves
            </div>
            <table class="w-full text-center text-[10px]">
                <thead>
                    <tr class="font-bold text-slate-700 border-b border-slate-300">
                        <th class="py-0.5 text-left">Team</th>
                        <th>1st Half</th>
                        <th>2nd Half</th>
                        <th>OT</th>
                        <th class="font-black text-black">FINAL</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="{{ $isHome ? 'font-black bg-blue-50' : '' }}">
                        <td class="text-left py-0.5 truncate max-w-[90px]">{{ $team['name'] }}</td>
                        <td>{{ $team['h1_total_pts'] }}</td>
                        <td>{{ $team['h2_total_pts'] }}</td>
                        <td>{{ $team['ot_total_pts'] }}</td>
                        <td class="font-black text-xs text-black">{{ $team['score'] }}</td>
                    </tr>
                    <tr class="{{ !$isHome ? 'font-black bg-rose-50' : '' }}">
                        <td class="text-left py-0.5 truncate max-w-[90px]">{{ $oppTeam['name'] }}</td>
                        <td>{{ $oppTeam['h1_total_pts'] }}</td>
                        <td>{{ $oppTeam['h2_total_pts'] }}</td>
                        <td>{{ $oppTeam['ot_total_pts'] }}</td>
                        <td class="font-black text-xs text-black">{{ $oppTeam['score'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Team Fouls & Timeouts Section (Official NCAA Matrix) -->
    <div class="grid grid-cols-12 gap-2 text-[10px] font-mono">
        <!-- Team Fouls Tracker -->
        <div class="col-span-8 ncaa-border p-2">
            <div class="font-bold uppercase tracking-wider text-black border-b border-black pb-0.5 mb-1.5 flex justify-between items-center">
                <span>Team Fouls Cumulative Tracker</span>
                <span class="text-[9px] text-slate-600 font-normal">7th Foul = Bonus (1+1) &bull; 10th Foul = Double Bonus (2 Shots)</span>
            </div>

            <div class="space-y-1.5">
                <!-- 1st Half Team Fouls -->
                <div class="flex items-center gap-2">
                    <span class="w-16 font-bold text-slate-700 shrink-0">1ST HALF:</span>
                    <div class="flex items-center gap-1 flex-1">
                        @for ($f = 1; $f <= 10; $f++)
                            @php
                                $isFouled = $team['h1_team_fouls'] >= $f;
                                $isBonus = ($f === 7);
                                $isDoubleBonus = ($f === 10);
                            @endphp
                            <div class="flex-1 h-5 flex flex-col items-center justify-center border {{ $isFouled ? 'bg-black text-white font-black border-black' : 'border-slate-400 bg-white text-slate-700' }} {{ $isBonus || $isDoubleBonus ? 'border-2' : '' }}" title="Foul #{{ $f }}{{ $isBonus ? ' (Bonus)' : '' }}{{ $isDoubleBonus ? ' (Double Bonus)' : '' }}">
                                <span class="leading-none text-[9px]">{{ $isFouled ? 'X' : $f }}</span>
                            </div>
                        @endfor
                        <span class="font-bold ml-1 text-slate-900 w-12 text-right">({{ $team['h1_team_fouls'] }})</span>
                    </div>
                </div>

                <!-- 2nd Half Team Fouls -->
                <div class="flex items-center gap-2">
                    <span class="w-16 font-bold text-slate-700 shrink-0">2ND HALF:</span>
                    <div class="flex items-center gap-1 flex-1">
                        @for ($f = 1; $f <= 10; $f++)
                            @php
                                $isFouled = $team['h2_team_fouls'] >= $f;
                                $isBonus = ($f === 7);
                                $isDoubleBonus = ($f === 10);
                            @endphp
                            <div class="flex-1 h-5 flex flex-col items-center justify-center border {{ $isFouled ? 'bg-black text-white font-black border-black' : 'border-slate-400 bg-white text-slate-700' }} {{ $isBonus || $isDoubleBonus ? 'border-2' : '' }}" title="Foul #{{ $f }}">
                                <span class="leading-none text-[9px]">{{ $isFouled ? 'X' : $f }}</span>
                            </div>
                        @endfor
                        <span class="font-bold ml-1 text-slate-900 w-12 text-right">({{ $team['h2_team_fouls'] }})</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Timeouts Tracker -->
        <div class="col-span-4 ncaa-border p-2">
            <div class="font-bold uppercase tracking-wider text-black border-b border-black pb-0.5 mb-1.5 flex justify-between">
                <span>Team Timeouts</span>
                <span>Rem: {{ $team['timeouts_remaining'] }}</span>
            </div>
            <div class="grid grid-cols-5 gap-1 text-center">
                @php
                    $takenCount = count($team['timeouts_taken'] ?? []);
                @endphp
                @for ($t = 1; $t <= 5; $t++)
                    @php
                        $taken = $takenCount >= $t;
                    @endphp
                    <div class="border {{ $taken ? 'bg-black text-white font-bold' : 'border-slate-400 text-slate-700 bg-white' }} h-7 flex flex-col items-center justify-center">
                        <span class="text-[7px] leading-tight text-slate-400 {{ $taken ? 'text-slate-300' : '' }}">TO {{ $t }}</span>
                        <span class="text-[9px] font-bold leading-none">{{ $taken ? 'X' : '60s' }}</span>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- MAIN ROSTER & PLAYER SCORING MATRIX TABLE -->
    <div class="ncaa-border-thick">
        <table class="w-full text-left text-[10px] font-mono border-collapse ncaa-table">
            <thead>
                <!-- Top Group Header -->
                <tr class="bg-slate-200 text-black font-bold uppercase text-center border-b border-black">
                    <th class="p-1 w-6 text-center" rowspan="2">St</th>
                    <th class="p-1 w-8 text-center" rowspan="2">No.</th>
                    <th class="p-1 text-left" rowspan="2">Player Name</th>
                    <th class="p-1 w-8 text-center" rowspan="2">Pos</th>
                    <th class="p-1 border-l border-black text-center" colspan="4">First Half Scoring</th>
                    <th class="p-1 border-l border-black text-center" colspan="4">Second Half Scoring</th>
                    <th class="p-1 border-l border-black text-center w-8" rowspan="2">OT</th>
                    <th class="p-1 border-l border-black text-center w-10 font-black bg-slate-300" rowspan="2">TOTAL PTS</th>
                    <th class="p-1 border-l border-black text-center" colspan="5">Personal Fouls</th>
                    <th class="p-1 border-l border-black text-center w-6" rowspan="2">TF</th>
                </tr>
                <!-- Sub Header -->
                <tr class="bg-slate-100 text-slate-800 text-[9px] font-bold text-center border-b border-black">
                    <!-- 1st Half Columns -->
                    <th class="p-0.5 border-l border-black w-8">2PT</th>
                    <th class="p-0.5 w-8">3PT</th>
                    <th class="p-0.5 w-16">FT (O/X)</th>
                    <th class="p-0.5 w-8 font-black text-black bg-slate-200">PTS</th>
                    <!-- 2nd Half Columns -->
                    <th class="p-0.5 border-l border-black w-8">2PT</th>
                    <th class="p-0.5 w-8">3PT</th>
                    <th class="p-0.5 w-16">FT (O/X)</th>
                    <th class="p-0.5 w-8 font-black text-black bg-slate-200">PTS</th>
                    <!-- Personal Fouls Columns -->
                    <th class="p-0.5 border-l border-black w-6">P1</th>
                    <th class="p-0.5 w-6">P2</th>
                    <th class="p-0.5 w-6">P3</th>
                    <th class="p-0.5 w-6">P4</th>
                    <th class="p-0.5 w-6">P5</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $players = $team['players'];
                    $totalRows = max(14, count($players));
                @endphp

                @for ($i = 0; $i < $totalRows; $i++)
                    @php
                        $p = $players[$i] ?? null;
                    @endphp
                    <tr class="h-6 border-b border-slate-300 hover:bg-slate-50">
                        <!-- Starter Mark -->
                        <td class="text-center font-bold font-sans {{ $p && $p['is_starter'] ? 'bg-slate-100' : '' }}">
                            {{ $p ? ($p['is_starter'] ? 'X' : '') : '' }}
                        </td>

                        <!-- Jersey Number -->
                        <td class="text-center font-bold text-black bg-slate-50">
                            {{ $p ? $p['jersey'] : '' }}
                        </td>

                        <!-- Player Name -->
                        <td class="px-1.5 font-sans font-semibold text-black truncate max-w-[150px]">
                            {{ $p ? $p['name'] : '' }}
                        </td>

                        <!-- Position -->
                        <td class="text-center text-slate-600">
                            {{ $p ? $p['position'] : '' }}
                        </td>

                        <!-- First Half 2PT -->
                        <td class="text-center border-l border-black font-semibold">
                            {{ $p ? ($p['h1_2pt'] > 0 ? $p['h1_2pt'] : '—') : '' }}
                        </td>

                        <!-- First Half 3PT -->
                        <td class="text-center font-semibold">
                            {{ $p ? ($p['h1_3pt'] > 0 ? $p['h1_3pt'] : '—') : '' }}
                        </td>

                        <!-- First Half FTs -->
                        <td class="text-center text-[9px] tracking-widest font-mono text-slate-800">
                            {{ $p ? $p['h1_ft_str'] : '' }}
                        </td>

                        <!-- First Half PTS -->
                        <td class="text-center font-bold text-black bg-slate-100">
                            {{ $p ? ($p['h1_pts'] > 0 ? $p['h1_pts'] : '0') : '' }}
                        </td>

                        <!-- Second Half 2PT -->
                        <td class="text-center border-l border-black font-semibold">
                            {{ $p ? ($p['h2_2pt'] > 0 ? $p['h2_2pt'] : '—') : '' }}
                        </td>

                        <!-- Second Half 3PT -->
                        <td class="text-center font-semibold">
                            {{ $p ? ($p['h2_3pt'] > 0 ? $p['h2_3pt'] : '—') : '' }}
                        </td>

                        <!-- Second Half FTs -->
                        <td class="text-center text-[9px] tracking-widest font-mono text-slate-800">
                            {{ $p ? $p['h2_ft_str'] : '' }}
                        </td>

                        <!-- Second Half PTS -->
                        <td class="text-center font-bold text-black bg-slate-100">
                            {{ $p ? ($p['h2_pts'] > 0 ? $p['h2_pts'] : '0') : '' }}
                        </td>

                        <!-- OT PTS -->
                        <td class="text-center border-l border-black font-semibold">
                            {{ $p ? ($p['ot_pts'] > 0 ? $p['ot_pts'] : '—') : '' }}
                        </td>

                        <!-- Total Points -->
                        <td class="text-center border-l border-black font-black text-black bg-slate-200 text-[11px]">
                            {{ $p ? $p['total_pts'] : '' }}
                        </td>

                        <!-- Personal Fouls P1..P5 -->
                        @for ($f = 1; $f <= 5; $f++)
                            @php
                                $foulIncurred = $p && ($p['pf_count'] >= $f);
                            @endphp
                            <td class="text-center border-l border-black {{ $foulIncurred ? 'bg-black text-white font-bold' : '' }}">
                                {{ $p ? ($foulIncurred ? 'X' : $f) : '' }}
                            </td>
                        @endfor

                        <!-- Technical Foul -->
                        <td class="text-center border-l border-black {{ $p && $p['tf_count'] > 0 ? 'bg-red-700 text-white font-bold' : '' }}">
                            {{ $p ? ($p['tf_count'] > 0 ? 'T' : '') : '' }}
                        </td>
                    </tr>
                @endfor

                <!-- TOTALS ROW -->
                <tr class="bg-slate-200 text-black font-black border-t-2 border-black h-7">
                    <td colspan="4" class="px-2 uppercase tracking-wider text-right font-bold text-[10px]">
                        TEAM TOTALS:
                    </td>
                    <!-- 1st Half Totals -->
                    <td colspan="3" class="border-l border-black text-right pr-2 text-[9px] text-slate-700">1st Half Total:</td>
                    <td class="text-center border-l border-black font-black bg-slate-300">{{ $team['h1_total_pts'] }}</td>
                    <!-- 2nd Half Totals -->
                    <td colspan="3" class="border-l border-black text-right pr-2 text-[9px] text-slate-700">2nd Half Total:</td>
                    <td class="text-center border-l border-black font-black bg-slate-300">{{ $team['h2_total_pts'] }}</td>
                    <!-- OT Total -->
                    <td class="text-center border-l border-black font-black">{{ $team['ot_total_pts'] }}</td>
                    <!-- Game Final Score -->
                    <td class="text-center border-l border-black font-black text-sm bg-black text-white">{{ $team['score'] }}</td>
                    <!-- Team Fouls -->
                    <td colspan="5" class="border-l border-black text-center text-[9px]">Total Fouls: {{ $team['h1_team_fouls'] + $team['h2_team_fouls'] + $team['ot_team_fouls'] }}</td>
                    <td class="border-l border-black"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- OFFICIAL NCAA 1-160 RUNNING SCORE MATRIX -->
    <div class="ncaa-border p-2">
        <div class="text-[10px] font-black uppercase tracking-wider text-black bg-slate-200 px-2 py-0.5 border-b border-black mb-1.5 flex justify-between items-center">
            <span>Official Running Score Progression (Points 1 &mdash; 160)</span>
            <span class="text-[9px] text-slate-600 font-mono">Team Total: {{ $team['score'] }} PTS</span>
        </div>

        <!-- 4 Columns of 40 points = 160 Total Point slots -->
        <div class="grid grid-cols-4 gap-2 text-[9px] font-mono">
            @for ($col = 0; $col < 4; $col++)
                @php
                    $startPt = ($col * 40) + 1;
                    $endPt = ($col + 1) * 40;
                @endphp
                <div class="border border-black">
                    <div class="grid grid-cols-10 gap-0.5 p-1 text-center">
                        @for ($pt = $startPt; $pt <= $endPt; $pt++)
                            @php
                                $ptData = $team['running_score'][$pt] ?? null;
                            @endphp
                            <div class="h-6 border {{ $ptData ? 'border-black bg-black text-white font-bold' : 'border-slate-300 text-slate-400 bg-white' }} flex flex-col items-center justify-center leading-none overflow-hidden relative" title="Point #{{ $pt }}{{ $ptData ? ' by #' . $ptData['jersey'] : '' }}">
                                <span class="text-[7px] leading-none {{ $ptData ? 'text-slate-300' : 'text-slate-400' }}">{{ $pt }}</span>
                                @if ($ptData)
                                    <span class="text-[8px] font-bold leading-none mt-0.5">#{{ $ptData['jersey'] }}</span>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <!-- OFFICIALS CERTIFICATION & SIGNATURE FOOTER -->
    <div class="ncaa-border-thick p-2.5 bg-slate-50 text-[10px] font-mono">
        <div class="grid grid-cols-4 gap-4">
            <div>
                <span class="text-slate-600 font-bold block">OFFICIAL SCORER:</span>
                <span class="text-black font-bold block truncate text-[11px]">{{ $officials['official_scorer'] ?? '—' }}</span>
                <div class="border-b border-black h-4 mt-0.5"></div>
            </div>
            <div>
                <span class="text-slate-600 font-bold block">REFEREE:</span>
                <span class="text-black font-bold block truncate text-[11px]">{{ $officials['referee'] ?? '—' }}</span>
                <div class="border-b border-black h-4 mt-0.5"></div>
            </div>
            <div>
                <span class="text-slate-600 font-bold block">UMPIRE 1:</span>
                <span class="text-black font-bold block truncate text-[11px]">{{ $officials['umpire1'] ?? '—' }}</span>
                <div class="border-b border-black h-4 mt-0.5"></div>
            </div>
            <div>
                <span class="text-slate-600 font-bold block">UMPIRE 2:</span>
                <span class="text-black font-bold block truncate text-[11px]">{{ $officials['umpire2'] ?? '—' }}</span>
                <div class="border-b border-black h-4 mt-0.5"></div>
            </div>
        </div>
        <div class="mt-2 pt-1 border-t border-slate-300 flex items-center justify-between text-[9px] text-slate-500">
            <span>I have verified and approved this official scorebook as the true record of the contest.</span>
            <span>Generated by ProKeeper NCAA Digital System &bull; {{ date('Y-m-d H:i:s') }}</span>
        </div>
    </div>
</div>
