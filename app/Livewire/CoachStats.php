<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use Livewire\Component;

class CoachStats extends Component
{
    public ?string $code = null;

    public string $inputCode = '';

    public string $errorMessage = '';

    public string $selectedPeriod = 'all'; // 'all', '1', '2', '3', '4', '5' (OT)

    public string $teamView = 'both'; // 'both', 'home', 'away'

    protected $queryString = [
        'selectedPeriod' => ['except' => 'all', 'as' => 'period'],
        'teamView' => ['except' => 'both', 'as' => 'team'],
    ];

    public function mount(?string $code = null)
    {
        $this->code = $code ?: request()->route('code');
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
            ])->layout('layouts.public');
        }

        $isQuarters = ($game->period_format !== 'halves');
        $maxPeriod = max($isQuarters ? 4 : 2, (int) $game->current_period);

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

        $homeBreakdown = $game->calculateTimeoutsBreakdown('home');
        $awayBreakdown = $game->calculateTimeoutsBreakdown('away');

        return view('livewire.coach-stats', [
            'game' => $game,
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
            'to' => 0,
            'pf' => 0,
            'tf' => 0,
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
                $reb = (int) $st->reb;
                $ast = (int) $st->ast;
                $stl = (int) $st->stl;
                $blk = (int) ($st->blk + ($st->swat ?? 0));
                $to = (int) $st->turnovers;
                $pf = (int) ($st->fouls_pers + $st->fouls_off);
                $tf = (int) $st->fouls_tech;
                $pts = (int) $st->points;
            } else {
                // Calculated from filtered period events
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
                'is_on_court' => (bool) $lp->is_on_court,
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
}
