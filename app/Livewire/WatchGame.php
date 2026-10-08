<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\VolleyballStat;
use Livewire\Component;

class WatchGame extends Component
{
    public ?string $code = null;
    public string $inputCode = '';
    public string $errorMessage = '';
    public string $activeTab = 'scoreboard'; // scoreboard, plays, boxscore, summary
    public string $pbpFilterTeam = 'all'; // all, home, away

    protected $queryString = [
        'activeTab' => ['except' => 'scoreboard'],
    ];

    public function mount(?string $code = null)
    {
        $this->code = $code ?: request()->route('code');
        if ($this->code) {
            $this->inputCode = strtoupper(trim($this->code));
        }

        $tab = request()->query('tab');
        if ($tab && in_array($tab, ['scoreboard', 'plays', 'boxscore', 'summary'])) {
            $this->activeTab = $tab;
        }
    }

    public function setTab(string $tab)
    {
        if (in_array($tab, ['scoreboard', 'plays', 'boxscore', 'summary'])) {
            $this->activeTab = $tab;
        }
    }

    public function setPbpFilter(string $filter)
    {
        if (in_array($filter, ['all', 'home', 'away'])) {
            $this->pbpFilterTeam = $filter;
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

        if (!$game) {
            $this->errorMessage = "No game found with code '{$clean}'.";
            return;
        }

        return redirect()->route('public.watch', $game->access_code);
    }

    public function getGameProperty(): ?Game
    {
        if (!$this->code) {
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

        if (!$game) {
            $liveGames = Game::whereIn('status', ['in_progress', 'paused', 'scheduled'])
                ->with(['homeTeam', 'awayTeam'])
                ->latest()
                ->take(6)
                ->get();

            return view('livewire.watch-game', [
                'game' => null,
                'liveGames' => $liveGames,
            ])->layout('layouts.public');
        }

        // 1. Play-by-play events
        $eventsQuery = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->orderBy('sequence', 'desc');

        if ($this->pbpFilterTeam !== 'all') {
            $eventsQuery->where('team_side', $this->pbpFilterTeam);
        }

        $events = $eventsQuery->take(100)->get();

        // 2. Lineups
        $homeLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->orderBy('is_on_court', 'desc')
            ->orderBy('jersey_number', 'asc')
            ->get();

        $awayLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->orderBy('is_on_court', 'desc')
            ->orderBy('jersey_number', 'asc')
            ->get();

        // 3. Stats for boxscore
        $homeBballStats = collect();
        $awayBballStats = collect();
        $homeVbStats = collect();
        $awayVbStats = collect();

        if ($game->sport === 'basketball') {
            $homeBballStats = BasketballStat::where('game_id', $game->id)
                ->where('team_side', 'home')
                ->orderBy('points', 'desc')
                ->get();

            $awayBballStats = BasketballStat::where('game_id', $game->id)
                ->where('team_side', 'away')
                ->orderBy('points', 'desc')
                ->get();
        } else {
            $homeVbStats = VolleyballStat::where('game_id', $game->id)
                ->where('team_side', 'home')
                ->orderBy('total_points', 'desc')
                ->get();

            $awayVbStats = VolleyballStat::where('game_id', $game->id)
                ->where('team_side', 'away')
                ->orderBy('total_points', 'desc')
                ->get();
        }

        // 4. Period breakdown
        $periodScores = [];
        $maxPeriods = max(4, $game->current_period);
        if ($game->sport === 'volleyball') {
            $maxPeriods = max(3, $game->current_period);
        }

        for ($p = 1; $p <= $maxPeriods; $p++) {
            $homePeriodScore = GameEvent::where('game_id', $game->id)
                ->where('period', $p)
                ->where('team_side', 'home')
                ->where('is_undone', false)
                ->sum('points');

            $awayPeriodScore = GameEvent::where('game_id', $game->id)
                ->where('period', $p)
                ->where('team_side', 'away')
                ->where('is_undone', false)
                ->sum('points');

            $periodName = $game->sport === 'basketball'
                ? ($p <= 4 ? "Q{$p}" : 'OT'.($p - 4))
                : "Set {$p}";

            $periodScores[] = [
                'period' => $p,
                'name' => $periodName,
                'home' => $homePeriodScore,
                'away' => $awayPeriodScore,
                'is_current' => ($p === $game->current_period),
            ];
        }

        // 5. Team Summary Comparisons (Basketball & Volleyball)
        $homeTeamStats = [
            'fgm' => $homeBballStats->sum('field_goals_made'),
            'fga' => $homeBballStats->sum('field_goals_attempted'),
            'fg_pct' => $homeBballStats->sum('field_goals_attempted') > 0 ? round(($homeBballStats->sum('field_goals_made') / $homeBballStats->sum('field_goals_attempted')) * 100, 1) : 0,
            'three_pm' => $homeBballStats->sum('three_pointers_made'),
            'three_pa' => $homeBballStats->sum('three_pointers_attempted'),
            'three_pct' => $homeBballStats->sum('three_pointers_attempted') > 0 ? round(($homeBballStats->sum('three_pointers_made') / $homeBballStats->sum('three_pointers_attempted')) * 100, 1) : 0,
            'ftm' => $homeBballStats->sum('free_throws_made'),
            'fta' => $homeBballStats->sum('free_throws_attempted'),
            'ft_pct' => $homeBballStats->sum('free_throws_attempted') > 0 ? round(($homeBballStats->sum('free_throws_made') / $homeBballStats->sum('free_throws_attempted')) * 100, 1) : 0,
            'reb' => $homeBballStats->sum('rebounds_total'),
            'ast' => $homeBballStats->sum('assists'),
            'stl' => $homeBballStats->sum('steals'),
            'blk' => $homeBballStats->sum('blocks'),
            'to' => $homeBballStats->sum('turnovers'),
            'fouls' => $homeBballStats->sum('fouls_personal'),
        ];

        $awayTeamStats = [
            'fgm' => $awayBballStats->sum('field_goals_made'),
            'fga' => $awayBballStats->sum('field_goals_attempted'),
            'fg_pct' => $awayBballStats->sum('field_goals_attempted') > 0 ? round(($awayBballStats->sum('field_goals_made') / $awayBballStats->sum('field_goals_attempted')) * 100, 1) : 0,
            'three_pm' => $awayBballStats->sum('three_pointers_made'),
            'three_pa' => $awayBballStats->sum('three_pointers_attempted'),
            'three_pct' => $awayBballStats->sum('three_pointers_attempted') > 0 ? round(($awayBballStats->sum('three_pointers_made') / $awayBballStats->sum('three_pointers_attempted')) * 100, 1) : 0,
            'ftm' => $awayBballStats->sum('free_throws_made'),
            'fta' => $awayBballStats->sum('free_throws_attempted'),
            'ft_pct' => $awayBballStats->sum('free_throws_attempted') > 0 ? round(($awayBballStats->sum('free_throws_made') / $awayBballStats->sum('free_throws_attempted')) * 100, 1) : 0,
            'reb' => $awayBballStats->sum('rebounds_total'),
            'ast' => $awayBballStats->sum('assists'),
            'stl' => $awayBballStats->sum('steals'),
            'blk' => $awayBballStats->sum('blocks'),
            'to' => $awayBballStats->sum('turnovers'),
            'fouls' => $awayBballStats->sum('fouls_personal'),
        ];

        return view('livewire.watch-game', [
            'game' => $game,
            'events' => $events,
            'homeLineup' => $homeLineup,
            'awayLineup' => $awayLineup,
            'homeBballStats' => $homeBballStats,
            'awayBballStats' => $awayBballStats,
            'homeVbStats' => $homeVbStats,
            'awayVbStats' => $awayVbStats,
            'periodScores' => $periodScores,
            'homeTeamStats' => $homeTeamStats,
            'awayTeamStats' => $awayTeamStats,
        ])->layout('layouts.public');
    }
}
