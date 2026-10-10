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
            font-size: 8.5px;
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
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }
        .header-subtitle {
            font-size: 9px;
            color: #475569;
            margin-top: 2px;
        }
        .header-code {
            text-align: right;
            font-family: Courier, monospace;
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .header-status {
            text-align: right;
            font-size: 8.5px;
            color: #475569;
        }
        .line-score-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8.5px;
        }
        .line-score-table th, .line-score-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 5px;
            text-align: center;
        }
        .line-score-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #334155;
            font-size: 8px;
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
            background-color: #0f172a;
            color: #ffffff;
            padding: 4px 8px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            border-radius: 2px;
        }
        .box-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8px;
        }
        .box-table th, .box-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            text-align: center;
        }
        .box-table th {
            background-color: #e2e8f0;
            color: #1e293b;
            font-weight: bold;
            font-size: 7.5px;
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
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 8px;
        }
        .summary-label {
            color: #64748b;
            font-size: 7.5px;
            text-transform: uppercase;
            display: block;
        }
        .summary-val {
            font-weight: bold;
            color: #0f172a;
            font-size: 9px;
        }
        .page-break {
            page-break-before: always;
        }
        .footer-note {
            margin-top: 8px;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- PAGE 1: HOME TEAM COACH SHEET -->
    <div class="header-bar">
        <table class="header-table">
            <tr>
                <td style="width: 70%;">
                    <div class="header-title">ProKeeper Basketball Coach Report &bull; HOME TEAM</div>
                    <div class="header-subtitle">
                        <strong>{{ $game->home_display_name }}</strong> vs <strong>{{ $game->away_display_name }}</strong> &nbsp;|&nbsp;
                        {{ $game->scheduled_at ? $game->scheduled_at->format('M j, Y - g:i A') : now()->format('M j, Y') }} &nbsp;|&nbsp;
                        {{ $game->venue ?: 'Main Court' }} {{ !empty(data_get($game->settings, 'event_name')) ? '&bull; '.data_get($game->settings, 'event_name') : '' }}
                    </div>
                </td>
                <td style="width: 30%;">
                    <div class="header-code">GAME CODE: {{ $game->access_code }}</div>
                    <div class="header-status">Score: <strong>{{ $game->home_score }} - {{ $game->away_score }}</strong> ({{ $game->period_name }})</div>
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
                <th style="width: 45px;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background-color: #f0fdf4;">
                <td class="team-cell">{{ $game->home_display_name }} (HOME)</td>
                @foreach ($lineScore['periods'] as $p)
                    <td>{{ $p['home_pts'] }}</td>
                @endforeach
                <td class="total-cell">{{ $lineScore['home_total'] }}</td>
            </tr>
            <tr>
                <td class="team-cell">{{ $game->away_display_name }} (AWAY)</td>
                @foreach ($lineScore['periods'] as $p)
                    <td>{{ $p['away_pts'] }}</td>
                @endforeach
                <td class="total-cell">{{ $lineScore['away_total'] }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Home Team Banner -->
    <div class="team-banner" style="background-color: #1e3a8a;">
        HOME TEAM: {{ $game->home_display_name }} &nbsp;&bull;&nbsp; Total Points: {{ $home['totals']['pts'] }}
    </div>

    <!-- Home Team Box Score Table -->
    <table class="box-table">
        <thead>
            <tr>
                <th style="width: 25px;">#</th>
                <th style="width: 140px; text-align: left;">Player Name</th>
                <th style="width: 25px;">Pos</th>
                <th style="width: 30px; background-color: #fde68a; color: #78350f;">PTS</th>
                <th style="width: 45px;">FGM-A</th>
                <th style="width: 35px;">FG%</th>
                <th style="width: 45px;">2PM-A</th>
                <th style="width: 35px;">2P%</th>
                <th style="width: 45px;">3PM-A</th>
                <th style="width: 35px;">3P%</th>
                <th style="width: 45px;">FTM-A</th>
                <th style="width: 35px;">FT%</th>
                <th style="width: 25px;">OFF</th>
                <th style="width: 25px;">DEF</th>
                <th style="width: 28px; font-weight: bold;">REB</th>
                <th style="width: 25px;">AST</th>
                <th style="width: 25px;">STL</th>
                <th style="width: 25px;">BLK</th>
                <th style="width: 25px;">TO</th>
                <th style="width: 25px;">PF</th>
                <th style="width: 25px;">TF</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($home['players'] as $p)
                <tr>
                    <td class="jersey-cell">#{{ $p['jersey'] }}</td>
                    <td class="player-cell">
                        {{ $p['name'] }}
                        @if ($p['is_starter']) <span style="font-size: 7px; color: #1e40af; font-weight: bold;">[S]</span> @endif
                    </td>
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
                    <td>{{ $p['to'] }}</td>
                    <td>{{ $p['pf'] }}</td>
                    <td>{{ $p['tf'] ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: left; padding-left: 8px;">TEAM TOTALS</td>
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
                <td>{{ $home['totals']['to'] }}</td>
                <td>{{ $home['totals']['pf'] }}</td>
                <td>{{ $home['totals']['tf'] }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Specialty & Team Analytics Breakdown -->
    <table class="summary-grid">
        <tr>
            <td>
                <span class="summary-label">Starters Points</span>
                <span class="summary-val">{{ $home['starter_pts'] }}</span>
            </td>
            <td>
                <span class="summary-label">Bench Points</span>
                <span class="summary-val">{{ $home['bench_pts'] }}</span>
            </td>
            <td>
                <span class="summary-label">Rebounds (Off / Def)</span>
                <span class="summary-val">{{ $home['totals']['reb'] }} ({{ $home['totals']['oreb'] }} / {{ $home['totals']['dreb'] }})</span>
            </td>
            <td>
                <span class="summary-label">Turnovers & Fouls</span>
                <span class="summary-val">{{ $home['totals']['to'] }} TO &bull; {{ $home['totals']['pf'] }} PF</span>
            </td>
            <td>
                <span class="summary-label">Timeouts Remaining</span>
                <span class="summary-val">{{ $game->home_timeouts_remaining }} ({{ $homeBreakdown['rem_full'] }} Full, {{ $homeBreakdown['rem_30s'] }} 30s)</span>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Page 1 of 2 &bull; ProKeeper Athletic Statistics System &bull; Generated {{ now()->format('m/d/Y g:i A') }}
    </div>

    <!-- PAGE 2 BREAK: AWAY TEAM COACH SHEET -->
    <div class="page-break"></div>

    <div class="header-bar">
        <table class="header-table">
            <tr>
                <td style="width: 70%;">
                    <div class="header-title">ProKeeper Basketball Coach Report &bull; VISITING TEAM</div>
                    <div class="header-subtitle">
                        <strong>{{ $game->away_display_name }}</strong> vs <strong>{{ $game->home_display_name }}</strong> &nbsp;|&nbsp;
                        {{ $game->scheduled_at ? $game->scheduled_at->format('M j, Y - g:i A') : now()->format('M j, Y') }} &nbsp;|&nbsp;
                        {{ $game->venue ?: 'Main Court' }} {{ !empty(data_get($game->settings, 'event_name')) ? '&bull; '.data_get($game->settings, 'event_name') : '' }}
                    </div>
                </td>
                <td style="width: 30%;">
                    <div class="header-code">GAME CODE: {{ $game->access_code }}</div>
                    <div class="header-status">Score: <strong>{{ $game->home_score }} - {{ $game->away_score }}</strong> ({{ $game->period_name }})</div>
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
                <th style="width: 45px;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="team-cell">{{ $game->home_display_name }} (HOME)</td>
                @foreach ($lineScore['periods'] as $p)
                    <td>{{ $p['home_pts'] }}</td>
                @endforeach
                <td class="total-cell">{{ $lineScore['home_total'] }}</td>
            </tr>
            <tr style="background-color: #fff1f2;">
                <td class="team-cell">{{ $game->away_display_name }} (AWAY)</td>
                @foreach ($lineScore['periods'] as $p)
                    <td>{{ $p['away_pts'] }}</td>
                @endforeach
                <td class="total-cell">{{ $lineScore['away_total'] }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Away Team Banner -->
    <div class="team-banner" style="background-color: #991b1b;">
        VISITING TEAM: {{ $game->away_display_name }} &nbsp;&bull;&nbsp; Total Points: {{ $away['totals']['pts'] }}
    </div>

    <!-- Away Team Box Score Table -->
    <table class="box-table">
        <thead>
            <tr>
                <th style="width: 25px;">#</th>
                <th style="width: 140px; text-align: left;">Player Name</th>
                <th style="width: 25px;">Pos</th>
                <th style="width: 30px; background-color: #fde68a; color: #78350f;">PTS</th>
                <th style="width: 45px;">FGM-A</th>
                <th style="width: 35px;">FG%</th>
                <th style="width: 45px;">2PM-A</th>
                <th style="width: 35px;">2P%</th>
                <th style="width: 45px;">3PM-A</th>
                <th style="width: 35px;">3P%</th>
                <th style="width: 45px;">FTM-A</th>
                <th style="width: 35px;">FT%</th>
                <th style="width: 25px;">OFF</th>
                <th style="width: 25px;">DEF</th>
                <th style="width: 28px; font-weight: bold;">REB</th>
                <th style="width: 25px;">AST</th>
                <th style="width: 25px;">STL</th>
                <th style="width: 25px;">BLK</th>
                <th style="width: 25px;">TO</th>
                <th style="width: 25px;">PF</th>
                <th style="width: 25px;">TF</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($away['players'] as $p)
                <tr>
                    <td class="jersey-cell">#{{ $p['jersey'] }}</td>
                    <td class="player-cell">
                        {{ $p['name'] }}
                        @if ($p['is_starter']) <span style="font-size: 7px; color: #991b1b; font-weight: bold;">[S]</span> @endif
                    </td>
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
                    <td>{{ $p['to'] }}</td>
                    <td>{{ $p['pf'] }}</td>
                    <td>{{ $p['tf'] ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: left; padding-left: 8px;">TEAM TOTALS</td>
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
                <td>{{ $away['totals']['to'] }}</td>
                <td>{{ $away['totals']['pf'] }}</td>
                <td>{{ $away['totals']['tf'] }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Specialty & Team Analytics Breakdown -->
    <table class="summary-grid">
        <tr>
            <td>
                <span class="summary-label">Starters Points</span>
                <span class="summary-val">{{ $away['starter_pts'] }}</span>
            </td>
            <td>
                <span class="summary-label">Bench Points</span>
                <span class="summary-val">{{ $away['bench_pts'] }}</span>
            </td>
            <td>
                <span class="summary-label">Rebounds (Off / Def)</span>
                <span class="summary-val">{{ $away['totals']['reb'] }} ({{ $away['totals']['oreb'] }} / {{ $away['totals']['dreb'] }})</span>
            </td>
            <td>
                <span class="summary-label">Turnovers & Fouls</span>
                <span class="summary-val">{{ $away['totals']['to'] }} TO &bull; {{ $away['totals']['pf'] }} PF</span>
            </td>
            <td>
                <span class="summary-label">Timeouts Remaining</span>
                <span class="summary-val">{{ $game->away_timeouts_remaining }} ({{ $awayBreakdown['rem_full'] }} Full, {{ $awayBreakdown['rem_30s'] }} 30s)</span>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Page 2 of 2 &bull; ProKeeper Athletic Statistics System &bull; Generated {{ now()->format('m/d/Y g:i A') }}
    </div>

</body>
</html>
