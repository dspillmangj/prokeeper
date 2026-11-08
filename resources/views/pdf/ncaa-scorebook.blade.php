<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official ProKeeper Scorebook - {{ $game->access_code }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 15px 18px;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #111;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 3px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 6px;
            font-size: 8.5px;
        }
        table.stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8px;
        }
        table.stats-table th, table.stats-table td {
            border: 1px solid #000;
            padding: 2.5px 3px;
            text-align: center;
        }
        table.stats-table th {
            background-color: #eee;
            font-weight: bold;
            font-size: 7.5px;
            text-transform: uppercase;
        }
        table.stats-table td.left {
            text-align: left;
        }
        .section-header {
            background-color: #0f172a;
            color: #fff;
            padding: 3px 6px;
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .footer-signatures {
            width: 100%;
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 6px;
            font-size: 7.5px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    @php
        $officials = $game->settings['officials'] ?? [];
        $signatures = $game->settings['signatures'] ?? [];
        $homeBreakdown = $game->calculateTimeoutsBreakdown('home');
        $awayBreakdown = $game->calculateTimeoutsBreakdown('away');
        $sportTitle = !empty($isVolleyball) ? 'Volleyball' : 'Basketball';
    @endphp

    <!-- ==================================================== -->
    <!-- PAGE 1: HOME TEAM OFFICIAL SCOREBOOK -->
    <!-- ==================================================== -->
    <div class="header">
        <div class="title">Official {{ $sportTitle }} Scorebook (ProKeeper Systems)</div>
        <table class="info-grid">
            <tr>
                <td style="width: 35%;"><strong>HOME TEAM:</strong> {{ $game->home_display_name }} ({{ $game->home_score }} PTS)</td>
                <td style="width: 30%; text-align: center;">
                    <strong>Date:</strong> {{ $game->scheduled_at?->format('m/d/Y') ?? date('m/d/Y') }} &bull;
                    <strong>Venue:</strong> {{ $game->venue ?: 'Main Court' }}
                </td>
                <td style="width: 35%; text-align: right;"><strong>VISITING TEAM:</strong> {{ $game->away_display_name }} ({{ $game->away_score }} PTS)</td>
            </tr>
        </table>
    </div>

    @if (!empty($isVolleyball))
        <!-- Home Team Volleyball Scoresheet -->
        <div class="section-header">HOME TEAM: {{ $game->home_display_name }} &bull; Sets Won: {{ $home['sets_won'] ?? 0 }} &bull; Match Points: {{ $game->home_score }}</div>
        <table class="stats-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 130px;" class="left">Player Name</th>
                    <th style="width: 25px;">Pos</th>
                    <th style="width: 22px;">SP</th>
                    <th style="width: 25px;">K</th>
                    <th style="width: 25px;">E</th>
                    <th style="width: 25px;">TA</th>
                    <th style="width: 38px;">HIT%</th>
                    <th style="width: 25px;">AST</th>
                    <th style="width: 25px;">ACE</th>
                    <th style="width: 25px;">SE</th>
                    <th style="width: 25px;">DIG</th>
                    <th style="width: 25px;">BS</th>
                    <th style="width: 25px;">BA</th>
                    <th style="width: 28px; font-weight: bold;">TB</th>
                    <th style="width: 25px;">BHE</th>
                    <th style="width: 25px;">RE</th>
                    <th style="width: 32px; font-weight: bold; background-color: #ddd;">PTS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($home['players'] as $p)
                    <tr>
                        <td>#{{ $p['jersey'] }}</td>
                        <td class="left">{{ $p['name'] }} @if ($p['is_starter']) [S] @endif</td>
                        <td>{{ $p['position'] }}</td>
                        <td>{{ $p['sp'] }}</td>
                        <td>{{ $p['kills'] }}</td>
                        <td>{{ $p['attack_errors'] }}</td>
                        <td>{{ $p['attack_attempts'] }}</td>
                        <td>{{ $p['hitting_pct_str'] }}</td>
                        <td>{{ $p['assists'] }}</td>
                        <td>{{ $p['service_aces'] }}</td>
                        <td>{{ $p['service_errors'] }}</td>
                        <td>{{ $p['digs'] }}</td>
                        <td>{{ $p['block_solos'] }}</td>
                        <td>{{ $p['block_assists'] }}</td>
                        <td style="font-weight: bold;">{{ $p['total_blocks'] }}</td>
                        <td>{{ $p['bhe'] }}</td>
                        <td>{{ $p['re'] }}</td>
                        <td style="font-weight: bold; background-color: #eee;">{{ $p['total_points'] ?? $p['pts'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Home Team Basketball Scoresheet -->
        <div class="section-header">HOME TEAM: {{ $game->home_display_name }} &bull; Timeouts Rem: {{ $game->home_timeouts_remaining }} &bull; Period Fouls: {{ $game->home_fouls_current_period }}</div>
        <table class="stats-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 130px;" class="left">Player Name</th>
                    <th style="width: 22px;">Pos</th>
                    <th style="width: 18px;">1</th>
                    <th style="width: 18px;">2</th>
                    <th style="width: 18px;">3</th>
                    <th style="width: 18px;">4</th>
                    <th style="width: 18px;">5</th>
                    <th style="width: 20px;">TF</th>
                    <th style="width: 38px;">FGM-A</th>
                    <th style="width: 38px;">3PM-A</th>
                    <th style="width: 38px;">FTM-A</th>
                    <th style="width: 25px;">REB</th>
                    <th style="width: 22px;">AST</th>
                    <th style="width: 22px;">STL</th>
                    <th style="width: 22px;">BLK</th>
                    <th style="width: 22px;">TO</th>
                    <th style="width: 32px; font-weight: bold; background-color: #ddd;">PTS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($home['players'] as $p)
                    <tr>
                        <td>#{{ $p['jersey'] }}</td>
                        <td class="left">{{ $p['name'] }} @if ($p['is_starter']) [S] @endif</td>
                        <td>{{ $p['position'] }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 1 ? 'X' : '1' }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 2 ? 'X' : '2' }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 3 ? 'X' : '3' }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 4 ? 'X' : '4' }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 5 ? 'X' : '5' }}</td>
                        <td>{{ !empty($p['tf_count']) ? $p['tf_count'] : '' }}</td>
                        <td>{{ $p['fg_str'] ?? '—' }}</td>
                        <td>{{ $p['fg3_str'] ?? '—' }}</td>
                        <td>{{ $p['ft_str'] ?? '—' }}</td>
                        <td>{{ $p['reb'] ?? 0 }}</td>
                        <td>{{ $p['ast'] ?? 0 }}</td>
                        <td>{{ $p['stl'] ?? 0 }}</td>
                        <td>{{ $p['blk'] ?? 0 }}</td>
                        <td>{{ $p['to'] ?? 0 }}</td>
                        <td style="font-weight: bold; background-color: #eee;">{{ $p['total_pts'] ?? $p['pts'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Officials Signatures -->
    <table class="footer-signatures">
        <tr>
            @if (!empty($isVolleyball))
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Official Scorer:</strong> {{ $officials['official_scorer'] ?? ($signatures['official_scorer']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['official_scorer']['data']))
                        <img src="{{ $signatures['official_scorer']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['official_scorer']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>First Referee (R1):</strong> {{ $officials['first_referee'] ?? ($signatures['first_referee']['signer_name'] ?? ($officials['referee'] ?? '—')) }}<br>
                    @if (!empty($signatures['first_referee']['data']) || !empty($signatures['referee']['data']))
                        <img src="{{ $signatures['first_referee']['data'] ?? $signatures['referee']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['first_referee']['signed_at'] ?? ($signatures['referee']['signed_at'] ?? 'Official Signature') }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Second Referee (R2):</strong> {{ $officials['second_referee'] ?? ($signatures['second_referee']['signer_name'] ?? ($officials['umpire1'] ?? '—')) }}<br>
                    @if (!empty($signatures['second_referee']['data']) || !empty($signatures['umpire1']['data']))
                        <img src="{{ $signatures['second_referee']['data'] ?? $signatures['umpire1']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['second_referee']['signed_at'] ?? ($signatures['umpire1']['signed_at'] ?? 'Official Signature') }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Libero Tracker:</strong> {{ $officials['libero_tracker'] ?? ($signatures['libero_tracker']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['libero_tracker']['data']))
                        <img src="{{ $signatures['libero_tracker']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['libero_tracker']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
            @else
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Official Scorer:</strong> {{ $officials['official_scorer'] ?? ($signatures['official_scorer']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['official_scorer']['data']))
                        <img src="{{ $signatures['official_scorer']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['official_scorer']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Referee:</strong> {{ $officials['referee'] ?? ($signatures['referee']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['referee']['data']))
                        <img src="{{ $signatures['referee']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['referee']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Umpire 1:</strong> {{ $officials['umpire1'] ?? ($signatures['umpire1']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['umpire1']['data']))
                        <img src="{{ $signatures['umpire1']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['umpire1']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Umpire 2:</strong> {{ $officials['umpire2'] ?? ($signatures['umpire2']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['umpire2']['data']))
                        <img src="{{ $signatures['umpire2']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['umpire2']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
            @endif
        </tr>
    </table>

    <!-- ==================================================== -->
    <!-- PAGE 2: VISITING TEAM OFFICIAL SCOREBOOK -->
    <!-- ==================================================== -->
    <div class="page-break"></div>

    <div class="header">
        <div class="title">Official {{ $sportTitle }} Scorebook (ProKeeper Systems)</div>
        <table class="info-grid">
            <tr>
                <td style="width: 35%;"><strong>VISITING TEAM:</strong> {{ $game->away_display_name }} ({{ $game->away_score }} PTS)</td>
                <td style="width: 30%; text-align: center;">
                    <strong>Date:</strong> {{ $game->scheduled_at?->format('m/d/Y') ?? date('m/d/Y') }} &bull;
                    <strong>Venue:</strong> {{ $game->venue ?: 'Main Court' }}
                </td>
                <td style="width: 35%; text-align: right;"><strong>HOME TEAM:</strong> {{ $game->home_display_name }} ({{ $game->home_score }} PTS)</td>
            </tr>
        </table>
    </div>

    @if (!empty($isVolleyball))
        <!-- Away Team Volleyball Scoresheet -->
        <div class="section-header">VISITING TEAM: {{ $game->away_display_name }} &bull; Sets Won: {{ $away['sets_won'] ?? 0 }} &bull; Match Points: {{ $game->away_score }}</div>
        <table class="stats-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 130px;" class="left">Player Name</th>
                    <th style="width: 25px;">Pos</th>
                    <th style="width: 22px;">SP</th>
                    <th style="width: 25px;">K</th>
                    <th style="width: 25px;">E</th>
                    <th style="width: 25px;">TA</th>
                    <th style="width: 38px;">HIT%</th>
                    <th style="width: 25px;">AST</th>
                    <th style="width: 25px;">ACE</th>
                    <th style="width: 25px;">SE</th>
                    <th style="width: 25px;">DIG</th>
                    <th style="width: 25px;">BS</th>
                    <th style="width: 25px;">BA</th>
                    <th style="width: 28px; font-weight: bold;">TB</th>
                    <th style="width: 25px;">BHE</th>
                    <th style="width: 25px;">RE</th>
                    <th style="width: 32px; font-weight: bold; background-color: #ddd;">PTS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($away['players'] as $p)
                    <tr>
                        <td>#{{ $p['jersey'] }}</td>
                        <td class="left">{{ $p['name'] }} @if ($p['is_starter']) [S] @endif</td>
                        <td>{{ $p['position'] }}</td>
                        <td>{{ $p['sp'] }}</td>
                        <td>{{ $p['kills'] }}</td>
                        <td>{{ $p['attack_errors'] }}</td>
                        <td>{{ $p['attack_attempts'] }}</td>
                        <td>{{ $p['hitting_pct_str'] }}</td>
                        <td>{{ $p['assists'] }}</td>
                        <td>{{ $p['service_aces'] }}</td>
                        <td>{{ $p['service_errors'] }}</td>
                        <td>{{ $p['digs'] }}</td>
                        <td>{{ $p['block_solos'] }}</td>
                        <td>{{ $p['block_assists'] }}</td>
                        <td style="font-weight: bold;">{{ $p['total_blocks'] }}</td>
                        <td>{{ $p['bhe'] }}</td>
                        <td>{{ $p['re'] }}</td>
                        <td style="font-weight: bold; background-color: #eee;">{{ $p['total_points'] ?? $p['pts'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <!-- Away Team Basketball Scoresheet -->
        <div class="section-header">VISITING TEAM: {{ $game->away_display_name }} &bull; Timeouts Rem: {{ $game->away_timeouts_remaining }} &bull; Period Fouls: {{ $game->away_fouls_current_period }}</div>
        <table class="stats-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 130px;" class="left">Player Name</th>
                    <th style="width: 22px;">Pos</th>
                    <th style="width: 18px;">1</th>
                    <th style="width: 18px;">2</th>
                    <th style="width: 18px;">3</th>
                    <th style="width: 18px;">4</th>
                    <th style="width: 18px;">5</th>
                    <th style="width: 20px;">TF</th>
                    <th style="width: 38px;">FGM-A</th>
                    <th style="width: 38px;">3PM-A</th>
                    <th style="width: 38px;">FTM-A</th>
                    <th style="width: 25px;">REB</th>
                    <th style="width: 22px;">AST</th>
                    <th style="width: 22px;">STL</th>
                    <th style="width: 22px;">BLK</th>
                    <th style="width: 22px;">TO</th>
                    <th style="width: 32px; font-weight: bold; background-color: #ddd;">PTS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($away['players'] as $p)
                    <tr>
                        <td>#{{ $p['jersey'] }}</td>
                        <td class="left">{{ $p['name'] }} @if ($p['is_starter']) [S] @endif</td>
                        <td>{{ $p['position'] }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 1 ? 'X' : '1' }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 2 ? 'X' : '2' }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 3 ? 'X' : '3' }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 4 ? 'X' : '4' }}</td>
                        <td>{{ ($p['pf_count'] ?? 0) >= 5 ? 'X' : '5' }}</td>
                        <td>{{ !empty($p['tf_count']) ? $p['tf_count'] : '' }}</td>
                        <td>{{ $p['fg_str'] ?? '—' }}</td>
                        <td>{{ $p['fg3_str'] ?? '—' }}</td>
                        <td>{{ $p['ft_str'] ?? '—' }}</td>
                        <td>{{ $p['reb'] ?? 0 }}</td>
                        <td>{{ $p['ast'] ?? 0 }}</td>
                        <td>{{ $p['stl'] ?? 0 }}</td>
                        <td>{{ $p['blk'] ?? 0 }}</td>
                        <td>{{ $p['to'] ?? 0 }}</td>
                        <td style="font-weight: bold; background-color: #eee;">{{ $p['total_pts'] ?? $p['pts'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Officials Signatures Page 2 -->
    <table class="footer-signatures">
        <tr>
            @if (!empty($isVolleyball))
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Official Scorer:</strong> {{ $officials['official_scorer'] ?? ($signatures['official_scorer']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['official_scorer']['data']))
                        <img src="{{ $signatures['official_scorer']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['official_scorer']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>First Referee (R1):</strong> {{ $officials['first_referee'] ?? ($signatures['first_referee']['signer_name'] ?? ($officials['referee'] ?? '—')) }}<br>
                    @if (!empty($signatures['first_referee']['data']) || !empty($signatures['referee']['data']))
                        <img src="{{ $signatures['first_referee']['data'] ?? $signatures['referee']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['first_referee']['signed_at'] ?? ($signatures['referee']['signed_at'] ?? 'Official Signature') }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Second Referee (R2):</strong> {{ $officials['second_referee'] ?? ($signatures['second_referee']['signer_name'] ?? ($officials['umpire1'] ?? '—')) }}<br>
                    @if (!empty($signatures['second_referee']['data']) || !empty($signatures['umpire1']['data']))
                        <img src="{{ $signatures['second_referee']['data'] ?? $signatures['umpire1']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['second_referee']['signed_at'] ?? ($signatures['umpire1']['signed_at'] ?? 'Official Signature') }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Libero Tracker:</strong> {{ $officials['libero_tracker'] ?? ($signatures['libero_tracker']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['libero_tracker']['data']))
                        <img src="{{ $signatures['libero_tracker']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['libero_tracker']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
            @else
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Official Scorer:</strong> {{ $officials['official_scorer'] ?? ($signatures['official_scorer']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['official_scorer']['data']))
                        <img src="{{ $signatures['official_scorer']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['official_scorer']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Referee:</strong> {{ $officials['referee'] ?? ($signatures['referee']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['referee']['data']))
                        <img src="{{ $signatures['referee']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['referee']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Umpire 1:</strong> {{ $officials['umpire1'] ?? ($signatures['umpire1']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['umpire1']['data']))
                        <img src="{{ $signatures['umpire1']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['umpire1']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
                <td style="width: 25%; vertical-align: bottom;">
                    <strong>Umpire 2:</strong> {{ $officials['umpire2'] ?? ($signatures['umpire2']['signer_name'] ?? '—') }}<br>
                    @if (!empty($signatures['umpire2']['data']))
                        <img src="{{ $signatures['umpire2']['data'] }}" style="max-height: 24px; max-width: 130px; display: block; margin-top: 2px;">
                    @else
                        <div style="border-bottom: 1px solid #000; height: 14px; margin-top: 4px;"></div>
                    @endif
                    <span style="font-size: 6.5px; color: #555;">{{ $signatures['umpire2']['signed_at'] ?? 'Official Signature' }}</span>
                </td>
            @endif
        </tr>
    </table>

</body>
</html>
