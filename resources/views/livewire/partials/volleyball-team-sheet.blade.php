<div class="space-y-4 print:space-y-1.5">
    <!-- Top Official Banner -->
    <div class="ncaa-border-thick p-2.5 print:p-1.5 bg-slate-100 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <img src="{{ asset('images/logo-blue.svg') }}" alt="ProKeeper" class="h-6 print:h-5 w-auto shrink-0">
            <div>
                <div class="text-[10px] print:text-[8px] font-mono uppercase tracking-widest text-slate-600 font-bold leading-none">
                    PROKEEPER ATHLETIC SYSTEMS &bull; OFFICIAL VOLLEYBALL SCOREBOOK (BEST OF 5 SETS)
                </div>
                <h2 class="text-base print:text-sm font-black uppercase tracking-tight text-black mt-0.5 leading-none">
                    {{ $sheetTitle }} &mdash; <span class="{{ $isHome ? 'text-blue-900' : 'text-rose-900' }}">{{ $team['name'] }}</span>
                </h2>
            </div>
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
                    <strong class="text-slate-600">VENUE:</strong> {{ $game->venue ?: 'Main Court' }}
                </div>
                <div>
                    <strong class="text-slate-600">STATUS:</strong> {{ strtoupper($game->status ?? 'ACTIVE') }}
                </div>
            </div>
            @php
                $officials = $game->settings['officials'] ?? [];
            @endphp
            <div class="pt-1 border-t border-slate-300 text-[10px] print:text-[7.5px] text-slate-700 flex flex-wrap gap-x-3 print:gap-x-2">
                <span><strong>R1 (FIRST REF):</strong> {{ $officials['first_referee'] ?? ($officials['referee'] ?? '—') }}</span>
                <span><strong>R2 (SECOND REF):</strong> {{ $officials['second_referee'] ?? ($officials['umpire1'] ?? '—') }}</span>
                <span><strong>SCORER:</strong> {{ $officials['official_scorer'] ?? '—' }}</span>
                <span><strong>LIBERO TRACKER:</strong> {{ $officials['libero_tracker'] ?? ($officials['timer'] ?? '—') }}</span>
            </div>
        </div>

        <!-- Right: Set-by-Set Score Summary Box -->
        <div class="col-span-5 ncaa-border p-2 print:p-1">
            <div class="text-[10px] print:text-[8px] font-bold text-center uppercase tracking-wider bg-slate-200 py-0.5 border-b border-black mb-1 leading-none">
                Official Score by Sets
            </div>
            <table class="w-full text-center text-[10px] print:text-[8px]">
                <thead>
                    <tr class="font-bold text-slate-700 border-b border-slate-300">
                        <th class="py-0.5 text-left">Team</th>
                        <th>Set 1</th>
                        <th>Set 2</th>
                        <th>Set 3</th>
                        <th>Set 4</th>
                        <th>Set 5</th>
                        <th class="text-emerald-700 font-bold">SETS</th>
                        <th class="font-black text-black">PTS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="{{ $isHome ? 'font-black bg-blue-50' : '' }}">
                        <td class="text-left py-0.5 truncate max-w-[85px]">{{ $team['name'] }}</td>
                        @for ($s = 0; $s < 5; $s++)
                            <td>{{ $team['set_scores'][$s] ?? 0 }}</td>
                        @endfor
                        <td class="font-bold text-emerald-800">{{ $team['sets_won'] }}</td>
                        <td class="font-black text-xs print:text-[9px] text-black">{{ $team['score'] }}</td>
                    </tr>
                    <tr class="{{ !$isHome ? 'font-black bg-rose-50' : '' }}">
                        <td class="text-left py-0.5 truncate max-w-[85px]">{{ $oppTeam['name'] }}</td>
                        @for ($s = 0; $s < 5; $s++)
                            <td>{{ $oppTeam['set_scores'][$s] ?? 0 }}</td>
                        @endfor
                        <td class="font-bold text-emerald-800">{{ $oppTeam['sets_won'] }}</td>
                        <td class="font-black text-xs print:text-[9px] text-black">{{ $oppTeam['score'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Timeouts & Match Protocol Bar -->
    <div class="ncaa-border p-2 print:p-1 text-[10px] print:text-[8px] font-mono flex items-center justify-between bg-slate-50">
        <div>
            <strong class="text-slate-700">TIMEOUTS CHARGED:</strong>
            @forelse ($team['timeouts'] as $to)
                <span class="inline-block px-1.5 py-0.5 bg-slate-200 rounded border border-slate-400 text-[9px] print:text-[7.5px] font-bold mr-1">
                    Set {{ $to->period }} ({{ $to->home_score_after }}-{{ $to->away_score_after }})
                </span>
            @empty
                <span class="text-slate-500 font-sans italic">None charged</span>
            @endforelse
        </div>
        <div class="text-[9px] print:text-[7.5px] text-slate-600">
            Rules: 2 Timeouts per Set (75s) &bull; Sets 1-4 to 25 pts &bull; Set 5 to 15 pts &bull; Win by 2
        </div>
    </div>

    <!-- MAIN VOLLEYBALL ROSTER & OFFICIAL PLAYER STATISTICS LEDGER -->
    <div class="ncaa-border-thick">
        <table class="w-full text-left text-[10px] print:text-[7.5px] font-mono border-collapse ncaa-table">
            <thead>
                <tr class="bg-slate-200 text-black font-bold uppercase text-center border-b border-black">
                    <th class="p-1 print:p-0.5 w-6 text-center" rowspan="2">St</th>
                    <th class="p-1 print:p-0.5 w-7 text-center" rowspan="2">No.</th>
                    <th class="p-1 print:p-0.5 text-left min-w-[130px]" rowspan="2">Player Name</th>
                    <th class="p-1 print:p-0.5 w-7 text-center" rowspan="2">Pos</th>
                    <th class="p-1 print:p-0.5 w-7 text-center" rowspan="2">SP</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="4">Attack</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="1">Set</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="2">Serve</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="1">Defense</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="3">Block</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center" colspan="2">Errors</th>
                    <th class="p-1 print:p-0.5 border-l border-black text-center w-10 font-black bg-slate-300" rowspan="2">TOTAL PTS</th>
                </tr>
                <tr class="bg-slate-100 text-slate-800 text-[9px] print:text-[7px] font-bold text-center border-b border-black">
                    <!-- Attack -->
                    <th class="p-0.5 border-l border-black w-7">K</th>
                    <th class="p-0.5 w-7">E</th>
                    <th class="p-0.5 w-8">TA</th>
                    <th class="p-0.5 w-11 font-bold text-black bg-slate-200">HIT%</th>
                    <!-- Set -->
                    <th class="p-0.5 border-l border-black w-8">AST</th>
                    <!-- Serve -->
                    <th class="p-0.5 border-l border-black w-7">ACE</th>
                    <th class="p-0.5 w-7">SE</th>
                    <!-- Defense -->
                    <th class="p-0.5 border-l border-black w-8">DIG</th>
                    <!-- Block -->
                    <th class="p-0.5 border-l border-black w-7">BS</th>
                    <th class="p-0.5 w-7">BA</th>
                    <th class="p-0.5 w-9 font-bold bg-slate-200">TB</th>
                    <!-- Errors -->
                    <th class="p-0.5 border-l border-black w-7">BHE</th>
                    <th class="p-0.5 w-7">RE</th>
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
                    <tr class="h-6 print:h-[15px] border-b border-slate-300 hover:bg-slate-50">
                        <td class="text-center font-bold font-sans {{ $p && $p['is_starter'] ? 'bg-slate-100' : '' }}">
                            {{ $p ? ($p['is_starter'] ? 'X' : '') : '' }}
                        </td>
                        <td class="text-center font-bold text-black bg-slate-50">
                            {{ $p ? $p['jersey'] : '' }}
                        </td>
                        <td class="px-1.5 print:px-1 font-sans font-semibold text-black truncate max-w-[130px]">
                            {{ $p ? $p['name'] : '' }}
                        </td>
                        <td class="text-center text-slate-600">
                            {{ $p ? $p['position'] : '' }}
                        </td>
                        <td class="text-center font-bold text-slate-800">
                            {{ $p ? $p['sp'] : '' }}
                        </td>
                        <!-- K, E, TA, HIT% -->
                        <td class="text-center border-l border-black font-semibold">{{ $p ? $p['kills'] : '' }}</td>
                        <td class="text-center text-rose-800">{{ $p ? $p['attack_errors'] : '' }}</td>
                        <td class="text-center text-slate-700">{{ $p ? $p['attack_attempts'] : '' }}</td>
                        <td class="text-center font-bold bg-slate-100">{{ $p ? $p['hitting_pct_str'] : '' }}</td>
                        <!-- Set Assists -->
                        <td class="text-center border-l border-black font-semibold">{{ $p ? $p['assists'] : '' }}</td>
                        <!-- Serve ACE, SE -->
                        <td class="text-center border-l border-black font-bold text-amber-800">{{ $p ? $p['service_aces'] : '' }}</td>
                        <td class="text-center text-rose-800">{{ $p ? $p['service_errors'] : '' }}</td>
                        <!-- Digs -->
                        <td class="text-center border-l border-black font-semibold text-emerald-800">{{ $p ? $p['digs'] : '' }}</td>
                        <!-- Blocks BS, BA, TB -->
                        <td class="text-center border-l border-black">{{ $p ? $p['block_solos'] : '' }}</td>
                        <td class="text-center">{{ $p ? $p['block_assists'] : '' }}</td>
                        <td class="text-center font-bold bg-slate-100">{{ $p ? $p['total_blocks'] : '' }}</td>
                        <!-- Errors BHE, RE -->
                        <td class="text-center border-l border-black text-rose-800">{{ $p ? $p['bhe'] : '' }}</td>
                        <td class="text-center text-rose-800">{{ $p ? $p['re'] : '' }}</td>
                        <!-- Total Points -->
                        <td class="text-center border-l border-black font-black text-black bg-slate-200">
                            {{ $p ? $p['total_points'] : '' }}
                        </td>
                    </tr>
                @endfor

                <!-- TOTALS ROW -->
                @php
                    $totKills = collect($players)->sum('kills');
                    $totErrors = collect($players)->sum('attack_errors');
                    $totAtt = collect($players)->sum('attack_attempts');
                    $totHitPct = $totAtt > 0 ? round(($totKills - $totErrors) / $totAtt, 3) : 0.000;
                @endphp
                <tr class="bg-slate-200 text-black font-black border-t-2 border-black h-7 print:h-5">
                    <td colspan="5" class="px-2 print:px-1 uppercase tracking-wider text-right font-bold text-[10px] print:text-[7.5px]">
                        TEAM TOTALS:
                    </td>
                    <td class="text-center border-l border-black font-black">{{ $totKills }}</td>
                    <td class="text-center font-black text-rose-900">{{ $totErrors }}</td>
                    <td class="text-center font-black">{{ $totAtt }}</td>
                    <td class="text-center font-black bg-slate-300">{{ sprintf('%+.3f', $totHitPct) }}</td>
                    <td class="text-center border-l border-black font-black">{{ collect($players)->sum('assists') }}</td>
                    <td class="text-center border-l border-black font-black text-amber-900">{{ collect($players)->sum('service_aces') }}</td>
                    <td class="text-center font-black text-rose-900">{{ collect($players)->sum('service_errors') }}</td>
                    <td class="text-center border-l border-black font-black text-emerald-900">{{ collect($players)->sum('digs') }}</td>
                    <td class="text-center border-l border-black font-black">{{ collect($players)->sum('block_solos') }}</td>
                    <td class="text-center font-black">{{ collect($players)->sum('block_assists') }}</td>
                    <td class="text-center font-black bg-slate-300">{{ collect($players)->sum('total_blocks') }}</td>
                    <td class="text-center border-l border-black font-black text-rose-900">{{ collect($players)->sum('bhe') }}</td>
                    <td class="text-center font-black text-rose-900">{{ collect($players)->sum('re') }}</td>
                    <td class="text-center border-l border-black font-black bg-black text-white text-sm print:text-[9.5px]">
                        {{ $team['score'] }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- SET-BY-SET RUNNING POINT PROGRESSION (Sets 1 through 5) -->
    <div class="ncaa-border p-2 print:p-1 space-y-2">
        <div class="text-[10px] print:text-[8px] font-black uppercase tracking-wider text-black bg-slate-200 px-2 print:px-1 py-0.5 border-b border-black flex justify-between items-center leading-none">
            <span>Set-by-Set Official Running Score Progression</span>
            <span class="text-[9px] print:text-[7.5px] text-slate-600 font-mono">Total Match Points: {{ $team['score'] }} &bull; Sets Won: {{ $team['sets_won'] }}</span>
        </div>

        <div class="grid grid-cols-5 gap-2 print:gap-1 text-[9px] print:text-[7.5px] font-mono">
            @for ($s = 1; $s <= 5; $s++)
                @php
                    $targetPts = ($s === 5) ? 15 : 25;
                    $setRunning = $team['set_running_scores'][$s] ?? [];
                    $setScore = $team['set_scores'][$s - 1] ?? 0;
                @endphp
                <div class="border border-black p-1 print:p-0.5">
                    <div class="flex items-center justify-between border-b border-slate-300 pb-0.5 mb-1 text-[8.5px] print:text-[7px] font-bold">
                        <span>SET {{ $s }}</span>
                        <span class="text-black font-black">{{ $setScore }} pts</span>
                    </div>
                    <div class="grid grid-cols-5 gap-0.5 print:gap-px text-center">
                        @for ($pt = 1; $pt <= max($targetPts, $setScore); $pt++)
                            @php
                                $scored = isset($setRunning[$pt]);
                                $jNo = $setRunning[$pt]['jersey'] ?? null;
                            @endphp
                            <div class="h-5 print:h-[13px] border {{ $scored ? 'bg-black text-white font-bold border-black' : 'border-slate-300 text-slate-400 bg-white' }} flex flex-col items-center justify-center leading-none" title="Set {{ $s }} Point #{{ $pt }}{{ $jNo ? ' by #' . $jNo : '' }}">
                                <span class="text-[6px] print:text-[5px] leading-none {{ $scored ? 'text-slate-300' : 'text-slate-400' }}">{{ $pt }}</span>
                                @if ($scored && $jNo)
                                    <span class="text-[7px] print:text-[5.5px] font-bold leading-none mt-0.5">{{ $jNo === 'TM' ? 'TM' : '#' . $jNo }}</span>
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
        $volleyballRoles = [
            'official_scorer' => 'OFFICIAL SCORER',
            'first_referee' => 'FIRST REFEREE (R1)',
            'second_referee' => 'SECOND REFEREE (R2)',
            'libero_tracker' => 'LIBERO TRACKER',
        ];
    @endphp
    <div class="ncaa-border-thick p-2.5 print:p-1.5 bg-slate-50 text-[10px] print:text-[8px] font-mono">
        <div class="grid grid-cols-4 gap-3 print:gap-2">
            @foreach ($volleyballRoles as $roleKey => $roleTitle)
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
            <span>I have verified and approved this official volleyball scoresheet as the true record of the contest.</span>
            <span>Generated by ProKeeper Digital Scorebook System &bull; {{ date('Y-m-d H:i:s') }}</span>
        </div>
    </div>
</div>

