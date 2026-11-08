<?php

namespace App\Http\Controllers;

use App\Mail\GameSharedMail;
use App\Models\Game;
use App\Models\GameLineup;
use App\Models\GamePreset;
use App\Models\Team;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function create()
    {
        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('error', 'Please verify your email address before creating a new game.');
        }

        $teams = Team::where('organization_id', $user->organization_id)->get();
        $presets = GamePreset::where('organization_id', $user->organization_id)
            ->latest()
            ->get();

        return view('games.create', compact('teams', 'presets'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('error', 'Please verify your email address before creating a new game.');
        }

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
            'preset' => ['nullable', 'string', 'max:100'],
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
        $game = Game::where('uuid', $uuid)->with('gameShares.sharedWithUser')->firstOrFail();
        $user = Auth::user();

        if (! $game->canBeOperatedBy($user)) {
            abort(403, 'You do not have operator authorization for this game.');
        }

        if ($game->sport === 'volleyball') {
            return view('games.operator-volleyball', compact('game'));
        }

        return view('games.operator-basketball', compact('game'));
    }

    public function shareGame(Request $request, string $uuid)
    {
        $user = Auth::user();
        $game = Game::where('uuid', $uuid)->firstOrFail();

        if (! $game->canBeOperatedBy($user)) {
            abort(403, 'Only game operators can share access.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'permission' => ['required', 'in:view,edit'],
            'notify' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $targetUser = \App\Models\User::where('email', $validated['email'])->first();

        \App\Models\GameShare::updateOrCreate(
            [
                'game_id' => $game->id,
                'shared_with_email' => $validated['email'],
            ],
            [
                'shared_with_user_id' => $targetUser?->id,
                'permission' => $validated['permission'],
                'invited_by_user_id' => $user->id,
            ]
        );

        if ($request->boolean('notify', true)) {
            try {
                Mail::to($validated['email'])->send(new GameSharedMail(
                    $user,
                    $game,
                    $validated['permission'],
                    $validated['message'] ?? null
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', "Game access shared with {$validated['email']}.");
    }

    public function removeGameShare(string $uuid, int $shareId)
    {
        $user = Auth::user();
        $game = Game::where('uuid', $uuid)->firstOrFail();

        if (! $game->canBeOperatedBy($user)) {
            abort(403);
        }

        $share = \App\Models\GameShare::where('game_id', $game->id)->findOrFail($shareId);
        $share->delete();

        return back()->with('success', 'Game share access removed.');
    }

    public function exportNcaaPdf(Request $request, string $code)
    {
        $game = Game::where('access_code', $code)->orWhere('uuid', $code)->firstOrFail();

        if (! $game->canBeViewedBy(Auth::user(), $request->query('token'), 'scorebook')) {
            abort(403, 'Unauthorized. Please scan the QR code or use the official link from the operator.');
        }

        $isVolleyball = ($game->sport === 'volleyball');
        $isQuarters = ($game->period_format !== 'halves' && ! $isVolleyball);

        $events = $game->events()->where('is_undone', false)->orderBy('sequence', 'asc')->get();
        $homeLineup = GameLineup::where('game_id', $game->id)->where('team_side', 'home')->orderBy('is_starter', 'desc')->orderBy('jersey_number', 'asc')->get();
        $awayLineup = GameLineup::where('game_id', $game->id)->where('team_side', 'away')->orderBy('is_starter', 'desc')->orderBy('jersey_number', 'asc')->get();

        if ($isVolleyball) {
            $homeStats = $game->volleyballStats()->where('team_side', 'home')->get();
            $awayStats = $game->volleyballStats()->where('team_side', 'away')->get();
            $homeStatsKeyed = $homeStats->keyBy('jersey_number');
            $awayStatsKeyed = $awayStats->keyBy('jersey_number');
            $home = $this->compilePdfVolleyballScorebookData('home', $homeLineup, $homeStatsKeyed, $events, $game);
            $away = $this->compilePdfVolleyballScorebookData('away', $awayLineup, $awayStatsKeyed, $events, $game);
        } else {
            $homeStats = $game->basketballStats()->where('team_side', 'home')->get();
            $awayStats = $game->basketballStats()->where('team_side', 'away')->get();
            $homeStatsKeyed = $homeStats->keyBy('jersey_number');
            $awayStatsKeyed = $awayStats->keyBy('jersey_number');
            $home = $this->compilePdfTeamStats('home', $homeLineup, $homeStatsKeyed, $events);
            $away = $this->compilePdfTeamStats('away', $awayLineup, $awayStatsKeyed, $events);
        }

        $pdf = Pdf::loadView('pdf.ncaa-scorebook', compact('game', 'homeStats', 'awayStats', 'events', 'home', 'away', 'isVolleyball', 'isQuarters'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("ProKeeper-Scorebook-{$game->access_code}.pdf");
    }

    public function exportCoachStatsPdf(Request $request, string $code)
    {
        $game = Game::where('access_code', $code)->orWhere('uuid', $code)->firstOrFail();

        if (! $game->canBeViewedBy(Auth::user(), $request->query('token'), 'stats')) {
            abort(403, 'Unauthorized. Please scan the QR code or use the official link from the operator.');
        }

        $isVolleyball = ($game->sport === 'volleyball');
        $isQuarters = ($game->period_format !== 'halves' && ! $isVolleyball);

        $events = $game->events()->where('is_undone', false)->orderBy('sequence', 'asc')->get();
        $homeLineup = GameLineup::where('game_id', $game->id)->where('team_side', 'home')->orderBy('is_starter', 'desc')->orderBy('jersey_number', 'asc')->get();
        $awayLineup = GameLineup::where('game_id', $game->id)->where('team_side', 'away')->orderBy('is_starter', 'desc')->orderBy('jersey_number', 'asc')->get();

        if ($isVolleyball) {
            $maxPeriod = max(3, (int) $game->current_period, count($game->home_period_scores ?? []));
            $homeOverallStats = $game->volleyballStats()->where('team_side', 'home')->get()->keyBy('jersey_number');
            $awayOverallStats = $game->volleyballStats()->where('team_side', 'away')->get()->keyBy('jersey_number');

            $homePeriodScores = $game->home_period_scores ?? [];
            $awayPeriodScores = $game->away_period_scores ?? [];
            $periods = [];
            $homeSetsWon = 0;
            $awaySetsWon = 0;

            for ($p = 1; $p <= $maxPeriod; $p++) {
                $hPts = (int) ($homePeriodScores[$p - 1] ?? $events->where('team_side', 'home')->where('period', $p)->sum('points'));
                $aPts = (int) ($awayPeriodScores[$p - 1] ?? $events->where('team_side', 'away')->where('period', $p)->sum('points'));
                $target = ($p === 5) ? 15 : 25;
                if ($hPts >= $target && ($hPts - $aPts) >= 2) {
                    $homeSetsWon++;
                } elseif ($aPts >= $target && ($aPts - $hPts) >= 2) {
                    $awaySetsWon++;
                }
                $periods[] = [
                    'period' => $p,
                    'label' => "Set {$p}",
                    'home_pts' => $hPts,
                    'away_pts' => $aPts,
                ];
            }

            $lineScore = [
                'periods' => $periods,
                'home_sets_won' => $homeSetsWon,
                'away_sets_won' => $awaySetsWon,
                'home_total' => array_sum($homePeriodScores) ?: $events->where('team_side', 'home')->sum('points'),
                'away_total' => array_sum($awayPeriodScores) ?: $events->where('team_side', 'away')->sum('points'),
            ];

            $home = $this->compilePdfVolleyballStats('home', $homeLineup, $homeOverallStats, $events);
            $away = $this->compilePdfVolleyballStats('away', $awayLineup, $awayOverallStats, $events);
        } else {
            $maxPeriod = max($isQuarters ? 4 : 2, (int) $game->current_period);
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
        }

        $homeBreakdown = $game->calculateTimeoutsBreakdown('home');
        $awayBreakdown = $game->calculateTimeoutsBreakdown('away');

        $pdf = Pdf::loadView('pdf.coach-stats', compact('game', 'lineScore', 'home', 'away', 'homeBreakdown', 'awayBreakdown', 'isVolleyball', 'isQuarters'))
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
            'swat' => 0, 'to' => 0, 'to_pass' => 0, 'to_fumble' => 0, 'to_violation' => 0,
            'pf' => 0, 'off_fouls' => 0, 'tf' => 0, 'flag_fouls' => 0, 'fouls_drawn' => 0, 'tot_fouls' => 0,
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
                $reb = (int) $st->reb ?: ($oreb + $dreb);
                $ast = (int) $st->ast;
                $stl = (int) $st->stl;
                $blk = (int) $st->blk;
                $swat = (int) ($st->swat ?? 0);
                $toPass = (int) ($st->to_pass ?? 0);
                $toFumble = (int) ($st->to_fumble ?? 0);
                $toViolation = (int) ($st->to_violation ?? 0);
                $to = (int) $st->turnovers ?: ($toPass + $toFumble + $toViolation);
                $pf = (int) $st->fouls_pers;
                $offFouls = (int) $st->fouls_off;
                $tf = (int) $st->fouls_tech;
                $flagFouls = (int) ($st->fouls_flagrant ?? 0);
                $foulsDrawn = (int) ($st->fouls_forced ?? 0);
                $totFouls = (int) $st->total_fouls ?: ($pf + $offFouls + $tf + $flagFouls);
                $pts = (int) $st->points;
            } else {
                $pEvents = $events->where('jersey_number', $j);
                $fg2m = $pEvents->filter(fn ($e) => in_array($e->action_code, ['X', '2P', '2P_FAST', '2P_SECOND']) || $e->action_type === '2pt_make')->count();
                $fg2a_miss = $pEvents->filter(fn ($e) => in_array($e->action_code, ['Z', '2P_MISS']) || $e->action_type === '2pt_miss')->count();
                $fg2a = $fg2m + $fg2a_miss;
                $fg3m = $pEvents->filter(fn ($e) => in_array($e->action_code, ['M', '3P']) || $e->action_type === '3pt_make')->count();
                $fg3a_miss = $pEvents->filter(fn ($e) => in_array($e->action_code, ['N', '3P_MISS']) || $e->action_type === '3pt_miss')->count();
                $fg3a = $fg3m + $fg3a_miss;
                $fgm = $fg2m + $fg3m;
                $fga = $fg2a + $fg3a;
                $ftm = $pEvents->filter(fn ($e) => in_array($e->action_code, ['B', 'FT_MADE']) || $e->action_type === 'ft_make')->count();
                $fta_miss = $pEvents->filter(fn ($e) => in_array($e->action_code, ['V', 'FT_MISSED']) || $e->action_type === 'ft_miss')->count();
                $fta = $ftm + $fta_miss;
                $oreb = $pEvents->filter(fn ($e) => in_array($e->action_code, ['O', 'OREB']) || $e->action_type === 'off_reb')->count();
                $dreb = $pEvents->filter(fn ($e) => in_array($e->action_code, ['D', 'DREB']) || $e->action_type === 'def_reb')->count();
                $reb = $oreb + $dreb;
                $ast = $pEvents->filter(fn ($e) => in_array($e->action_code, ['A', 'AST']) || $e->action_type === 'assist')->count();
                $stl = $pEvents->filter(fn ($e) => in_array($e->action_code, ['S', 'STL']) || $e->action_type === 'steal')->count();
                $blk = $pEvents->filter(fn ($e) => in_array($e->action_code, ['K', 'BLK']) || $e->action_type === 'block')->count();
                $swat = $pEvents->filter(fn ($e) => in_array($e->action_code, ['W', 'SWAT']) || $e->action_type === 'swat')->count();
                $toPass = $pEvents->filter(fn ($e) => $e->action_code === 'P' || $e->action_type === 'turnover_pass')->count();
                $toFumble = $pEvents->filter(fn ($e) => $e->action_code === 'U' || $e->action_type === 'turnover_fumble')->count();
                $toViolation = $pEvents->filter(fn ($e) => $e->action_code === 'I' || $e->action_type === 'turnover_violation')->count();
                $to = $toPass + $toFumble + $toViolation + $pEvents->filter(fn ($e) => $e->action_code === 'TO')->count();
                $pf = $pEvents->filter(fn ($e) => in_array($e->action_code, ['F', 'PF']) || $e->action_type === 'foul_pers')->count();
                $offFouls = $pEvents->filter(fn ($e) => in_array($e->action_code, ['R', 'OF']) || $e->action_type === 'foul_off')->count();
                $tf = $pEvents->filter(fn ($e) => in_array($e->action_code, ['T', 'TF']) || $e->action_type === 'foul_tech')->count();
                $flagFouls = $pEvents->filter(fn ($e) => in_array($e->action_code, ['G', 'FL', 'FLAG']) || $e->action_type === 'foul_flagrant')->count();
                $foulsDrawn = $pEvents->filter(fn ($e) => in_array($e->action_code, ['H', 'FD']) || $e->action_type === 'foul_drawn')->count();
                $totFouls = $pf + $offFouls + $tf + $flagFouls;
                $pts = ($fg2m * 2) + ($fg3m * 3) + $ftm;
            }

            $fgPct = $fga > 0 ? round(($fgm / $fga) * 100, 1) : 0.0;
            $fg2Pct = $fg2a > 0 ? round(($fg2m / $fg2a) * 100, 1) : 0.0;
            $fg3Pct = $fg3a > 0 ? round(($fg3m / $fg3a) * 100, 1) : 0.0;
            $ftPct = $fta > 0 ? round(($ftm / $fta) * 100, 1) : 0.0;
            $efgPct = $fga > 0 ? round((($fgm + (0.5 * $fg3m)) / $fga) * 100, 1) : 0.0;

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
                'efg_pct' => $efgPct,
                'oreb' => $oreb,
                'dreb' => $dreb,
                'reb' => $reb,
                'ast' => $ast,
                'stl' => $stl,
                'blk' => $blk,
                'swat' => $swat,
                'to' => $to,
                'to_pass' => $toPass,
                'to_fumble' => $toFumble,
                'to_violation' => $toViolation,
                'pf' => $pf,
                'off_fouls' => $offFouls,
                'tf' => $tf,
                'flag_fouls' => $flagFouls,
                'fouls_drawn' => $foulsDrawn,
                'tot_fouls' => $totFouls,
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
            $teamTotals['swat'] += $swat;
            $teamTotals['to'] += $to;
            $teamTotals['to_pass'] += $toPass;
            $teamTotals['to_fumble'] += $toFumble;
            $teamTotals['to_violation'] += $toViolation;
            $teamTotals['pf'] += $pf;
            $teamTotals['off_fouls'] += $offFouls;
            $teamTotals['tf'] += $tf;
            $teamTotals['flag_fouls'] += $flagFouls;
            $teamTotals['fouls_drawn'] += $foulsDrawn;
            $teamTotals['tot_fouls'] += $totFouls;
        }

        $teamTotals['fg_pct'] = $teamTotals['fga'] > 0 ? round(($teamTotals['fgm'] / $teamTotals['fga']) * 100, 1) : 0.0;
        $teamTotals['fg2_pct'] = $teamTotals['fg2a'] > 0 ? round(($teamTotals['fg2m'] / $teamTotals['fg2a']) * 100, 1) : 0.0;
        $teamTotals['fg3_pct'] = $teamTotals['fg3a'] > 0 ? round(($teamTotals['fg3m'] / $teamTotals['fg3a']) * 100, 1) : 0.0;
        $teamTotals['ft_pct'] = $teamTotals['fta'] > 0 ? round(($teamTotals['ftm'] / $teamTotals['fta']) * 100, 1) : 0.0;
        $teamTotals['efg_pct'] = $teamTotals['fga'] > 0 ? round((($teamTotals['fgm'] + (0.5 * $teamTotals['fg3m'])) / $teamTotals['fga']) * 100, 1) : 0.0;
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

    protected function compilePdfVolleyballStats(string $teamSide, $lineup, $overallStats, $allEvents): array
    {
        $events = $allEvents->where('team_side', $teamSide);
        $players = [];
        $teamTotals = [
            'sp' => 0, 'pts' => 0.0, 'kills' => 0, 'attack_errors' => 0, 'attack_attempts' => 0,
            'assists' => 0, 'service_aces' => 0, 'service_errors' => 0, 'service_attempts' => 0,
            'digs' => 0, 'block_solos' => 0, 'block_assists' => 0, 'total_blocks' => 0.0,
            'ball_handling_errors' => 0, 'reception_errors' => 0,
        ];

        foreach ($lineup as $lp) {
            $j = (string) $lp->jersey_number;
            $st = $overallStats[$j] ?? null;

            if ($st) {
                $sp = (int) $st->sets_played;
                $kills = (int) $st->kills;
                $attackErrors = (int) $st->attack_errors;
                $attackAttempts = (int) $st->attack_attempts;
                $hittingPct = (float) $st->hitting_percentage;
                $assists = (int) $st->assists;
                $serviceAces = (int) $st->service_aces;
                $serviceErrors = (int) $st->service_errors;
                $serviceAttempts = (int) $st->service_attempts;
                $digs = (int) $st->digs;
                $blockSolos = (int) $st->block_solos;
                $blockAssists = (int) $st->block_assists;
                $totalBlocks = (float) $st->total_blocks;
                $bhe = (int) $st->ball_handling_errors;
                $re = (int) $st->reception_errors;
                $totalPts = (float) $st->total_points;
            } else {
                $pEvents = $events->where('jersey_number', $j);
                $kills = $pEvents->filter(fn ($e) => $e->action_code === 'K' || $e->action_type === 'kill')->count();
                $attackErrors = $pEvents->filter(fn ($e) => $e->action_code === 'E' || $e->action_type === 'attack_error')->count();
                $attackAttempts = $pEvents->filter(fn ($e) => in_array($e->action_code, ['K', 'E', 'T']))->count();
                $hittingPct = $attackAttempts > 0 ? round(($kills - $attackErrors) / $attackAttempts, 3) : 0.000;
                $assists = $pEvents->filter(fn ($e) => $e->action_code === 'Z' || $e->action_type === 'set_assist')->count();
                $serviceAces = $pEvents->filter(fn ($e) => $e->action_code === 'A' || $e->action_type === 'service_ace')->count();
                $serviceErrors = $pEvents->filter(fn ($e) => $e->action_code === 'S' || $e->action_type === 'service_error')->count();
                $serviceAttempts = $pEvents->filter(fn ($e) => in_array($e->action_code, ['A', 'S']))->count();
                $digs = $pEvents->filter(fn ($e) => $e->action_code === 'D' || $e->action_type === 'dig')->count();
                $blockSolos = $pEvents->filter(fn ($e) => $e->action_code === 'B' || $e->action_type === 'block_solo')->count();
                $blockAssists = $pEvents->filter(fn ($e) => $e->action_code === 'C' || $e->action_type === 'block_assist')->count();
                $totalBlocks = $blockSolos + ($blockAssists * 0.5);
                $bhe = $pEvents->filter(fn ($e) => $e->action_code === 'H' || $e->action_type === 'ball_handling_error')->count();
                $re = $pEvents->filter(fn ($e) => $e->action_code === 'R' || $e->action_type === 'reception_error')->count();
                $totalPts = $kills + $serviceAces + $blockSolos + ($blockAssists * 0.5);
                $sp = $pEvents->count() > 0 ? 1 : 0;
            }

            $players[] = [
                'jersey' => $j,
                'name' => $lp->player_name,
                'position' => $lp->position ?: '—',
                'is_starter' => (bool) $lp->is_starter,
                'sp' => $sp,
                'pts' => $totalPts,
                'kills' => $kills,
                'attack_errors' => $attackErrors,
                'attack_attempts' => $attackAttempts,
                'hitting_pct' => $hittingPct,
                'hitting_pct_str' => sprintf('%+.3f', $hittingPct),
                'assists' => $assists,
                'service_aces' => $serviceAces,
                'service_errors' => $serviceErrors,
                'service_attempts' => $serviceAttempts,
                'digs' => $digs,
                'block_solos' => $blockSolos,
                'block_assists' => $blockAssists,
                'total_blocks' => $totalBlocks,
                'bhe' => $bhe,
                're' => $re,
            ];

            $teamTotals['sp'] = max($teamTotals['sp'], $sp);
            $teamTotals['pts'] += $totalPts;
            $teamTotals['kills'] += $kills;
            $teamTotals['attack_errors'] += $attackErrors;
            $teamTotals['attack_attempts'] += $attackAttempts;
            $teamTotals['assists'] += $assists;
            $teamTotals['service_aces'] += $serviceAces;
            $teamTotals['service_errors'] += $serviceErrors;
            $teamTotals['service_attempts'] += $serviceAttempts;
            $teamTotals['digs'] += $digs;
            $teamTotals['block_solos'] += $blockSolos;
            $teamTotals['block_assists'] += $blockAssists;
            $teamTotals['total_blocks'] += $totalBlocks;
            $teamTotals['ball_handling_errors'] += $bhe;
            $teamTotals['reception_errors'] += $re;
        }

        $teamTotals['hitting_pct'] = $teamTotals['attack_attempts'] > 0
            ? round(($teamTotals['kills'] - $teamTotals['attack_errors']) / $teamTotals['attack_attempts'], 3)
            : 0.000;
        $teamTotals['hitting_pct_str'] = sprintf('%+.3f', $teamTotals['hitting_pct']);

        return [
            'side' => $teamSide,
            'players' => $players,
            'totals' => $teamTotals,
        ];
    }

    protected function compilePdfVolleyballScorebookData(string $teamSide, $lineup, $overallStats, $allEvents, Game $game): array
    {
        return $this->compilePdfVolleyballStats($teamSide, $lineup, $overallStats, $allEvents);
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
