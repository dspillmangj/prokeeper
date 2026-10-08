<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use Livewire\Component;

class NcaaScorebook extends Component
{
    public string $code;

    public function mount(string $code)
    {
        $this->code = $code;
    }

    public function getGameProperty(): Game
    {
        return Game::where('access_code', $this->code)
            ->orWhere('uuid', $this->code)
            ->orWhere('slug', $this->code)
            ->with(['homeTeam', 'awayTeam'])
            ->firstOrFail();
    }

    public function render()
    {
        $game = $this->game;

        $homeLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->orderBy('jersey_number', 'asc')
            ->get();

        $awayLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->orderBy('jersey_number', 'asc')
            ->get();

        $homeStats = BasketballStat::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->get()
            ->keyBy('jersey_number');

        $awayStats = BasketballStat::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->get()
            ->keyBy('jersey_number');

        // Extract running score points mapping: point index => ['jersey' => '23', 'team' => 'home', 'period' => 1]
        $scoringEvents = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->where('points', '>', 0)
            ->orderBy('sequence', 'asc')
            ->get();

        $homeRunningScore = [];
        $awayRunningScore = [];
        $currentHome = 0;
        $currentAway = 0;

        foreach ($scoringEvents as $ev) {
            $pts = $ev->points;
            if ($ev->team_side === 'home') {
                for ($p = 1; $p <= $pts; $p++) {
                    $currentHome++;
                    $homeRunningScore[$currentHome] = [
                        'jersey' => $ev->jersey_number,
                        'period' => $ev->period,
                        'is_made_basket' => ($p === $pts),
                    ];
                }
            } else {
                for ($p = 1; $p <= $pts; $p++) {
                    $currentAway++;
                    $awayRunningScore[$currentAway] = [
                        'jersey' => $ev->jersey_number,
                        'period' => $ev->period,
                        'is_made_basket' => ($p === $pts),
                    ];
                }
            }
        }

        // Foul breakdown per player
        $foulEvents = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->whereIn('action_code', ['F', 'R', 'T'])
            ->orderBy('sequence', 'asc')
            ->get();

        $playerFouls = [];
        foreach ($foulEvents as $fe) {
            $key = "{$fe->team_side}_{$fe->jersey_number}";
            if (! isset($playerFouls[$key])) {
                $playerFouls[$key] = [];
            }
            $playerFouls[$key][] = [
                'type' => $fe->action_code,
                'period' => $fe->period,
                'clock' => $fe->formatted_clock,
            ];
        }

        return view('livewire.ncaa-scorebook', [
            'game' => $game,
            'homeLineup' => $homeLineup,
            'awayLineup' => $awayLineup,
            'homeStats' => $homeStats,
            'awayStats' => $awayStats,
            'homeRunningScore' => $homeRunningScore,
            'awayRunningScore' => $awayRunningScore,
            'playerFouls' => $playerFouls,
        ])->layout('layouts.public');
    }
}
