<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\VolleyballStat;
use Livewire\Component;

class CoachStats extends Component
{
    public ?string $code = null;

    public string $inputCode = '';

    public string $errorMessage = '';

    public string $selectedPeriod = 'all'; // 'all', '1', '2', '3', '4', '5' (OT / Set 5)

    public string $teamView = 'both'; // 'both', 'home', 'away'

    public ?string $token = null;

    protected $queryString = [
        'selectedPeriod' => ['except' => 'all', 'as' => 'period'],
        'teamView' => ['except' => 'both', 'as' => 'team'],
    ];

    public function mount(?string $code = null)
    {
        $this->code = $code ?: request()->route('code');
        $this->token = request()->query('token');
        if ($this->code) {
            $this->inputCode = strtoupper(trim($this->code));
        }

        $period = request()->query('period');
        if ($period !== null && in_array($period, ['all', '1', '2', '3', '4', '5', 'ot'])) {
            $this->selectedPeriod = $period === 'ot' ? '5' : $period;
        }
    }

    public function setPeriod(string $period)
    {
        $this->selectedPeriod = $period;
    }

    public function setTeamView(string $view)
    {
        if (in_array($view, ['both', 'home', 'away'])) {
            $this->teamView = $view;
        }
    }

    public function submitCode()
    {
        $clean = strtoupper(trim($this->inputCode));
        if (empty($clean)) {
            $this->errorMessage = 'Please enter a game access code.';

            return;
        }

        $game = Game::where('access_code', $clean)
            ->orWhere('uuid', $clean)
            ->orWhere('slug', $clean)
            ->first();

        if (! $game) {
            $this->errorMessage = "No game found with code '{$clean}'.";

            return;
        }

        return redirect()->route('public.stats', $game->access_code);
    }

    public function getGameProperty(): ?Game
    {
        if (! $this->code) {
            return null;
        }

        return Game::where('access_code', $this->code)
            ->orWhere('uuid', $this->code)
            ->orWhere('slug', $this->code)
            ->with(['homeTeam', 'awayTeam'])
            ->first();
    }

    public function render()
    {
        $game = $this->game;

        if (! $game) {
            return view('livewire.coach-stats', [
                'game' => null,
                'isRestricted' => false,
            ])->layout('layouts.public');
        }

        // Check tokenized / authenticated access
        $isAllowed = $game->canBeViewedBy(auth()->user(), $this->token, 'stats');
        if (! $isAllowed) {
            return view('livewire.coach-stats', [
                'game' => $game,
                'isRestricted' => true,
            ])->layout('layouts.public');
        }

        $isVolleyball = ($game->sport === 'volleyball');
        $isQuarters = ($game->period_format !== 'halves' && ! $isVolleyball);

        // Fetch non-undone game events sorted chronologically
        $allEvents = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->orderBy('sequence', 'asc')
            ->get();

        // Lineups
        $homeLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->orderBy('is_starter', 'desc')
            ->orderBy('jersey_number', 'asc')
            ->get();

        $awayLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->orderBy('is_starter', 'desc')
            ->orderBy('jersey_number', 'asc')
            ->get();

        if ($isVolleyball) {
            $maxPeriod = max(3, (int) $game->current_period, count($game->home_period_scores ?? []));

            $homeOverallStats = VolleyballStat::where('game_id', $game->id)
                ->where('team_side', 'home')
                ->get()
                ->keyBy('jersey_number');

            $awayOverallStats = VolleyballStat::where('game_id', $game->id)
                ->where('team_side', 'away')
                ->get()
                ->keyBy('jersey_number');

            $lineScore = $this->compileVolleyballLineScore($game, $allEvents, $maxPeriod);

            $homeData = $this->compileVolleyballBoxData('home', $homeLineup, $homeOverallStats, $allEvents, $this->selectedPeriod);
            $awayData = $this->compileVolleyballBoxData('away', $awayLineup, $awayOverallStats, $allEvents, $this->selectedPeriod);
        } else {
            $maxPeriod = max($isQuarters ? 4 : 2, (int) $game->current_period);

            // Overall Basketball Stats records
            $homeOverallStats = BasketballStat::where('game_id', $game->id)
                ->where('team_side', 'home')
                ->get()
                ->keyBy('jersey_number');

            $awayOverallStats = BasketballStat::where('game_id', $game->id)
                ->where('team_side', 'away')
                ->get()
                ->keyBy('jersey_number');

            // Compile Line Score across all periods
            $lineScore = $this->compileLineScore($game, $allEvents, $maxPeriod, $isQuarters);

            // Compile Detailed Coach Box Stats for Home and Away
            $homeData = $this->compileCoachBoxData('home', $homeLineup, $homeOverallStats, $allEvents, $this->selectedPeriod, $isQuarters);
            $awayData = $this->compileCoachBoxData('away', $awayLineup, $awayOverallStats, $allEvents, $this->selectedPeriod, $isQuarters);
        }

        $homeBreakdown = $game->calculateTimeoutsBreakdown('home');
        $awayBreakdown = $game->calculateTimeoutsBreakdown('away');

        return view('livewire.coach-stats', [
            'game' => $game,
            'isVolleyball' => $isVolleyball,
            'isQuarters' => $isQuarters,
            'maxPeriod' => $maxPeriod,
            'lineScore' => $lineScore,
            'home' => $homeData,
            'away' => $awayData,
            'homeBreakdown' => $homeBreakdown,
            'awayBreakdown' => $awayBreakdown,
            'selectedPeriod' => $this->selectedPeriod,
            'teamView' => $this->teamView,
        ])->layout('layouts.public');
    }

    protected function compileLineScore(Game $game, $events, int $maxPeriod, bool $isQuarters): array
    {
        $periods = [];
        $homeTotal = 0;
        $awayTotal = 0;

        for ($p = 1; $p <= $maxPeriod; $p++) {
            $pEvents = $events->where('period', $p);
            $hPts = $pEvents->where('team_side', 'home')->sum('points');
            $aPts = $pEvents->where('team_side', 'away')->sum('points');

            $homeTotal += $hPts;
            $awayTotal += $aPts;

            $label = '';
            if ($isQuarters) {
                $label = ($p <= 4) ? "Q{$p}" : ($p === 5 ? 'OT' : 'OT'.($p - 4));
            } else {
                $label = ($p === 1) ? '1H' : (($p === 2) ? '2H' : ($p === 3 ? 'OT' : 'OT'.($p - 2)));
            }

            $periods[] = [
                'period' => $p,
                'label' => $label,
                'home_pts' => $hPts,
                'away_pts' => $aPts,
                'is_current' => ($p === (int) $game->current_period),
            ];
        }

        return [
            'periods' => $periods,
            'home_total' => $game->home_score ?: $homeTotal,
            'away_total' => $game->away_score ?: $awayTotal,
        ];
    }

    protected function compileVolleyballLineScore(Game $game, $events, int $maxPeriod): array
    {
        $periods = [];
        $homeSetsWon = 0;
        $awaySetsWon = 0;
        $homePeriodScores = $game->home_period_scores ?? [];
        $awayPeriodScores = $game->away_period_scores ?? [];

        for ($p = 1; $p <= $maxPeriod; $p++) {
            $hPts = (int) ($homePeriodScores[$p - 1] ?? $events->where('team_side', 'home')->where('period', $p)->sum('points'));
            $aPts = (int) ($awayPeriodScores[$p - 1] ?? $events->where('team_side', 'away')->where('period', $p)->sum('points'));

            $target = ($p === 5) ? 15 : 25;
            $setWon = null;
            if ($hPts >= $target && ($hPts - $aPts) >= 2) {
                $setWon = 'home';
                $homeSetsWon++;
            } elseif ($aPts >= $target && ($aPts - $hPts) >= 2) {
                $setWon = 'away';
                $awaySetsWon++;
            }

            $periods[] = [
                'period' => $p,
                'label' => "Set {$p}",
                'home_pts' => $hPts,
                'away_pts' => $aPts,
                'set_won' => $setWon,
                'is_current' => ($p === (int) $game->current_period),
            ];
        }

        return [
            'periods' => $periods,
            'home_sets_won' => $homeSetsWon,
            'away_sets_won' => $awaySetsWon,
            'home_total' => array_sum($homePeriodScores) ?: $events->where('team_side', 'home')->sum('points'),
            'away_total' => array_sum($awayPeriodScores) ?: $events->where('team_side', 'away')->sum('points'),
        ];
    }

    protected function compileCoachBoxData(string $teamSide, $lineup, $overallStats, $allEvents, string $periodFilter, bool $isQuarters): array
    {
        // Filter events by period if requested
        $events = $allEvents->where('team_side', $teamSide);
        if ($periodFilter !== 'all') {
            $periodNum = (int) $periodFilter;
            if ($periodNum >= 5) {
                $events = $events->where('period', '>=', 5);
            } else {
                $events = $events->where('period', $periodNum);
            }
        }

        $players = [];
        $teamTotals = [
            'pts' => 0,
            'fgm' => 0,
            'fga' => 0,
            'fg2m' => 0,
            'fg2a' => 0,
            'fg3m' => 0,
            'fg3a' => 0,
            'ftm' => 0,
            'fta' => 0,
            'oreb' => 0,
            'dreb' => 0,
            'reb' => 0,
            'ast' => 0,
            'stl' => 0,
            'blk' => 0,
            'swat' => 0,
            'to' => 0,
            'to_pass' => 0,
            'to_fumble' => 0,
            'to_violation' => 0,
            'pf' => 0,
            'off_fouls' => 0,
            'tf' => 0,
            'flag_fouls' => 0,
            'fouls_drawn' => 0,
            'tot_fouls' => 0,
            'seconds_played' => 0,
        ];

        $benchPts = 0;
        $starterPts = 0;

        foreach ($lineup as $lp) {
            $j = (string) $lp->jersey_number;
            $pEvents = $events->where('jersey_number', $j);
            $st = $overallStats[$j] ?? null;

            if ($periodFilter === 'all' && $st) {
                // Full game totals from precomputed BasketballStat
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
                $secondsPlayed = (int) ($st->seconds_played ?? 0);
            } else {
                // Calculated from filtered period events
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
                $otherTo = $pEvents->filter(fn ($e) => $e->action_code === 'TO' && ! in_array($e->action_type, ['turnover_pass', 'turnover_fumble', 'turnover_violation']))->count();
                $to = $toPass + $toFumble + $toViolation + $otherTo;

                $pf = $pEvents->filter(fn ($e) => in_array($e->action_code, ['F', 'PF']) || $e->action_type === 'foul_pers')->count();
                $offFouls = $pEvents->filter(fn ($e) => in_array($e->action_code, ['R', 'OF']) || $e->action_type === 'foul_off')->count();
                $tf = $pEvents->filter(fn ($e) => in_array($e->action_code, ['T', 'TF']) || $e->action_type === 'foul_tech')->count();
                $flagFouls = $pEvents->filter(fn ($e) => in_array($e->action_code, ['G', 'FL', 'FLAG']) || $e->action_type === 'foul_flagrant')->count();
                $foulsDrawn = $pEvents->filter(fn ($e) => in_array($e->action_code, ['H', 'FD']) || $e->action_type === 'foul_drawn')->count();
                $totFouls = $pf + $offFouls + $tf + $flagFouls;

                $pts = ($fg2m * 2) + ($fg3m * 3) + $ftm;
                $secondsPlayed = 0;
            }

            $fgPct = $fga > 0 ? round(($fgm / $fga) * 100, 1) : 0.0;
            $fg2Pct = $fg2a > 0 ? round(($fg2m / $fg2a) * 100, 1) : 0.0;
            $fg3Pct = $fg3a > 0 ? round(($fg3m / $fg3a) * 100, 1) : 0.0;
            $ftPct = $fta > 0 ? round(($ftm / $fta) * 100, 1) : 0.0;
            $efgPct = $fga > 0 ? round((($fgm + (0.5 * $fg3m)) / $fga) * 100, 1) : 0.0;
            $astToRatio = $to > 0 ? round($ast / $to, 2) : ($ast > 0 ? (float) $ast : 0.0);

            if ($lp->is_starter) {
                $starterPts += $pts;
            } else {
                $benchPts += $pts;
            }

            $minFormatted = $secondsPlayed > 0 ? sprintf('%d:%02d', floor($secondsPlayed / 60), $secondsPlayed % 60) : '—';

            $players[] = [
                'jersey' => $j,
                'name' => $lp->player_name,
                'position' => $lp->position ?: '—',
                'is_starter' => (bool) $lp->is_starter,
                'is_on_court' => (bool) $lp->is_on_court,
                'min' => $minFormatted,
                'seconds_played' => $secondsPlayed,
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
                'ast_to_ratio' => $astToRatio,
            ];

            // Accumulate team totals
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
            $teamTotals['seconds_played'] += $secondsPlayed;
        }

        $teamTotals['fg_pct'] = $teamTotals['fga'] > 0 ? round(($teamTotals['fgm'] / $teamTotals['fga']) * 100, 1) : 0.0;
        $teamTotals['fg2_pct'] = $teamTotals['fg2a'] > 0 ? round(($teamTotals['fg2m'] / $teamTotals['fg2a']) * 100, 1) : 0.0;
        $teamTotals['fg3_pct'] = $teamTotals['fg3a'] > 0 ? round(($teamTotals['fg3m'] / $teamTotals['fg3a']) * 100, 1) : 0.0;
        $teamTotals['ft_pct'] = $teamTotals['fta'] > 0 ? round(($teamTotals['ftm'] / $teamTotals['fta']) * 100, 1) : 0.0;
        $teamTotals['efg_pct'] = $teamTotals['fga'] > 0 ? round((($teamTotals['fgm'] + (0.5 * $teamTotals['fg3m'])) / $teamTotals['fga']) * 100, 1) : 0.0;
        $teamTotals['ast_to_ratio'] = $teamTotals['to'] > 0 ? round($teamTotals['ast'] / $teamTotals['to'], 2) : ($teamTotals['ast'] > 0 ? (float) $teamTotals['ast'] : 0.0);
        $teamTotals['fg_str'] = "{$teamTotals['fgm']}-{$teamTotals['fga']}";
        $teamTotals['fg2_str'] = "{$teamTotals['fg2m']}-{$teamTotals['fg2a']}";
        $teamTotals['fg3_str'] = "{$teamTotals['fg3m']}-{$teamTotals['fg3a']}";
        $teamTotals['ft_str'] = "{$teamTotals['ftm']}-{$teamTotals['fta']}";
        $teamTotals['min'] = $teamTotals['seconds_played'] > 0 ? sprintf('%d:%02d', floor($teamTotals['seconds_played'] / 60), $teamTotals['seconds_played'] % 60) : '—';

        return [
            'side' => $teamSide,
            'players' => $players,
            'totals' => $teamTotals,
            'starter_pts' => $starterPts,
            'bench_pts' => $benchPts,
        ];
    }

    protected function compileVolleyballBoxData(string $teamSide, $lineup, $overallStats, $allEvents, string $periodFilter): array
    {
        $events = $allEvents->where('team_side', $teamSide);
        if ($periodFilter !== 'all') {
            $setNum = (int) $periodFilter;
            $events = $events->where('period', $setNum);
        }

        $players = [];
        $teamTotals = [
            'sp' => 0,
            'pts' => 0.0,
            'kills' => 0,
            'attack_errors' => 0,
            'attack_attempts' => 0,
            'assists' => 0,
            'service_aces' => 0,
            'service_errors' => 0,
            'service_attempts' => 0,
            'digs' => 0,
            'block_solos' => 0,
            'block_assists' => 0,
            'total_blocks' => 0.0,
            'ball_handling_errors' => 0,
            'reception_errors' => 0,
        ];

        foreach ($lineup as $lp) {
            $j = (string) $lp->jersey_number;
            $pEvents = $events->where('jersey_number', $j);
            $st = $overallStats[$j] ?? null;

            if ($periodFilter === 'all' && $st) {
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
                $kills = $pEvents->filter(fn ($e) => $e->action_code === 'K' || $e->action_type === 'kill')->count();
                $attackErrors = $pEvents->filter(fn ($e) => $e->action_code === 'E' || $e->action_type === 'attack_error')->count();
                $attackAttempts = $pEvents->filter(fn ($e) => in_array($e->action_code, ['K', 'E', 'T']) || in_array($e->action_type, ['kill', 'attack_error', 'attack_attempt']))->count();
                $hittingPct = $attackAttempts > 0 ? round(($kills - $attackErrors) / $attackAttempts, 3) : 0.000;
                $assists = $pEvents->filter(fn ($e) => $e->action_code === 'Z' || $e->action_type === 'set_assist')->count();
                $serviceAces = $pEvents->filter(fn ($e) => $e->action_code === 'A' || $e->action_type === 'service_ace')->count();
                $serviceErrors = $pEvents->filter(fn ($e) => $e->action_code === 'S' || $e->action_type === 'service_error')->count();
                $serviceAttempts = $pEvents->filter(fn ($e) => in_array($e->action_code, ['A', 'S']) || in_array($e->action_type, ['service_ace', 'service_error']))->count();
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
                'is_on_court' => (bool) $lp->is_on_court,
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

            // Accumulate totals
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
}
