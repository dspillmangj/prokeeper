<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\VolleyballStat;
use Livewire\Component;

class PublicScoreboard extends Component
{
    public string $code;

    public string $activeTab = 'summary'; // summary, boxscore, plays

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

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $game = $this->game;

        $events = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->orderBy('sequence', 'desc')
            ->take(25)
            ->get();

        $homeLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->get();

        $awayLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->get();

        $homeBballStats = [];
        $awayBballStats = [];
        $homeVbStats = [];
        $awayVbStats = [];

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

        return view('livewire.public-scoreboard', [
            'game' => $game,
            'events' => $events,
            'homeLineup' => $homeLineup,
            'awayLineup' => $awayLineup,
            'homeBballStats' => $homeBballStats,
            'awayBballStats' => $awayBballStats,
            'homeVbStats' => $homeVbStats,
            'awayVbStats' => $awayVbStats,
        ])->layout('layouts.public');
    }
}
