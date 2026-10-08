<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use Livewire\Component;

class PureScoreboard extends Component
{
    public ?string $code = null;
    public string $inputCode = '';
    public string $errorMessage = '';

    public function mount(?string $code = null)
    {
        $this->code = $code ?: request()->route('code');
        if ($this->code) {
            $this->inputCode = strtoupper(trim($this->code));
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

        return redirect()->route('public.scoreboard', $game->access_code);
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

            return view('livewire.pure-scoreboard', [
                'game' => null,
                'liveGames' => $liveGames,
            ])->layout('layouts.public');
        }

        // Period score breakdown calculation
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

        return view('livewire.pure-scoreboard', [
            'game' => $game,
            'periodScores' => $periodScores,
        ])->layout('layouts.public');
    }
}
