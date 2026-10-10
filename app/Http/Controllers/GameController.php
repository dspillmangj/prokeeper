<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameLineup;
use App\Models\Team;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $teams = Team::where('organization_id', $user->organization_id)->get();

        return view('games.create', compact('teams'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'sport' => ['required', 'in:basketball,volleyball'],
            'home_team_id' => ['nullable', 'exists:teams,id'],
            'home_team_name' => ['nullable', 'string', 'max:255'],
            'home_team_score_color' => ['nullable', 'string', 'max:20'],
            'away_team_id' => ['nullable', 'exists:teams,id'],
            'away_team_name' => ['nullable', 'string', 'max:255'],
            'away_team_score_color' => ['nullable', 'string', 'max:20'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'preset' => ['nullable', 'string', 'max:50'],
            'timeouts_full' => ['nullable', 'integer', 'min:0', 'max:10'],
            'timeouts_30s' => ['nullable', 'integer', 'min:0', 'max:10'],
            'timeouts_ot' => ['nullable', 'integer', 'min:0', 'max:5'],
            'period_format' => ['nullable', 'string', 'in:quarters,halves,sets'],
            'period_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'ot_minutes' => ['nullable', 'integer', 'min:1', 'max:30'],
            'shot_clock_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'bonus_foul_threshold' => ['nullable', 'integer', 'min:1', 'max:20'],
            'double_bonus_foul_threshold' => ['nullable', 'integer', 'min:1', 'max:20'],
            'player_foul_limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'timeouts_per_set' => ['nullable', 'integer', 'min:0', 'max:10'],
            'set_target_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'deciding_set_target_score' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $homeTeam = ! empty($validated['home_team_id']) ? Team::find($validated['home_team_id']) : null;
        $awayTeam = ! empty($validated['away_team_id']) ? Team::find($validated['away_team_id']) : null;

        $homeName = $homeTeam?->name ?? $validated['home_team_name'] ?? 'Home Team';
        $awayName = $awayTeam?->name ?? $validated['away_team_name'] ?? 'Away Team';

        $homeScoreColor = ! empty($validated['home_team_score_color'])
            ? $validated['home_team_score_color']
            : ($homeTeam?->home_jersey_color && $homeTeam->home_jersey_color !== '#ffffff' ? $homeTeam->home_jersey_color : '#1e40af');

        $awayScoreColor = ! empty($validated['away_team_score_color'])
            ? $validated['away_team_score_color']
            : ($awayTeam?->away_jersey_color && $awayTeam->away_jersey_color !== '#ffffff' ? $awayTeam->away_jersey_color : '#b91c1c');

        $sport = $validated['sport'];
        $periodFormat = $validated['period_format'] ?? ($sport === 'basketball' ? 'quarters' : 'sets');
        $periodMinutes = (int) ($validated['period_minutes'] ?? ($sport === 'basketball' ? 8 : 0));
        $initialClock = ($sport === 'basketball') ? ($periodMinutes * 60) : 0;

        $timeoutsFull = isset($validated['timeouts_full']) ? (int) $validated['timeouts_full'] : 3;
        $timeouts30s = isset($validated['timeouts_30s']) ? (int) $validated['timeouts_30s'] : 2;
        $timeoutsOt = isset($validated['timeouts_ot']) ? (int) $validated['timeouts_ot'] : 1;
        $timeoutsPerSet = isset($validated['timeouts_per_set']) ? (int) $validated['timeouts_per_set'] : 2;

        $totalInitialTimeouts = ($sport === 'basketball') ? ($timeoutsFull + $timeouts30s) : $timeoutsPerSet;

        $rules = [
            'preset' => $validated['preset'] ?? 'default',
            'timeouts_full' => $timeoutsFull,
            'timeouts_30s' => $timeouts30s,
            'timeouts_ot' => $timeoutsOt,
            'period_format' => $periodFormat,
            'period_minutes' => $periodMinutes,
            'ot_minutes' => (int) ($validated['ot_minutes'] ?? 4),
            'shot_clock_seconds' => ! empty($validated['shot_clock_seconds']) ? (int) $validated['shot_clock_seconds'] : null,
            'bonus_foul_threshold' => (int) ($validated['bonus_foul_threshold'] ?? ($periodFormat === 'halves' ? 7 : 5)),
            'double_bonus_foul_threshold' => (int) ($validated['double_bonus_foul_threshold'] ?? ($periodFormat === 'halves' ? 10 : 5)),
            'player_foul_limit' => (int) ($validated['player_foul_limit'] ?? 5),
            'timeouts_per_set' => $timeoutsPerSet,
            'set_target_score' => (int) ($validated['set_target_score'] ?? 25),
            'deciding_set_target_score' => (int) ($validated['deciding_set_target_score'] ?? 15),
        ];

        $settings = [
            'event_name' => $validated['event_name'] ?? null,
            'rules' => $rules,
            'period_minutes' => $periodMinutes,
            'timeouts_per_game' => $totalInitialTimeouts,
            'timeouts_per_set' => $timeoutsPerSet,
        ];

        $game = Game::create([
            'access_code' => strtoupper(Str::random(6)),
            'slug' => Str::slug($homeName).'-vs-'.Str::slug($awayName).'-'.strtolower(Str::random(4)),
            'organization_id' => $user->organization_id,
            'created_by_user_id' => $user->id,
            'sport' => $sport,
            'status' => 'in_progress',
            'home_team_id' => $homeTeam?->id,
            'home_team_name' => $homeName,
            'home_team_score_color' => $homeScoreColor,
            'away_team_id' => $awayTeam?->id,
            'away_team_name' => $awayName,
            'away_team_score_color' => $awayScoreColor,
            'current_period' => 1,
            'clock_seconds_remaining' => $initialClock,
            'home_score' => 0,
            'away_score' => 0,
            'home_timeouts_remaining' => $totalInitialTimeouts,
            'away_timeouts_remaining' => $totalInitialTimeouts,
            'venue' => $validated['venue'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
            'settings' => $settings,
        ]);

        // Copy roster into game lineups if team exists
        if ($homeTeam) {
            foreach ($homeTeam->rosterPlayers()->with('player')->get() as $idx => $rp) {
                GameLineup::create([
                    'game_id' => $game->id,
                    'team_side' => 'home',
                    'player_id' => $rp->player_id,
                    'jersey_number' => $rp->jersey_number,
                    'player_name' => $rp->player?->full_name ?? "Player #{$rp->jersey_number}",
                    'position' => $rp->position,
                    'is_starter' => $rp->is_starter,
                    'is_on_court' => ($validated['sport'] === 'basketball' ? ($idx < 5) : ($idx < 6)),
                    'court_position' => $idx + 1,
                    'is_libero' => $rp->is_libero,
                    'subbed_in_at_clock' => ($idx < 5 ? 480 : null),
                ]);
            }
        }

        if ($awayTeam) {
            foreach ($awayTeam->rosterPlayers()->with('player')->get() as $idx => $rp) {
                GameLineup::create([
                    'game_id' => $game->id,
                    'team_side' => 'away',
                    'player_id' => $rp->player_id,
                    'jersey_number' => $rp->jersey_number,
                    'player_name' => $rp->player?->full_name ?? "Player #{$rp->jersey_number}",
                    'position' => $rp->position,
                    'is_starter' => $rp->is_starter,
                    'is_on_court' => ($validated['sport'] === 'basketball' ? ($idx < 5) : ($idx < 6)),
                    'court_position' => $idx + 1,
                    'is_libero' => $rp->is_libero,
                    'subbed_in_at_clock' => ($idx < 5 ? 480 : null),
                ]);
            }
        }

        return redirect()->route('games.operator', $game->uuid);
    }

    public function operator(string $uuid)
    {
        $game = Game::where('uuid', $uuid)->firstOrFail();
        if ($game->sport === 'volleyball') {
            return view('games.operator-volleyball', compact('game'));
        }

        return view('games.operator-basketball', compact('game'));
    }

    public function exportNcaaPdf(string $code)
    {
        $game = Game::where('access_code', $code)->orWhere('uuid', $code)->firstOrFail();
        $homeStats = $game->basketballStats()->where('team_side', 'home')->get();
        $awayStats = $game->basketballStats()->where('team_side', 'away')->get();
        $events = $game->events()->where('is_undone', false)->get();

        $pdf = Pdf::loadView('pdf.ncaa-scorebook', compact('game', 'homeStats', 'awayStats', 'events'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("ProKeeper-Scorebook-{$game->access_code}.pdf");
    }

    public function exportCoachStatsPdf(string $code)
    {
        $game = Game::where('access_code', $code)->orWhere('uuid', $code)->firstOrFail();
        $isQuarters = ($game->period_format !== 'halves');
        $maxPeriod = max($isQuarters ? 4 : 2, (int) $game->current_period);

        $events = $game->events()->where('is_undone', false)->orderBy('sequence', 'asc')->get();
        $homeLineup = GameLineup::where('game_id', $game->id)->where('team_side', 'home')->orderBy('is_starter', 'desc')->orderBy('jersey_number', 'asc')->get();
        $awayLineup = GameLineup::where('game_id', $game->id)->where('team_side', 'away')->orderBy('is_starter', 'desc')->orderBy('jersey_number', 'asc')->get();
        $homeOverallStats = $game->basketballStats()->where('team_side', 'home')->get()->keyBy('jersey_number');
        $awayOverallStats = $game->basketballStats()->where('team_side', 'away')->get()->keyBy('jersey_number');

        // Compile Line Score
        $periods = [];
        $homeTotal = 0;
        $awayTotal = 0;
        for ($p = 1; $p <= $maxPeriod; $p++) {
            $pEvents = $events->where('period', $p);
            $hPts = $pEvents->where('team_side', 'home')->sum('points');
            $aPts = $pEvents->where('team_side', 'away')->sum('points');
            $homeTotal += $hPts;
            $awayTotal += $aPts;

            $label = $isQuarters
                ? (($p <= 4) ? "Q{$p}" : ($p === 5 ? 'OT' : 'OT'.($p - 4)))
                : (($p === 1) ? '1H' : (($p === 2) ? '2H' : ($p === 3 ? 'OT' : 'OT'.($p - 2))));

            $periods[] = [
                'period' => $p,
                'label' => $label,
                'home_pts' => $hPts,
                'away_pts' => $aPts,
            ];
        }

        $lineScore = [
            'periods' => $periods,
            'home_total' => $game->home_score ?: $homeTotal,
            'away_total' => $game->away_score ?: $awayTotal,
        ];

        $home = $this->compilePdfTeamStats('home', $homeLineup, $homeOverallStats, $events);
        $away = $this->compilePdfTeamStats('away', $awayLineup, $awayOverallStats, $events);

        $homeBreakdown = $game->calculateTimeoutsBreakdown('home');
        $awayBreakdown = $game->calculateTimeoutsBreakdown('away');

        $pdf = Pdf::loadView('pdf.coach-stats', compact('game', 'lineScore', 'home', 'away', 'homeBreakdown', 'awayBreakdown'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("ProKeeper-CoachStats-{$game->access_code}.pdf");
    }

    protected function compilePdfTeamStats(string $teamSide, $lineup, $overallStats, $allEvents): array
    {
        $events = $allEvents->where('team_side', $teamSide);
        $players = [];
        $teamTotals = [
            'pts' => 0, 'fgm' => 0, 'fga' => 0, 'fg2m' => 0, 'fg2a' => 0,
            'fg3m' => 0, 'fg3a' => 0, 'ftm' => 0, 'fta' => 0, 'oreb' => 0,
            'dreb' => 0, 'reb' => 0, 'ast' => 0, 'stl' => 0, 'blk' => 0,
            'to' => 0, 'pf' => 0, 'tf' => 0,
        ];
        $benchPts = 0;
        $starterPts = 0;

        foreach ($lineup as $lp) {
            $j = (string) $lp->jersey_number;
            $st = $overallStats[$j] ?? null;

            if ($st) {
                $fgm = (int) $st->fgm;
                $fga = (int) $st->fga;
                $fg3m = (int) $st->fg3m;
                $fg3a = (int) $st->fg3a;
                $fg2m = max(0, $fgm - $fg3m);
                $fg2a = max(0, $fga - $fg3a);
                $ftm = (int) $st->ftm;
                $fta = (int) $st->fta;
                $oreb = (int) $st->oreb;
                $dreb = (int) $st->dreb;
                $reb = (int) $st->reb;
                $ast = (int) $st->ast;
                $stl = (int) $st->stl;
                $blk = (int) ($st->blk + ($st->swat ?? 0));
                $to = (int) $st->turnovers;
                $pf = (int) ($st->fouls_pers + $st->fouls_off);
                $tf = (int) $st->fouls_tech;
                $pts = (int) $st->points;
            } else {
                $pEvents = $events->where('jersey_number', $j);
                $fg2m = $pEvents->whereIn('action_code', ['X', '2P', '2P_FAST', '2P_SECOND'])->count();
                $fg2a_miss = $pEvents->where('action_code', 'Z')->count();
                $fg2a = $fg2m + $fg2a_miss;
                $fg3m = $pEvents->whereIn('action_code', ['M', '3P'])->count();
                $fg3a_miss = $pEvents->where('action_code', 'N')->count();
                $fg3a = $fg3m + $fg3a_miss;
                $fgm = $fg2m + $fg3m;
                $fga = $fg2a + $fg3a;
                $ftm = $pEvents->whereIn('action_code', ['B', 'FT_MADE'])->count();
                $fta_miss = $pEvents->whereIn('action_code', ['V', 'FT_MISSED'])->count();
                $fta = $ftm + $fta_miss;
                $oreb = $pEvents->where('action_code', 'O')->count();
                $dreb = $pEvents->where('action_code', 'D')->count();
                $reb = $oreb + $dreb;
                $ast = $pEvents->where('action_code', 'A')->count();
                $stl = $pEvents->where('action_code', 'S')->count();
                $blk = $pEvents->whereIn('action_code', ['K', 'W'])->count();
                $to = $pEvents->whereIn('action_code', ['P', 'U', 'I', 'TO', 'R'])->count();
                $pf = $pEvents->whereIn('action_code', ['F', 'R'])->count();
                $tf = $pEvents->where('action_code', 'T')->count();
                $pts = ($fg2m * 2) + ($fg3m * 3) + $ftm;
            }

            $fgPct = $fga > 0 ? round(($fgm / $fga) * 100, 1) : 0.0;
            $fg2Pct = $fg2a > 0 ? round(($fg2m / $fg2a) * 100, 1) : 0.0;
            $fg3Pct = $fg3a > 0 ? round(($fg3m / $fg3a) * 100, 1) : 0.0;
            $ftPct = $fta > 0 ? round(($ftm / $fta) * 100, 1) : 0.0;

            if ($lp->is_starter) {
                $starterPts += $pts;
            } else {
                $benchPts += $pts;
            }

            $players[] = [
                'jersey' => $j,
                'name' => $lp->player_name,
                'position' => $lp->position ?: '—',
                'is_starter' => (bool) $lp->is_starter,
                'pts' => $pts,
                'fgm' => $fgm,
                'fga' => $fga,
                'fg_pct' => $fgPct,
                'fg_str' => "{$fgm}-{$fga}",
                'fg2m' => $fg2m,
                'fg2a' => $fg2a,
                'fg2_pct' => $fg2Pct,
                'fg2_str' => "{$fg2m}-{$fg2a}",
                'fg3m' => $fg3m,
                'fg3a' => $fg3a,
                'fg3_pct' => $fg3Pct,
                'fg3_str' => "{$fg3m}-{$fg3a}",
                'ftm' => $ftm,
                'fta' => $fta,
                'ft_pct' => $ftPct,
                'ft_str' => "{$ftm}-{$fta}",
                'oreb' => $oreb,
                'dreb' => $dreb,
                'reb' => $reb,
                'ast' => $ast,
                'stl' => $stl,
                'blk' => $blk,
                'to' => $to,
                'pf' => $pf,
                'tf' => $tf,
            ];

            $teamTotals['pts'] += $pts;
            $teamTotals['fgm'] += $fgm;
            $teamTotals['fga'] += $fga;
            $teamTotals['fg2m'] += $fg2m;
            $teamTotals['fg2a'] += $fg2a;
            $teamTotals['fg3m'] += $fg3m;
            $teamTotals['fg3a'] += $fg3a;
            $teamTotals['ftm'] += $ftm;
            $teamTotals['fta'] += $fta;
            $teamTotals['oreb'] += $oreb;
            $teamTotals['dreb'] += $dreb;
            $teamTotals['reb'] += $reb;
            $teamTotals['ast'] += $ast;
            $teamTotals['stl'] += $stl;
            $teamTotals['blk'] += $blk;
            $teamTotals['to'] += $to;
            $teamTotals['pf'] += $pf;
            $teamTotals['tf'] += $tf;
        }

        $teamTotals['fg_pct'] = $teamTotals['fga'] > 0 ? round(($teamTotals['fgm'] / $teamTotals['fga']) * 100, 1) : 0.0;
        $teamTotals['fg2_pct'] = $teamTotals['fg2a'] > 0 ? round(($teamTotals['fg2m'] / $teamTotals['fg2a']) * 100, 1) : 0.0;
        $teamTotals['fg3_pct'] = $teamTotals['fg3a'] > 0 ? round(($teamTotals['fg3m'] / $teamTotals['fg3a']) * 100, 1) : 0.0;
        $teamTotals['ft_pct'] = $teamTotals['fta'] > 0 ? round(($teamTotals['ftm'] / $teamTotals['fta']) * 100, 1) : 0.0;
        $teamTotals['fg_str'] = "{$teamTotals['fgm']}-{$teamTotals['fga']}";
        $teamTotals['fg2_str'] = "{$teamTotals['fg2m']}-{$teamTotals['fg2a']}";
        $teamTotals['fg3_str'] = "{$teamTotals['fg3m']}-{$teamTotals['fg3a']}";
        $teamTotals['ft_str'] = "{$teamTotals['ftm']}-{$teamTotals['fta']}";

        return [
            'side' => $teamSide,
            'players' => $players,
            'totals' => $teamTotals,
            'starter_pts' => $starterPts,
            'bench_pts' => $benchPts,
        ];
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $game = Game::where('organization_id', $user->organization_id)->findOrFail($id);
        $matchup = "{$game->home_display_name} vs {$game->away_display_name}";
        $game->delete();

        return redirect()->route('dashboard')->with('success', "Game '{$matchup}' deleted successfully.");
    }
}
