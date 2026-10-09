<div class="space-y-4 print:space-y-1.5">
    <!-- Top Official Banner -->
    <div class="ncaa-border-thick p-2.5 print:p-1.5 bg-slate-100 flex items-center justify-between">
        <div>
            <div class="text-[10px] print:text-[8px] font-mono uppercase tracking-widest text-slate-600 font-bold leading-none">
                PROKEEPER ATHLETIC SYSTEMS &bull; OFFICIAL BASKETBALL SCOREBOOK
            </div>
            <h2 class="text-base print:text-sm font-black uppercase tracking-tight text-black mt-0.5 leading-none">
                {{ $sheetTitle }} &mdash; <span class="{{ $isHome ? 'text-blue-900' : 'text-rose-900' }}">{{ $team['name'] }}</span>
            </h2>
        </div>
        <div class="text-right font-mono">
            <span class="inline-block px-2 py-0.5 bg-black text-white text-[11px] print:text-[9px] font-bold uppercase rounded-sm">
                PAGE {{ $pageNumber }} OF 2
            </span>
            <div class="text-[10px] print:text-[8px] text-slate-700 font-bold mt-0.5">
                CODE: {{ $game->access_code }}
            </div>
        </div>
    </div>

    <!-- Matchup Information & Officials Metadata Box -->
    <div class="grid grid-cols-12 gap-2 print:gap-1.5 text-[11px] print:text-[8.5px] font-mono">
        <!-- Left: Game Details -->
        <div class="col-span-7 ncaa-border p-2 print:p-1 space-y-1 print:space-y-0.5">
            <div class="grid grid-cols-2 gap-2 print:gap-1">
                <div>
                    <strong class="text-slate-600">TEAM:</strong> <span class="font-bold text-black">{{ $team['name'] }} ({{ $isHome ? 'HOME' : 'VISITOR' }})</span>
                </div>
                <div>
                    <strong class="text-slate-600">OPPONENT:</strong> <span class="font-bold text-black">{{ $oppTeam['name'] }}</span>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 print:gap-1 pt-1 border-t border-slate-300">
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
                <div class="pt-1 border-t border-slate-300 text-[10px] print:text-[7.5px] text-slate-700 flex flex-wrap gap-x-3 print:gap-x-2">
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
        <div class="col-span-5 ncaa-border p-2 print:p-1">
            <div class="text-[10px] print:text-[8px] font-bold text-center uppercase tracking-wider bg-slate-200 py-0.5 border-b border-black mb-1 leading-none">
                Official Score by Halves
            </div>
            <table class="w-full text-center text-[10px] print:text-[8px]">
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
                        <td class="font-black text-xs print:text-[9px] text-black">{{ $team['score'] }}</td>
                    </tr>
                    <tr class="{{ !$isHome ? 'font-black bg-rose-50' : '' }}">
                        <td class="text-left py-0.5 truncate max-w-[90px]">{{ $oppTeam['name'] }}</td>
                        <td>{{ $oppTeam['h1_total_pts'] }}</td>
                        <td>{{ $oppTeam['h2_total_pts'] }}</td>
                        <td>{{ $oppTeam['ot_total_pts'] }}</td>
                        <td class="font-black text-xs print:text-[9px] text-black">{{ $oppTeam['score'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Team Fouls & Timeouts Section (Official Scorebook Matrix) -->
    <div class="grid grid-cols-12 gap-2 print:gap-1.5 text-[10px] print:text-[8px] font-mono">
        <!-- Team Fouls Tracker -->
        <div class="col-span-8 ncaa-border p-2 print:p-1">
            <div class="font-bold uppercase tracking-wider text-black border-b border-black pb-0.5 mb-1.5 print:mb-1 flex justify-between items-center leading-none">
                <span>Team Fouls Cumulative Tracker</span>
                <span class="text-[9px] print:text-[7.5px] text-slate-600 font-normal">7th Foul = Bonus (1+1) &bull; 10th Foul = Double Bonus (2 Shots)</span>
            </div>

            <div class="space-y-1.5 print:space-y-1">
                <!-- 1st Half Team Fouls -->
                <div class="flex items-center gap-2 print:gap-1">
                    <span class="w-16 print:w-14 font-bold text-slate-700 shrink-0">1ST HALF:</span>
                    <div class="flex items-center gap-1 print:gap-0.5 flex-1">
                        @for ($f = 1; $f <= 10; $f++)
                            @php
                                $isFouled = $team['h1_team_fouls'] >= $f;
                                $isBonus = ($f === 7);
                                $isDoubleBonus = ($f === 10);
                            @endphp
                            <div class="flex-1 h-5 print:h-4 flex flex-col items-center justify-center border {{ $isFouled ? 'bg-black text-white font-black border-black' : 'border-slate-400 bg-white text-slate-700' }} {{ $isBonus || $isDoubleBonus ? 'border-2' : '' }}" title="Foul #{{ $f }}{{ $isBonus ? ' (Bonus)' : '' }}{{ $isDoubleBonus ? ' (Double Bonus)' : '' }}">
                                <span class="leading-none text-[9px] print:text-[7.5px]">{{ $isFouled ? 'X' : $f }}</span>
                            </div>
                        @endfor
                        <span class="font-bold ml-1 text-slate-900 w-12 text-right">({{ $team['h1_team_fouls'] }})</span>
                    </div>
                </div>

                <!-- 2nd Half Team Fouls -->
                <div class="flex items-center gap-2 print:gap-1">
                    <span class="w-16 print:w-14 font-bold text-slate-700 shrink-0">2ND HALF:</span>
                    <div class="flex items-center gap-1 print:gap-0.5 flex-1">
                        @for ($f = 1; $f <= 10; $f++)
                            @php
                                $isFouled = $team['h2_team_fouls'] >= $f;
                                $isBonus = ($f === 7);
                                $isDoubleBonus = ($f === 10);
                            @endphp
                            <div class="flex-1 h-5 print:h-4 flex flex-col items-center justify-center border {{ $isFouled ? 'bg-black text-white font-black border-black' : 'border-slate-400 bg-white text-slate-700' }} {{ $isBonus || $isDoubleBonus ? 'border-2' : '' }}" title="Foul #{{ $f }}">
                                <span class="leading-none text-[9px] print:text-[7.5px]">{{ $isFouled ? 'X' : $f }}</span>
                            </div>
                        @endfor
                        <span class="font-bold ml-1 text-slate-900 w-12 text-right">({{ $team['h2_team_fouls'] }})</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Timeouts Tracker -->
        <div class="col-span-4 ncaa-border p-2 print:p-1">
            <div class="font-bold uppercase tracking-wider text-black border-b border-black pb-0.5 mb-1.5 print:mb-1 flex justify-between leading-none">
                <span>Team Timeouts</span>
                <span class="text-[9px]">Rem: {{ $team['timeouts_remaining'] }}</span>
            </div>
            @php
                $bd = $team['timeouts_breakdown'] ?? [
                    'allowed_full' => $game->full_timeouts_allowed,
                    'allowed_30s' => $game->thirty_second_timeouts_allowed,
                    'used_full' => 0,
                    'used_30s' => 0,
                ];
                $allowedFull = max(1, $bd['allowed_full'] ?? 3);
                $allowed30s = max(0, $bd['allowed_30s'] ?? 2);
                $totalBoxes = max(1, $allowedFull + $allowed30s);
                $usedFullCount = $bd['used_full'] ?? 0;
                $used30sCount = $bd['used_30s'] ?? 0;
            @endphp
            <div class="grid gap-1 print:gap-0.5 text-center" style="grid-template-columns: repeat({{ $totalBoxes }}, minmax(0, 1fr));">
                @for ($f = 1; $f <= $allowedFull; $f++)
                    @php
                        $taken = $usedFullCount >= $f;
                    @endphp
                    <div class="border {{ $taken ? 'bg-black text-white font-bold' : 'border-slate-400 text-slate-700 bg-white' }} h-7 print:h-5 flex flex-col items-center justify-center">
                        <span class="text-[7px] print:text-[6px] leading-tight text-slate-400 {{ $taken ? 'text-slate-300' : '' }}">Full {{ $f }}</span>
                        <span class="text-[9px] print:text-[7.5px] font-bold leading-none">{{ $taken ? 'X' : '60s' }}</span>
                    </div>
                @endfor
                @for ($s = 1; $s <= $allowed30s; $s++)
                    @php
                        $taken = $used30sCount >= $s;
                    @endphp
                    <div class="border {{ $taken ? 'bg-black text-white font-bold' : 'border-amber-400 text-amber-900 bg-amber-50/50' }} h-7 print:h-5 flex flex-col items-center justify-center">
                        <span class="text-[7px] print:text-[6px] leading-tight text-amber-700 {{ $taken ? 'text-slate-300' : '' }}">30s #{{ $s }}</span>
                        <span class="text-[9px] print:text-[7.5px] font-bold leading-none">{{ $taken ? 'X' : '30s' }}</span>
                    </div>
                @endfor
            </div>
        </div>
    </div>

    <!-- MAIN ROSTER & PLAYER SCORING MATRIX TABLE -->
    <div class="ncaa-border-thick">
        <table class="w-full text-left text-[10px] print:text-[8px] font-mono border-collapse ncaa-table">
            <thead>
                <!-- Top Group Header -->
                <tr class="bg-slate-200 text-black font-bold uppercase text-center border-b border-black">
                    <th class="p-1 print:p-0.5 w-6 text-center" rowspan="2">St</th>
                    <th class="p-1 print:p-0.5 w-8 text-center" rowspan="2">No.</th>
                    <th class="p-1 print:p-0.5 text-left" rowspan="2">Player Name</th>
                    <th class="p-1 print:p-0.5 w-8 text-center" rowspan="2">Pos</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="4">First Half Scoring</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="4">Second Half Scoring</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center w-8" rowspan="2">OT</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center w-10 font-black bg-slate-300" rowspan="2">TOTAL PTS</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="5">Personal Fouls</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center w-6" rowspan="2">TF</th>
                </tr>
                <!-- Sub Header -->
                <tr class="bg-slate-100 text-slate-800 text-[9px] print:text-[7.5px] font-bold text-center border-b border-black">
                    <!-- 1st Half Columns -->
                    <th class="p-0.5 border-l border-black w-8">2PT</th>
                    <th class="p-0.5 w-8">3PT</th>
                    <th class="p-0.5 w-16 print:w-12">FT (O/X)</th>
                    <th class="p-0.5 w-8 font-black text-black bg-slate-200">PTS</th>
                    <!-- 2nd Half Columns -->
                    <th class="p-0.5 border-l border-black w-8">2PT</th>
                    <th class="p-0.5 w-8">3PT</th>
                    <th class="p-0.5 w-16 print:w-12">FT (O/X)</th>
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
                    <tr class="h-6 print:h-[16px] border-b border-slate-300 hover:bg-slate-50">
                        <!-- Starter Mark -->
                        <td class="text-center font-bold font-sans {{ $p && $p['is_starter'] ? 'bg-slate-100' : '' }}">
                            {{ $p ? ($p['is_starter'] ? 'X' : '') : '' }}
                        </td>

                        <!-- Jersey Number -->
                        <td class="text-center font-bold text-black bg-slate-50">
                            {{ $p ? $p['jersey'] : '' }}
                        </td>

                        <!-- Player Name -->
                        <td class="px-1.5 print:px-1 font-sans font-semibold text-black truncate max-w-[150px]">
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
                        <td class="text-center text-[9px] print:text-[7.5px] tracking-wider font-mono text-slate-800">
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
                        <td class="text-center text-[9px] print:text-[7.5px] tracking-wider font-mono text-slate-800">
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
                        <td class="text-center border-l border-black font-black text-black bg-slate-200 text-[11px] print:text-[9px]">
                            {{ $p ? $p['total_pts'] : '' }}
                        </td>

                        <!-- Personal Fouls P1..P5 -->
                        @for ($f = 1; $f <= 5; $f++)
                            @php
                                $foulIncurred = $p && ($p['pf_count'] >= $f);
                            @endphp
                            <td class="text-center border-l border-black {{ $foulIncurred ? 'bg-black text-white font-bold' : '' }}">
                                <span class="leading-none">{{ $p ? ($foulIncurred ? 'X' : $f) : '' }}</span>
                            </td>
                        @endfor

                        <!-- Technical Foul -->
                        <td class="text-center border-l border-black {{ $p && $p['tf_count'] > 0 ? 'bg-red-700 text-white font-bold' : '' }}">
                            <span class="leading-none">{{ $p ? ($p['tf_count'] > 0 ? 'T' : '') : '' }}</span>
                        </td>
                    </tr>
                @endfor

                <!-- TOTALS ROW -->
                <tr class="bg-slate-200 text-black font-black border-t-2 border-black h-7 print:h-5">
                    <td colspan="4" class="px-2 print:px-1 uppercase tracking-wider text-right font-bold text-[10px] print:text-[8px]">
                        TEAM TOTALS:
                    </td>
                    <!-- 1st Half Totals -->
                    <td colspan="3" class="border-l border-black text-right pr-2 print:pr-1 text-[9px] print:text-[7.5px] text-slate-700">1st Half Total:</td>
                    <td class="text-center border-l border-black font-black bg-slate-300">{{ $team['h1_total_pts'] }}</td>
                    <!-- 2nd Half Totals -->
                    <td colspan="3" class="border-l border-black text-right pr-2 print:pr-1 text-[9px] print:text-[7.5px] text-slate-700">2nd Half Total:</td>
                    <td class="text-center border-l border-black font-black bg-slate-300">{{ $team['h2_total_pts'] }}</td>
                    <!-- OT Total -->
                    <td class="text-center border-l border-black font-black">{{ $team['ot_total_pts'] }}</td>
                    <!-- Game Final Score -->
                    <td class="text-center border-l border-black font-black text-sm print:text-[10px] bg-black text-white">{{ $team['score'] }}</td>
                    <!-- Team Fouls -->
                    <td colspan="5" class="border-l border-black text-center text-[9px] print:text-[7.5px]">Total Fouls: {{ $team['h1_team_fouls'] + $team['h2_team_fouls'] + $team['ot_team_fouls'] }}</td>
                    <td class="border-l border-black"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- OFFICIAL 1-160 RUNNING SCORE MATRIX -->
    <div class="ncaa-border p-2 print:p-1">
        <div class="text-[10px] print:text-[8px] font-black uppercase tracking-wider text-black bg-slate-200 px-2 print:px-1 py-0.5 border-b border-black mb-1.5 print:mb-1 flex justify-between items-center leading-none">
            <span>Official Running Score Progression (Points 1 &mdash; 160)</span>
            <span class="text-[9px] print:text-[7.5px] text-slate-600 font-mono">Team Total: {{ $team['score'] }} PTS</span>
        </div>

        <!-- 4 Columns of 40 points = 160 Total Point slots -->
        <div class="grid grid-cols-4 gap-2 print:gap-1 text-[9px] print:text-[7.5px] font-mono">
            @for ($col = 0; $col < 4; $col++)
                @php
                    $startPt = ($col * 40) + 1;
                    $endPt = ($col + 1) * 40;
                @endphp
                <div class="border border-black">
                    <div class="grid grid-cols-10 gap-0.5 print:gap-px p-1 print:p-0.5 text-center">
                        @for ($pt = $startPt; $pt <= $endPt; $pt++)
                            @php
                                $ptData = $team['running_score'][$pt] ?? null;
                            @endphp
                            <div class="h-6 print:h-[15px] border {{ $ptData ? 'border-black bg-black text-white font-bold' : 'border-slate-300 text-slate-400 bg-white' }} flex flex-col items-center justify-center leading-none overflow-hidden relative" title="Point #{{ $pt }}{{ $ptData ? ' by #' . $ptData['jersey'] : '' }}">
                                <span class="text-[7px] print:text-[6px] leading-none {{ $ptData ? 'text-slate-300' : 'text-slate-400' }}">{{ $pt }}</span>
                                @if ($ptData)
                                    <span class="text-[8px] print:text-[6.5px] font-bold leading-none mt-0.5 print:mt-0">#{{ $ptData['jersey'] }}</span>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>
    </div>

    <!-- OFFICIALS CERTIFICATION & SIGNATURE FOOTER -->
    @php
        $signatures = $game->settings['signatures'] ?? [];
        $rolesFooter = [
            'official_scorer' => 'OFFICIAL SCORER',
            'referee' => 'REFEREE (CREW CHIEF)',
            'umpire1' => 'UMPIRE 1',
            'umpire2' => 'UMPIRE 2',
        ];
    @endphp
    <div class="ncaa-border-thick p-2.5 print:p-1.5 bg-slate-50 text-[10px] print:text-[8px] font-mono">
        <div class="grid grid-cols-4 gap-3 print:gap-2">
            @foreach ($rolesFooter as $roleKey => $roleTitle)
                @php
                    $sig = $signatures[$roleKey] ?? null;
                    $officialName = $officials[$roleKey] ?? ($sig['signer_name'] ?? '');
                @endphp
                <div class="flex flex-col justify-between h-full bg-white/70 print:bg-transparent p-1.5 print:p-0 rounded border border-slate-200 print:border-none">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600 font-bold block text-[9px] print:text-[7.5px]">{{ $roleTitle }}:</span>
                            @if ($sig)
                                <span class="text-[8px] print:text-[7px] text-emerald-700 font-bold flex items-center gap-0.5">
                                    <svg class="w-2.5 h-2.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span>SIGNED</span>
                                </span>
                            @endif
                        </div>
                        <span class="text-black font-bold block truncate text-[11px] print:text-[9px]">
                            {{ $officialName ?: '—' }}
                        </span>
                    </div>

                    <!-- Signature Display Area -->
                    <div class="my-1 min-h-[34px] print:min-h-[26px] flex items-center justify-center relative">
                        @if ($sig && !empty($sig['data']))
                            <div class="text-center w-full">
                                <img src="{{ $sig['data'] }}" alt="{{ $roleTitle }} Signature" class="max-h-[32px] print:max-h-[24px] max-w-full mx-auto object-contain">
                            </div>
                        @else
                            <div class="w-full text-center print:hidden">
                                <button type="button"
                                        wire:click="openSignatureModal('{{ $roleKey }}')"
                                        class="w-full py-1 px-1.5 rounded bg-amber-500/10 hover:bg-amber-500/20 text-amber-900 border border-dashed border-amber-500/40 text-[9px] font-bold transition flex items-center justify-center gap-1">
                                    <svg class="w-3 h-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    <span>✍️ Sign / Initial</span>
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Official Line & Metadata -->
                    <div>
                        <div class="border-b border-black h-0.5 w-full"></div>
                        <div class="flex items-center justify-between text-[8px] print:text-[7px] text-slate-500 mt-0.5">
                            @if ($sig)
                                <span>{{ $sig['signed_at'] ?? 'Certified' }}</span>
                                <div class="space-x-1 print:hidden">
                                    <button type="button" wire:click="openSignatureModal('{{ $roleKey }}')" class="text-blue-600 hover:underline">Edit</button>
                                    <span>&bull;</span>
                                    <button type="button" wire:click="clearSignature('{{ $roleKey }}')" class="text-rose-600 hover:underline">Clear</button>
                                </div>
                            @else
                                <span>Official Signature</span>
                                <span class="print:hidden">Unsigned</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-1.5 print:mt-1 pt-1 border-t border-slate-300 flex items-center justify-between text-[9px] print:text-[7.5px] text-slate-500">
            <span>I have verified and approved this official scorebook as the true record of the contest.</span>
            <span>Generated by ProKeeper Digital Scorebook System &bull; {{ date('Y-m-d H:i:s') }}</span>
        </div>
    </div>
</div>

