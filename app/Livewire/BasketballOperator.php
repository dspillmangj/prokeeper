<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Services\BasketballStatService;
use Exception;
use Livewire\Component;

class BasketballOperator extends Component
{
    public int $gameId;

    public string $statInput = '';

    public string $feedbackMessage = '';

    public string $feedbackType = 'info'; // info, success, error

    // Sub modal / controls
    public bool $showSubModal = false;

    public string $subTeamSide = 'home';

    public string $subOutJersey = '';

    public string $subInJersey = '';

    // Quick Action Bar helper
    public ?string $selectedTeam = 'home';

    public ?string $selectedJersey = null;

    // On-The-Fly Roster Modal & Editing State
    public bool $showRosterModal = false;

    public string $rosterModalTeam = 'home';

    public string $rosterModalTab = 'list'; // 'list', 'create', 'paste'

    public string $newJersey = '';

    public string $newName = '';

    public string $newPosition = '';

    public bool $newIsStarter = false;

    public bool $newIsOnCourt = false;

    public bool $syncWithTeamRoster = true;

    public string $bulkRosterInput = '';

    public string $bulkRosterMode = 'append';

    public ?int $editingLineupId = null;

    public string $editJersey = '';

    public string $editName = '';

    public string $editPosition = '';

    public bool $editIsOnCourt = false;

    public bool $editIsStarter = false;

    protected BasketballStatService $statService;

    public function boot(BasketballStatService $statService)
    {
        $this->statService = $statService;
    }

    public function mount(int $gameId)
    {
        $this->gameId = $gameId;
    }

    public function getGameProperty(): Game
    {
        return Game::with(['homeTeam', 'awayTeam'])->findOrFail($this->gameId);
    }

    public function openRosterModal(string $team = 'home', string $tab = 'list')
    {
        $this->rosterModalTeam = $team;
        $this->rosterModalTab = $tab;
        $this->showRosterModal = true;
        $this->editingLineupId = null;
        $this->resetNewPlayerFields();
    }

    public function closeRosterModal()
    {
        $this->showRosterModal = false;
        $this->editingLineupId = null;
    }

    public function setRosterModalTeam(string $team)
    {
        $this->rosterModalTeam = $team;
        $this->editingLineupId = null;
    }

    public function setRosterModalTab(string $tab)
    {
        $this->rosterModalTab = $tab;
    }

    public function resetNewPlayerFields()
    {
        $this->newJersey = '';
        $this->newName = '';
        $this->newPosition = '';
        $this->newIsStarter = false;
        $this->newIsOnCourt = false;
    }

    public function quickAddLineupPlayer()
    {
        $jersey = trim($this->newJersey);
        $name = trim($this->newName);
        $pos = !empty($this->newPosition) ? strtoupper(trim($this->newPosition)) : null;

        if (empty($jersey) || empty($name)) {
            $this->feedbackMessage = 'Jersey number and player name are required.';
            $this->feedbackType = 'error';
            return;
        }

        $game = $this->game;

        $existing = GameLineup::where('game_id', $game->id)
            ->where('team_side', $this->rosterModalTeam)
            ->where('jersey_number', $jersey)
            ->first();

        if ($existing) {
            $this->feedbackMessage = "Player with Jersey #{$jersey} already exists in this lineup.";
            $this->feedbackType = 'error';
            return;
        }

        $courtCount = GameLineup::where('game_id', $game->id)
            ->where('team_side', $this->rosterModalTeam)
            ->where('is_on_court', true)
            ->count();

        $isOnCourt = $this->newIsOnCourt;
        if (!$isOnCourt && $courtCount < 5) {
            $isOnCourt = true;
        }

        $teamId = ($this->rosterModalTeam === 'home') ? $game->home_team_id : $game->away_team_id;
        $playerId = null;

        if ($teamId && $this->syncWithTeamRoster) {
            $nameParts = explode(' ', $name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? $firstName;

            $player = \App\Models\Player::create([
                'organization_id' => $game->organization_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'default_jersey_number' => $jersey,
                'position' => $pos,
            ]);

            \App\Models\RosterPlayer::create([
                'team_id' => $teamId,
                'player_id' => $player->id,
                'jersey_number' => $jersey,
                'position' => $pos,
                'is_starter' => $this->newIsStarter,
            ]);

            $playerId = $player->id;
        }

        GameLineup::create([
            'game_id' => $game->id,
            'team_side' => $this->rosterModalTeam,
            'player_id' => $playerId,
            'jersey_number' => $jersey,
            'player_name' => $name,
            'position' => $pos,
            'is_starter' => $this->newIsStarter,
            'is_on_court' => $isOnCourt,
            'court_position' => $isOnCourt ? ($courtCount + 1) : null,
        ]);

        $this->resetNewPlayerFields();
        $this->feedbackMessage = "Added #{$jersey} {$name} to ".strtoupper($this->rosterModalTeam)." roster!";
        $this->feedbackType = 'success';
        $this->rosterModalTab = 'list';
    }

    public function startEditingLineup(int $id)
    {
        $lineup = GameLineup::where('game_id', $this->gameId)->findOrFail($id);
        $this->editingLineupId = $lineup->id;
        $this->editJersey = (string)$lineup->jersey_number;
        $this->editName = $lineup->player_name;
        $this->editPosition = (string)$lineup->position;
        $this->editIsOnCourt = (bool)$lineup->is_on_court;
        $this->editIsStarter = (bool)$lineup->is_starter;
    }

    public function cancelEditingLineup()
    {
        $this->editingLineupId = null;
    }

    public function saveEditedLineup()
    {
        if (!$this->editingLineupId) return;

        $lineup = GameLineup::where('game_id', $this->gameId)->findOrFail($this->editingLineupId);
        $newJersey = trim($this->editJersey);
        $newName = trim($this->editName);
        $newPos = !empty($this->editPosition) ? strtoupper(trim($this->editPosition)) : null;

        if (empty($newJersey) || empty($newName)) {
            $this->feedbackMessage = 'Jersey number and player name cannot be blank.';
            $this->feedbackType = 'error';
            return;
        }

        $oldJersey = $lineup->jersey_number;

        $lineup->jersey_number = $newJersey;
        $lineup->player_name = $newName;
        $lineup->position = $newPos;
        $lineup->is_on_court = $this->editIsOnCourt;
        $lineup->is_starter = $this->editIsStarter;
        $lineup->save();

        if ($oldJersey !== $newJersey || $lineup->player_name !== $newName) {
            BasketballStat::where('game_id', $this->gameId)
                ->where('team_side', $lineup->team_side)
                ->where('jersey_number', $oldJersey)
                ->update([
                    'jersey_number' => $newJersey,
                    'player_name' => $newName,
                ]);

            GameEvent::where('game_id', $this->gameId)
                ->where('team_side', $lineup->team_side)
                ->where('jersey_number', $oldJersey)
                ->update([
                    'jersey_number' => $newJersey,
                    'player_name' => $newName,
                ]);
        }

        if ($lineup->player) {
            $nameParts = explode(' ', $newName, 2);
            $lineup->player->update([
                'first_name' => $nameParts[0],
                'last_name' => $nameParts[1] ?? $nameParts[0],
                'default_jersey_number' => $newJersey,
                'position' => $newPos,
            ]);
        }

        $this->editingLineupId = null;
        $this->feedbackMessage = "Updated player #{$newJersey} {$newName} successfully.";
        $this->feedbackType = 'success';
    }

    public function toggleLineupCourtStatus(int $id)
    {
        $lineup = GameLineup::where('game_id', $this->gameId)->findOrFail($id);
        $lineup->is_on_court = !$lineup->is_on_court;
        $lineup->save();

        $this->feedbackMessage = "Player #{$lineup->jersey_number} moved to ".($lineup->is_on_court ? 'Court' : 'Bench').'.';
        $this->feedbackType = 'info';
    }

    public function deleteLineupPlayer(int $id)
    {
        $lineup = GameLineup::where('game_id', $this->gameId)->findOrFail($id);
        $jersey = $lineup->jersey_number;
        $name = $lineup->player_name;
        $lineup->delete();

        $this->feedbackMessage = "Removed #{$jersey} {$name} from game lineup.";
        $this->feedbackType = 'info';
    }

    public function importBulkRosterToGame()
    {
        $text = trim($this->bulkRosterInput);
        if (empty($text)) return;

        $game = $this->game;
        $teamSide = $this->rosterModalTeam;
        $teamId = ($teamSide === 'home') ? $game->home_team_id : $game->away_team_id;

        if ($this->bulkRosterMode === 'replace') {
            GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->delete();
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $importedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (str_contains($line, "\t")) {
                $parts = array_map('trim', explode("\t", $line));
            } elseif (str_contains($line, ",") && !preg_match('/^[0-9]+$/', $line)) {
                $parts = array_map('trim', explode(",", $line));
            } else {
                $parts = array_values(array_filter(preg_split('/\s+/', $line)));
            }

            if (empty($parts)) continue;

            $jersey = '';
            $name = '';
            $pos = null;
            $isStarter = false;

            $p0 = ltrim($parts[0], '#');
            if (is_numeric($p0)) {
                $jersey = $p0;
                array_shift($parts);
            }

            if (count($parts) >= 2) {
                $firstName = array_shift($parts);
                $lastName = array_shift($parts);
                $name = "{$firstName} {$lastName}";
            } elseif (count($parts) === 1) {
                $name = array_shift($parts);
            }

            while (!empty($parts)) {
                $p = array_shift($parts);
                if (in_array(strtolower($p), ['starter', 'start'])) {
                    $isStarter = true;
                } elseif (!$pos && strlen($p) <= 5) {
                    $pos = strtoupper($p);
                }
            }

            if (empty($jersey) || empty($name)) continue;

            $existing = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $jersey)
                ->first();

            if ($existing) {
                $existing->update([
                    'player_name' => $name,
                    'position' => $pos,
                    'is_starter' => $isStarter,
                ]);
            } else {
                $courtCount = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('is_on_court', true)
                    ->count();

                $isOnCourt = ($courtCount < 5) || $isStarter;

                $playerId = null;
                if ($teamId && $this->syncWithTeamRoster) {
                    $nameParts = explode(' ', $name, 2);
                    $p = \App\Models\Player::create([
                        'organization_id' => $game->organization_id,
                        'first_name' => $nameParts[0],
                        'last_name' => $nameParts[1] ?? $nameParts[0],
                        'default_jersey_number' => $jersey,
                        'position' => $pos,
                    ]);
                    \App\Models\RosterPlayer::create([
                        'team_id' => $teamId,
                        'player_id' => $p->id,
                        'jersey_number' => $jersey,
                        'position' => $pos,
                        'is_starter' => $isStarter,
                    ]);
                    $playerId = $p->id;
                }

                GameLineup::create([
                    'game_id' => $game->id,
                    'team_side' => $teamSide,
                    'player_id' => $playerId,
                    'jersey_number' => $jersey,
                    'player_name' => $name,
                    'position' => $pos,
                    'is_starter' => $isStarter,
                    'is_on_court' => $isOnCourt,
                    'court_position' => $isOnCourt ? ($courtCount + 1) : null,
                ]);
            }

            $importedCount++;
        }

        $this->bulkRosterInput = '';
        $this->feedbackMessage = "Imported {$importedCount} players into ".strtoupper($teamSide)." roster.";
        $this->feedbackType = 'success';
        $this->rosterModalTab = 'list';
    }

    public function processInput()
    {
        $input = trim($this->statInput);
        if (empty($input)) {
            return;
        }

        try {
            $game = $this->game;
            $result = $this->statService->processKeyboardInput($game, $input);
            $this->feedbackMessage = $result['message'];
            $this->feedbackType = 'success';
            $this->statInput = '';
        } catch (Exception $e) {
            $this->feedbackMessage = $e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function recordQuickStat(string $teamSide, string $jersey, string $actionCode)
    {
        try {
            $game = $this->game;
            $result = $this->statService->recordStat($game, $teamSide, $jersey, $actionCode);
            $this->feedbackMessage = $result['message'];
            $this->feedbackType = 'success';
        } catch (Exception $e) {
            $this->feedbackMessage = $e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function adjustScore(string $teamSide, int $delta)
    {
        $game = $this->game;
        if ($teamSide === 'home') {
            $game->home_score = max(0, $game->home_score + $delta);
            $scores = $game->home_period_scores ?? [0, 0, 0, 0];
            $cur = $game->current_period;
            while (count($scores) < $cur) {
                $scores[] = 0;
            }
            $scores[$cur - 1] = max(0, $scores[$cur - 1] + $delta);
            $game->home_period_scores = $scores;
        } else {
            $game->away_score = max(0, $game->away_score + $delta);
            $scores = $game->away_period_scores ?? [0, 0, 0, 0];
            $cur = $game->current_period;
            while (count($scores) < $cur) {
                $scores[] = 0;
            }
            $scores[$cur - 1] = max(0, $scores[$cur - 1] + $delta);
            $game->away_period_scores = $scores;
        }
        $game->save();
        $this->feedbackMessage = "Adjusted ".strtoupper($teamSide)." score (".($delta > 0 ? "+{$delta}" : "{$delta}").")";
        $this->feedbackType = 'info';
    }

    public function nextPeriod()
    {
        $game = $this->game;
        $game->current_period += 1;
        $game->home_fouls_current_period = 0;
        $game->away_fouls_current_period = 0;
        $game->save();

        $this->feedbackMessage = "Advanced to {$game->period_name}.";
        $this->feedbackType = 'info';
    }

    public function setPeriod(int $period)
    {
        if ($period < 1) {
            return;
        }
        $game = $this->game;
        $game->current_period = $period;
        $game->home_fouls_current_period = 0;
        $game->away_fouls_current_period = 0;
        $game->save();

        $this->feedbackMessage = "Set period to {$game->period_name}.";
        $this->feedbackType = 'info';
    }

    public function togglePossession()
    {
        $game = $this->game;
        $game->possession_arrow = ($game->possession_arrow === 'home') ? 'away' : 'home';
        $game->save();
    }

    public function callTimeout(string $teamSide)
    {
        $game = $this->game;
        if ($teamSide === 'home' && $game->home_timeouts_remaining > 0) {
            $game->home_timeouts_remaining -= 1;
            $this->feedbackMessage = "Timeout charged to HOME. Remaining: {$game->home_timeouts_remaining}";
        } elseif ($teamSide === 'away' && $game->away_timeouts_remaining > 0) {
            $game->away_timeouts_remaining -= 1;
            $this->feedbackMessage = "Timeout charged to AWAY. Remaining: {$game->away_timeouts_remaining}";
        }
        $game->save();
        $this->feedbackType = 'info';
    }

    public function executeSub()
    {
        if (empty($this->subOutJersey) || empty($this->subInJersey)) {
            $this->feedbackMessage = 'Please select both player leaving and player entering.';
            $this->feedbackType = 'error';

            return;
        }

        try {
            $this->statService->substitute($this->game, $this->subTeamSide, $this->subOutJersey, $this->subInJersey);
            $this->feedbackMessage = "Subbed OUT #{$this->subOutJersey} -> IN #{$this->subInJersey} (".strtoupper($this->subTeamSide).')';
            $this->feedbackType = 'success';
            $this->showSubModal = false;
            $this->subOutJersey = '';
            $this->subInJersey = '';
        } catch (Exception $e) {
            $this->feedbackMessage = $e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function undo()
    {
        $undone = $this->statService->undoLastEvent($this->game);
        if ($undone) {
            $this->feedbackMessage = "Undone: {$undone->description}";
            $this->feedbackType = 'info';
        } else {
            $this->feedbackMessage = 'No active plays to undo.';
            $this->feedbackType = 'error';
        }
    }

    public function render()
    {
        $game = $this->game;

        $homeLineupOnCourt = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->where('is_on_court', true)
            ->get();

        $homeBench = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->where('is_on_court', false)
            ->get();

        $awayLineupOnCourt = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->where('is_on_court', true)
            ->get();

        $awayBench = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->where('is_on_court', false)
            ->get();

        $rosterModalLineups = GameLineup::where('game_id', $game->id)
            ->where('team_side', $this->rosterModalTeam)
            ->orderBy('is_on_court', 'desc')
            ->orderBy('jersey_number', 'asc')
            ->get();

        $recentEvents = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->orderBy('sequence', 'desc')
            ->take(12)
            ->get();

        $homeStats = BasketballStat::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->orderBy('jersey_number', 'asc')
            ->get();

        $awayStats = BasketballStat::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->orderBy('jersey_number', 'asc')
            ->get();

        return view('livewire.basketball-operator', [
            'game' => $game,
            'homeLineupOnCourt' => $homeLineupOnCourt,
            'homeBench' => $homeBench,
            'awayLineupOnCourt' => $awayLineupOnCourt,
            'awayBench' => $awayBench,
            'rosterModalLineups' => $rosterModalLineups,
            'recentEvents' => $recentEvents,
            'homeStats' => $homeStats,
            'awayStats' => $awayStats,
            'actionDefinitions' => BasketballStatService::ACTIONS,
        ]);
    }
}
