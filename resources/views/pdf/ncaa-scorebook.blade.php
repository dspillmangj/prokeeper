<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official ProKeeper Scorebook - {{ $game->access_code }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 15px;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #111;
            margin: 10px;
        }
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 4px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 10px;
        }
        table.stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        table.stats-table th, table.stats-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            text-align: center;
        }
        table.stats-table th {
            background-color: #eee;
            font-weight: bold;
        }
        table.stats-table td.left {
            text-align: left;
        }
        .section-header {
            background-color: #222;
            color: #fff;
            padding: 3px 5px;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .footer-signatures {
            width: 100%;
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Official Basketball Scorebook (ProKeeper Systems)</div>
        <table class="info-grid">
            <tr>
                <td><strong>Home Team:</strong> {{ $game->home_display_name }} ({{ $game->home_score }} PTS)</td>
                <td style="text-align: center;"><strong>Date:</strong> {{ $game->scheduled_at?->format('m/d/Y') ?? date('m/d/Y') }} &nbsp;|&nbsp; <strong>Format:</strong> {{ $game->period_format === 'halves' ? '2 Halves' : '4 Quarters' }}</td>
                <td style="text-align: right;"><strong>Visiting Team:</strong> {{ $game->away_display_name }} ({{ $game->away_score }} PTS)</td>
            </tr>
        </table>
    </div>

    @php
        $homeBreakdown = $game->home_timeouts_breakdown;
        $awayBreakdown = $game->away_timeouts_breakdown;
    @endphp

    <!-- Home Team Stats -->
    <div class="section-header">HOME TEAM: {{ $game->home_display_name }} &nbsp;|&nbsp; Timeouts: {{ $homeBreakdown['rem_full'] }} Full, {{ $homeBreakdown['rem_30s'] }} 30s remaining ({{ $game->home_timeouts_remaining }} Total)</div>
    <table class="stats-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 150px;" class="left">Player Name</th>
                <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                <th>TF</th>
                <th>2PM</th>
                <th>3PM</th>
                <th>FTM-A</th>
                <th>REB</th>
                <th>AST</th>
                <th>PTS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($homeStats as $s)
                <tr>
                    <td>#{{ $s->jersey_number }}</td>
                    <td class="left">{{ $s->player_name }}</td>
                    <td>{{ $s->total_fouls >= 1 ? 'X' : '' }}</td>
                    <td>{{ $s->total_fouls >= 2 ? 'X' : '' }}</td>
                    <td>{{ $s->total_fouls >= 3 ? 'X' : '' }}</td>
                    <td>{{ $s->total_fouls >= 4 ? 'X' : '' }}</td>
                    <td>{{ $s->total_fouls >= 5 ? 'X' : '' }}</td>
                    <td>{{ $s->fouls_tech ?: '' }}</td>
                    <td>{{ $s->fgm }}</td>
                    <td>{{ $s->fg3m }}</td>
                    <td>{{ $s->ftm }}-{{ $s->fta }}</td>
                    <td>{{ $s->reb }}</td>
                    <td>{{ $s->ast }}</td>
                    <td style="font-weight: bold;">{{ $s->points }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Away Team Stats -->
    <div class="section-header">VISITING TEAM: {{ $game->away_display_name }} &nbsp;|&nbsp; Timeouts: {{ $awayBreakdown['rem_full'] }} Full, {{ $awayBreakdown['rem_30s'] }} 30s remaining ({{ $game->away_timeouts_remaining }} Total)</div>
    <table class="stats-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 150px;" class="left">Player Name</th>
                <th>1</th><th>2</th><th>3</th><th>4</th><th>5</th>
                <th>TF</th>
                <th>2PM</th>
                <th>3PM</th>
                <th>FTM-A</th>
                <th>REB</th>
                <th>AST</th>
                <th>PTS</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($awayStats as $s)
                <tr>
                    <td>#{{ $s->jersey_number }}</td>
                    <td class="left">{{ $s->player_name }}</td>
                    <td>{{ $s->total_fouls >= 1 ? 'X' : '' }}</td>
                    <td>{{ $s->total_fouls >= 2 ? 'X' : '' }}</td>
                    <td>{{ $s->total_fouls >= 3 ? 'X' : '' }}</td>
                    <td>{{ $s->total_fouls >= 4 ? 'X' : '' }}</td>
                    <td>{{ $s->total_fouls >= 5 ? 'X' : '' }}</td>
                    <td>{{ $s->fouls_tech ?: '' }}</td>
                    <td>{{ $s->fgm }}</td>
                    <td>{{ $s->fg3m }}</td>
                    <td>{{ $s->ftm }}-{{ $s->fta }}</td>
                    <td>{{ $s->reb }}</td>
                    <td>{{ $s->ast }}</td>
                    <td style="font-weight: bold;">{{ $s->points }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $officials = $game->settings['officials'] ?? [];
        $signatures = $game->settings['signatures'] ?? [];
    @endphp
    <table class="footer-signatures">
        <tr>
            <td style="width: 25%; vertical-align: bottom;">
                <strong>Official Scorer:</strong> {{ $officials['official_scorer'] ?? ($signatures['official_scorer']['signer_name'] ?? '—') }}<br>
                @if (!empty($signatures['official_scorer']['data']))
                    <img src="{{ $signatures['official_scorer']['data'] }}" style="max-height: 28px; max-width: 140px; display: block; margin-top: 2px;">
                @else
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-top: 4px;"></div>
                @endif
                <span style="font-size: 7px; color: #555;">{{ $signatures['official_scorer']['signed_at'] ?? 'Official Signature' }}</span>
            </td>
            <td style="width: 25%; vertical-align: bottom;">
                <strong>Referee:</strong> {{ $officials['referee'] ?? ($signatures['referee']['signer_name'] ?? '—') }}<br>
                @if (!empty($signatures['referee']['data']))
                    <img src="{{ $signatures['referee']['data'] }}" style="max-height: 28px; max-width: 140px; display: block; margin-top: 2px;">
                @else
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-top: 4px;"></div>
                @endif
                <span style="font-size: 7px; color: #555;">{{ $signatures['referee']['signed_at'] ?? 'Official Signature' }}</span>
            </td>
            <td style="width: 25%; vertical-align: bottom;">
                <strong>Umpire 1:</strong> {{ $officials['umpire1'] ?? ($signatures['umpire1']['signer_name'] ?? '—') }}<br>
                @if (!empty($signatures['umpire1']['data']))
                    <img src="{{ $signatures['umpire1']['data'] }}" style="max-height: 28px; max-width: 140px; display: block; margin-top: 2px;">
                @else
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-top: 4px;"></div>
                @endif
                <span style="font-size: 7px; color: #555;">{{ $signatures['umpire1']['signed_at'] ?? 'Official Signature' }}</span>
            </td>
            <td style="width: 25%; vertical-align: bottom;">
                <strong>Umpire 2:</strong> {{ $officials['umpire2'] ?? ($signatures['umpire2']['signer_name'] ?? '—') }}<br>
                @if (!empty($signatures['umpire2']['data']))
                    <img src="{{ $signatures['umpire2']['data'] }}" style="max-height: 28px; max-width: 140px; display: block; margin-top: 2px;">
                @else
                    <div style="border-bottom: 1px solid #000; height: 16px; margin-top: 4px;"></div>
                @endif
                <span style="font-size: 7px; color: #555;">{{ $signatures['umpire2']['signed_at'] ?? 'Official Signature' }}</span>
            </td>
        </tr>
    </table>

</body>
</html>
