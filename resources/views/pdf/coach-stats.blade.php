<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ProKeeper Coach Stats - {{ $game->access_code }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 18px 20px;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #111827;
            margin: 0;
            padding: 0;
        }
        .header-bar {
            width: 100%;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header-table {
            width: 100%;
        }
        .header-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .header-subtitle {
            font-size: 8.5px;
            color: #475569;
            margin-top: 2px;
        }
        .header-code {
            text-align: right;
            font-family: Courier, monospace;
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .header-status {
            text-align: right;
            font-size: 8px;
            color: #475569;
        }
        .line-score-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8px;
        }
        .line-score-table th, .line-score-table td {
            border: 1px solid #cbd5e1;
            padding: 2.5px 4px;
            text-align: center;
        }
        .line-score-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #334155;
            font-size: 7.5px;
            text-transform: uppercase;
        }
        .line-score-table td.team-cell {
            text-align: left;
            font-weight: bold;
        }
        .line-score-table td.total-cell {
            font-weight: bold;
            background-color: #f8fafc;
        }
        .team-banner {
            color: #ffffff;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            border-radius: 2px;
        }
        .box-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 7.5px;
        }
        .box-table th, .box-table td {
            border: 1px solid #cbd5e1;
            padding: 2.5px 3px;
            text-align: center;
        }
        .box-table th {
            background-color: #e2e8f0;
            color: #1e293b;
            font-weight: bold;
            font-size: 7px;
            text-transform: uppercase;
        }
        .box-table td.player-cell {
            text-align: left;
            font-weight: 500;
        }
        .box-table td.jersey-cell {
            font-weight: bold;
            font-family: Courier, monospace;
        }
        .box-table td.pts-cell {
            font-weight: bold;
            background-color: #fef3c7;
            color: #92400e;
        }
        .box-table tfoot td {
            font-weight: bold;
            background-color: #f1f5f9;
            border-top: 2px solid #475569;
        }
        .box-table tfoot td.total-pts {
            background-color: #fde68a;
            color: #78350f;
            font-weight: bold;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }
        .summary-grid td {
            padding: 3px 6px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 7.5px;
        }
        .summary-label {
            color: #64748b;
            font-size: 7px;
            text-transform: uppercase;
            display: block;
        }
        .summary-val {
            font-weight: bold;
            color: #0f172a;
            font-size: 8.5px;
        }
        .page-break {
            page-break-before: always;
        }
        .footer-note {
            margin-top: 6px;
            font-size: 7px;
            color: #94a3b8;
            text-align: right;
        }
    </style>
</head>
<body>

    @php
        $sportName = !empty($isVolleyball) ? 'Volleyball' : 'Basketball';
    @endphp

    <!-- ==================================================== -->
    <!-- PAGE 1: HOME TEAM COACH SHEET -->
    <!-- ==================================================== -->
    <div class="header-bar">
        <table class="header-table">
            <tr>
                <td style="width: 70%;">
                    <div class="header-title">ProKeeper {{ $sportName }} Coach Report &bull; HOME TEAM</div>
                    <div class="header-subtitle">
                        <strong>{{ $game->home_display_name }}</strong> vs <strong>{{ $game->away_display_name }}</strong> &nbsp;|&nbsp;
                        {{ $game->scheduled_at ? $game->scheduled_at->format('M j, Y - g:i A') : now()->format('M j, Y') }} &nbsp;|&nbsp;
                        {{ $game->venue ?: 'Main Court' }}
                    </div>
                </td>
                <td style="width: 30%;">
                    <div class="header-code">GAME CODE: {{ $game->access_code }}</div>
                    <div class="header-status">
                        Score: <strong>{{ $game->home_score }} - {{ $game->away_score }}</strong>
                        @if (!empty($isVolleyball))
                            (Sets: {{ $lineScore['home_sets_won'] ?? 0 }} - {{ $lineScore['away_sets_won'] ?? 0 }})
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Line Score Strip -->
    <table class="line-score-table">
        <thead>
            <tr>
                <th style="width: 140px; text-align: left;">Team</th>
                @foreach ($lineScore['periods'] as $p)
                    <th>{{ $p['label'] }}</th>
                @endforeach
                @if (!empty($isVolleyball))
                    <th style="width: 45px;">SETS</th>
                @endif
                <th style="width: 45px;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #f0fdf4;">
                <td class="team-cell">{{ $game->home_display_name }} (HOME)</td>
                @foreach ($lineScore['periods'] as $p)
                    <td>{{ $p['home_pts'] }}</td>
                @endforeach
                @if (!empty($isVolleyball))
                    <td class="total-cell" style="font-weight: bold;">{{ $lineScore['home_sets_won'] ?? 0 }}</td>
                @endif
                <td class="total-cell">{{ $lineScore['home_total'] }}</td>
            </tr>
            <tr>
                <td class="team-cell">{{ $game->away_display_name }} (AWAY)</td>
                @foreach ($lineScore['periods'] as $p)
                    <td>{{ $p['away_pts'] }}</td>
                @endforeach
                @if (!empty($isVolleyball))
                    <td class="total-cell" style="font-weight: bold;">{{ $lineScore['away_sets_won'] ?? 0 }}</td>
                @endif
                <td class="total-cell">{{ $lineScore['away_total'] }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Home Team Banner -->
    <div class="team-banner" style="background-color: #1e3a8a;">
        HOME TEAM: {{ $game->home_display_name }} &nbsp;&bull;&nbsp; Total Points: {{ $home['totals']['pts'] }}
    </div>

    @if (!empty($isVolleyball))
        <!-- Home Team Volleyball Table -->
        <table class="box-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 130px; text-align: left;">Player Name</th>
                    <th style="width: 25px;">Pos</th>
                    <th style="width: 22px;">SP</th>
                    <th style="width: 28px; background-color: #fde68a; color: #78350f;">PTS</th>
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
                </tr>
            </thead>
            <tbody>
                @foreach ($home['players'] as $p)
                    <tr>
                        <td class="jersey-cell">#{{ $p['jersey'] }}</td>
                        <td class="player-cell">{{ $p['name'] }} @if ($p['is_starter']) [S] @endif</td>
                        <td>{{ $p['position'] }}</td>
                        <td>{{ $p['sp'] }}</td>
                        <td class="pts-cell">{{ $p['pts'] }}</td>
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
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: left; padding-left: 6px;">TEAM TOTALS</td>
                    <td>{{ $home['totals']['sp'] }}</td>
                    <td class="total-pts">{{ $home['totals']['pts'] }}</td>
                    <td>{{ $home['totals']['kills'] }}</td>
                    <td>{{ $home['totals']['attack_errors'] }}</td>
                    <td>{{ $home['totals']['attack_attempts'] }}</td>
                    <td>{{ $home['totals']['hitting_pct_str'] }}</td>
                    <td>{{ $home['totals']['assists'] }}</td>
                    <td>{{ $home['totals']['service_aces'] }}</td>
                    <td>{{ $home['totals']['service_errors'] }}</td>
                    <td>{{ $home['totals']['digs'] }}</td>
                    <td>{{ $home['totals']['block_solos'] }}</td>
                    <td>{{ $home['totals']['block_assists'] }}</td>
                    <td style="font-weight: bold;">{{ $home['totals']['total_blocks'] }}</td>
                    <td>{{ $home['totals']['ball_handling_errors'] }}</td>
                    <td>{{ $home['totals']['reception_errors'] }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <!-- Home Team Basketball Table -->
        <table class="box-table">
            <thead>
                <tr>
                    <th style="width: 20px;">#</th>
                    <th style="width: 110px; text-align: left;">Player Name</th>
                    <th style="width: 20px;">Pos</th>
                    <th style="width: 25px; background-color: #fde68a; color: #78350f;">PTS</th>
                    <th style="width: 38px;">FGM-A</th>
                    <th style="width: 28px;">FG%</th>
                    <th style="width: 38px;">2PM-A</th>
                    <th style="width: 28px;">2P%</th>
                    <th style="width: 38px;">3PM-A</th>
                    <th style="width: 28px;">3P%</th>
                    <th style="width: 38px;">FTM-A</th>
                    <th style="width: 28px;">FT%</th>
                    <th style="width: 20px;">OFF</th>
                    <th style="width: 20px;">DEF</th>
                    <th style="width: 22px; font-weight: bold;">REB</th>
                    <th style="width: 20px;">AST</th>
                    <th style="width: 20px;">STL</th>
                    <th style="width: 20px;">BLK</th>
                    <th style="width: 22px;" title="Swats / Deflections">SWAT</th>
                    <th style="width: 20px;">TO</th>
                    <th style="width: 18px;" title="TO Pass">P</th>
                    <th style="width: 18px;" title="TO Fumble">F</th>
                    <th style="width: 18px;" title="TO Viol">V</th>
                    <th style="width: 20px;">PF</th>
                    <th style="width: 18px;" title="Off Foul">OF</th>
                    <th style="width: 18px;" title="Tech Foul">TF</th>
                    <th style="width: 18px;" title="Flagrant Foul">FF</th>
                    <th style="width: 18px;" title="Fouls Drawn">FD</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($home['players'] as $p)
                    <tr>
                        <td class="jersey-cell">#{{ $p['jersey'] }}</td>
                        <td class="player-cell">{{ $p['name'] }} @if ($p['is_starter']) [S] @endif</td>
                        <td>{{ $p['position'] }}</td>
                        <td class="pts-cell">{{ $p['pts'] }}</td>
                        <td>{{ $p['fg_str'] }}</td>
                        <td>{{ $p['fga'] > 0 ? $p['fg_pct'].'%' : '—' }}</td>
                        <td>{{ $p['fg2_str'] }}</td>
                        <td>{{ $p['fg2a'] > 0 ? $p['fg2_pct'].'%' : '—' }}</td>
                        <td>{{ $p['fg3_str'] }}</td>
                        <td>{{ $p['fg3a'] > 0 ? $p['fg3_pct'].'%' : '—' }}</td>
                        <td>{{ $p['ft_str'] }}</td>
                        <td>{{ $p['fta'] > 0 ? $p['ft_pct'].'%' : '—' }}</td>
                        <td>{{ $p['oreb'] }}</td>
                        <td>{{ $p['dreb'] }}</td>
                        <td style="font-weight: bold;">{{ $p['reb'] }}</td>
                        <td>{{ $p['ast'] }}</td>
                        <td>{{ $p['stl'] }}</td>
                        <td>{{ $p['blk'] }}</td>
                        <td>{{ $p['swat'] ?? 0 }}</td>
                        <td>{{ $p['to'] }}</td>
                        <td>{{ $p['to_pass'] ?? 0 }}</td>
                        <td>{{ $p['to_fumble'] ?? 0 }}</td>
                        <td>{{ $p['to_violation'] ?? 0 }}</td>
                        <td>{{ $p['pf'] }}</td>
                        <td>{{ $p['off_fouls'] ?? 0 }}</td>
                        <td>{{ $p['tf'] ?: '—' }}</td>
                        <td>{{ $p['flag_fouls'] ?? 0 }}</td>
                        <td>{{ $p['fouls_drawn'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: left; padding-left: 6px;">TEAM TOTALS</td>
                    <td class="total-pts">{{ $home['totals']['pts'] }}</td>
                    <td>{{ $home['totals']['fg_str'] }}</td>
                    <td>{{ $home['totals']['fg_pct'] }}%</td>
                    <td>{{ $home['totals']['fg2_str'] }}</td>
                    <td>{{ $home['totals']['fg2_pct'] }}%</td>
                    <td>{{ $home['totals']['fg3_str'] }}</td>
                    <td>{{ $home['totals']['fg3_pct'] }}%</td>
                    <td>{{ $home['totals']['ft_str'] }}</td>
                    <td>{{ $home['totals']['ft_pct'] }}%</td>
                    <td>{{ $home['totals']['oreb'] }}</td>
                    <td>{{ $home['totals']['dreb'] }}</td>
                    <td style="font-weight: bold;">{{ $home['totals']['reb'] }}</td>
                    <td>{{ $home['totals']['ast'] }}</td>
                    <td>{{ $home['totals']['stl'] }}</td>
                    <td>{{ $home['totals']['blk'] }}</td>
                    <td>{{ $home['totals']['swat'] ?? 0 }}</td>
                    <td>{{ $home['totals']['to'] }}</td>
                    <td>{{ $home['totals']['to_pass'] ?? 0 }}</td>
                    <td>{{ $home['totals']['to_fumble'] ?? 0 }}</td>
                    <td>{{ $home['totals']['to_violation'] ?? 0 }}</td>
                    <td>{{ $home['totals']['pf'] }}</td>
                    <td>{{ $home['totals']['off_fouls'] ?? 0 }}</td>
                    <td>{{ $home['totals']['tf'] }}</td>
                    <td>{{ $home['totals']['flag_fouls'] ?? 0 }}</td>
                    <td>{{ $home['totals']['fouls_drawn'] ?? 0 }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer-note">
        ProKeeper Athletic Systems &bull; Page 1 of 2 (Home Team Report) &bull; Generated {{ date('Y-m-d H:i') }}
    </div>

    <!-- ==================================================== -->
    <!-- PAGE 2: VISITING TEAM COACH SHEET -->
    <!-- ==================================================== -->
    <div class="page-break"></div>

    <div class="header-bar">
        <table class="header-table">
            <tr>
                <td style="width: 70%;">
                    <div class="header-title">ProKeeper {{ $sportName }} Coach Report &bull; VISITING TEAM</div>
                    <div class="header-subtitle">
                        <strong>{{ $game->away_display_name }}</strong> at <strong>{{ $game->home_display_name }}</strong> &nbsp;|&nbsp;
                        {{ $game->scheduled_at ? $game->scheduled_at->format('M j, Y - g:i A') : now()->format('M j, Y') }} &nbsp;|&nbsp;
                        {{ $game->venue ?: 'Main Court' }}
                    </div>
                </td>
                <td style="width: 30%;">
                    <div class="header-code">GAME CODE: {{ $game->access_code }}</div>
                    <div class="header-status">
                        Score: <strong>{{ $game->home_score }} - {{ $game->away_score }}</strong>
                        @if (!empty($isVolleyball))
                            (Sets: {{ $lineScore['home_sets_won'] ?? 0 }} - {{ $lineScore['away_sets_won'] ?? 0 }})
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Away Team Banner -->
    <div class="team-banner" style="background-color: #991b1b;">
        VISITING TEAM: {{ $game->away_display_name }} &nbsp;&bull;&nbsp; Total Points: {{ $away['totals']['pts'] }}
    </div>

    @if (!empty($isVolleyball))
        <!-- Away Team Volleyball Table -->
        <table class="box-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 130px; text-align: left;">Player Name</th>
                    <th style="width: 25px;">Pos</th>
                    <th style="width: 22px;">SP</th>
                    <th style="width: 28px; background-color: #fde68a; color: #78350f;">PTS</th>
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
                </tr>
            </thead>
            <tbody>
                @foreach ($away['players'] as $p)
                    <tr>
                        <td class="jersey-cell">#{{ $p['jersey'] }}</td>
                        <td class="player-cell">{{ $p['name'] }} @if ($p['is_starter']) [S] @endif</td>
                        <td>{{ $p['position'] }}</td>
                        <td>{{ $p['sp'] }}</td>
                        <td class="pts-cell">{{ $p['pts'] }}</td>
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
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: left; padding-left: 6px;">TEAM TOTALS</td>
                    <td>{{ $away['totals']['sp'] }}</td>
                    <td class="total-pts">{{ $away['totals']['pts'] }}</td>
                    <td>{{ $away['totals']['kills'] }}</td>
                    <td>{{ $away['totals']['attack_errors'] }}</td>
                    <td>{{ $away['totals']['attack_attempts'] }}</td>
                    <td>{{ $away['totals']['hitting_pct_str'] }}</td>
                    <td>{{ $away['totals']['assists'] }}</td>
                    <td>{{ $away['totals']['service_aces'] }}</td>
                    <td>{{ $away['totals']['service_errors'] }}</td>
                    <td>{{ $away['totals']['digs'] }}</td>
                    <td>{{ $away['totals']['block_solos'] }}</td>
                    <td>{{ $away['totals']['block_assists'] }}</td>
                    <td style="font-weight: bold;">{{ $away['totals']['total_blocks'] }}</td>
                    <td>{{ $away['totals']['ball_handling_errors'] }}</td>
                    <td>{{ $away['totals']['reception_errors'] }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <!-- Away Team Basketball Table -->
        <table class="box-table">
            <thead>
                <tr>
                    <th style="width: 20px;">#</th>
                    <th style="width: 110px; text-align: left;">Player Name</th>
                    <th style="width: 20px;">Pos</th>
                    <th style="width: 25px; background-color: #fde68a; color: #78350f;">PTS</th>
                    <th style="width: 38px;">FGM-A</th>
                    <th style="width: 28px;">FG%</th>
                    <th style="width: 38px;">2PM-A</th>
                    <th style="width: 28px;">2P%</th>
                    <th style="width: 38px;">3PM-A</th>
                    <th style="width: 28px;">3P%</th>
                    <th style="width: 38px;">FTM-A</th>
                    <th style="width: 28px;">FT%</th>
                    <th style="width: 20px;">OFF</th>
                    <th style="width: 20px;">DEF</th>
                    <th style="width: 22px; font-weight: bold;">REB</th>
                    <th style="width: 20px;">AST</th>
                    <th style="width: 20px;">STL</th>
                    <th style="width: 20px;">BLK</th>
                    <th style="width: 22px;" title="Swats / Deflections">SWAT</th>
                    <th style="width: 20px;">TO</th>
                    <th style="width: 18px;" title="TO Pass">P</th>
                    <th style="width: 18px;" title="TO Fumble">F</th>
                    <th style="width: 18px;" title="TO Viol">V</th>
                    <th style="width: 20px;">PF</th>
                    <th style="width: 18px;" title="Off Foul">OF</th>
                    <th style="width: 18px;" title="Tech Foul">TF</th>
                    <th style="width: 18px;" title="Flagrant Foul">FF</th>
                    <th style="width: 18px;" title="Fouls Drawn">FD</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($away['players'] as $p)
                    <tr>
                        <td class="jersey-cell">#{{ $p['jersey'] }}</td>
                        <td class="player-cell">{{ $p['name'] }} @if ($p['is_starter']) [S] @endif</td>
                        <td>{{ $p['position'] }}</td>
                        <td class="pts-cell">{{ $p['pts'] }}</td>
                        <td>{{ $p['fg_str'] }}</td>
                        <td>{{ $p['fga'] > 0 ? $p['fg_pct'].'%' : '—' }}</td>
                        <td>{{ $p['fg2_str'] }}</td>
                        <td>{{ $p['fg2a'] > 0 ? $p['fg2_pct'].'%' : '—' }}</td>
                        <td>{{ $p['fg3_str'] }}</td>
                        <td>{{ $p['fg3a'] > 0 ? $p['fg3_pct'].'%' : '—' }}</td>
                        <td>{{ $p['ft_str'] }}</td>
                        <td>{{ $p['fta'] > 0 ? $p['ft_pct'].'%' : '—' }}</td>
                        <td>{{ $p['oreb'] }}</td>
                        <td>{{ $p['dreb'] }}</td>
                        <td style="font-weight: bold;">{{ $p['reb'] }}</td>
                        <td>{{ $p['ast'] }}</td>
                        <td>{{ $p['stl'] }}</td>
                        <td>{{ $p['blk'] }}</td>
                        <td>{{ $p['swat'] ?? 0 }}</td>
                        <td>{{ $p['to'] }}</td>
                        <td>{{ $p['to_pass'] ?? 0 }}</td>
                        <td>{{ $p['to_fumble'] ?? 0 }}</td>
                        <td>{{ $p['to_violation'] ?? 0 }}</td>
                        <td>{{ $p['pf'] }}</td>
                        <td>{{ $p['off_fouls'] ?? 0 }}</td>
                        <td>{{ $p['tf'] ?: '—' }}</td>
                        <td>{{ $p['flag_fouls'] ?? 0 }}</td>
                        <td>{{ $p['fouls_drawn'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: left; padding-left: 6px;">TEAM TOTALS</td>
                    <td class="total-pts">{{ $away['totals']['pts'] }}</td>
                    <td>{{ $away['totals']['fg_str'] }}</td>
                    <td>{{ $away['totals']['fg_pct'] }}%</td>
                    <td>{{ $away['totals']['fg2_str'] }}</td>
                    <td>{{ $away['totals']['fg2_pct'] }}%</td>
                    <td>{{ $away['totals']['fg3_str'] }}</td>
                    <td>{{ $away['totals']['fg3_pct'] }}%</td>
                    <td>{{ $away['totals']['ft_str'] }}</td>
                    <td>{{ $away['totals']['ft_pct'] }}%</td>
                    <td>{{ $away['totals']['oreb'] }}</td>
                    <td>{{ $away['totals']['dreb'] }}</td>
                    <td style="font-weight: bold;">{{ $away['totals']['reb'] }}</td>
                    <td>{{ $away['totals']['ast'] }}</td>
                    <td>{{ $away['totals']['stl'] }}</td>
                    <td>{{ $away['totals']['blk'] }}</td>
                    <td>{{ $away['totals']['swat'] ?? 0 }}</td>
                    <td>{{ $away['totals']['to'] }}</td>
                    <td>{{ $away['totals']['to_pass'] ?? 0 }}</td>
                    <td>{{ $away['totals']['to_fumble'] ?? 0 }}</td>
                    <td>{{ $away['totals']['to_violation'] ?? 0 }}</td>
                    <td>{{ $away['totals']['pf'] }}</td>
                    <td>{{ $away['totals']['off_fouls'] ?? 0 }}</td>
                    <td>{{ $away['totals']['tf'] }}</td>
                    <td>{{ $away['totals']['flag_fouls'] ?? 0 }}</td>
                    <td>{{ $away['totals']['fouls_drawn'] ?? 0 }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer-note">
        ProKeeper Athletic Systems &bull; Page 2 of 2 (Visiting Team Report) &bull; Generated {{ date('Y-m-d H:i') }}
    </div>

</body>
</html>
