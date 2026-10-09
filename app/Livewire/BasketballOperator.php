<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Player;
use App\Models\RosterPlayer;
use App\Models\Team;
use App\Services\BasketballStatService;
use Carbon\Carbon;
use Exception;
use Livewire\Attributes\On;
use Livewire\Component;

class BasketballOperator extends Component
{
    public int $gameId;

    public string $statInput = '';

    public string $feedbackMessage = '';

    public string $feedbackType = 'info'; // info, success, error

    // Game Details / Officials Modal State
    public bool $showGameDetailsModal = false;

    public string $gameScheduledDate = '';

    public string $gameScheduledTime = '';

    public string $gameVenue = '';

    public string $gameStatus = 'in_progress';

    public string $gameEventName = '';

    public string $gameHomeName = '';

    public string $gameAwayName = '';

    public int $gamePeriod = 1;

    public int $gameClockMinutes = 8;

    public int $gameClockSeconds = 0;

    public int $gamePeriodMinutes = 8;

    // Configurable Game Rules & Timeouts
    public int $timeoutsFull = 3;

    public int $timeouts30s = 2;

    public int $timeoutsOt = 1;

    public string $periodFormat = 'quarters';

    public int $otMinutes = 4;

    public int $shotClockSeconds = 0;

    public int $bonusFoulThreshold = 5;

    public int $doubleBonusFoulThreshold = 5;

    public int $playerFoulLimit = 5;

    public int $homeTimeoutsRemaining = 5;

    public int $awayTimeoutsRemaining = 5;

    public string $homeScoreColor = '#1e40af';

    public string $awayScoreColor = '#b91c1c';

    public bool $broadcastFlip = false;

    public string $officialReferee = '';

    public string $officialUmpire1 = '';

    public string $officialUmpire2 = '';

    public string $officialScorer = '';

    public string $officialTimer = '';

    public string $officialShotClock = '';

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

    #[On('open-game-details')]
    public function openGameDetailsModal()
    {
        $game = $this->game;
        $this->gameScheduledDate = $game->scheduled_at ? $game->scheduled_at->format('Y-m-d') : date('Y-m-d');
        $this->gameScheduledTime = $game->scheduled_at ? $game->scheduled_at->format('H:i') : date('H:i');
        $this->gameVenue = $game->venue ?? '';
        $this->gameStatus = $game->status ?? 'in_progress';
        $this->gameEventName = $game->settings['event_name'] ?? '';
        $this->gameHomeName = $game->home_team_name ?? ($game->homeTeam?->name ?? '');
        $this->gameAwayName = $game->away_team_name ?? ($game->awayTeam?->name ?? '');
        $this->gamePeriod = (int) $game->current_period;
        $this->gameClockMinutes = (int) floor($game->clock_seconds_remaining / 60);
        $this->gameClockSeconds = (int) ($game->clock_seconds_remaining % 60);
        $this->gamePeriodMinutes = $game->period_minutes;

        // Configurable rules
        $this->timeoutsFull = $game->full_timeouts_allowed;
        $this->timeouts30s = $game->thirty_second_timeouts_allowed;
        $this->timeoutsOt = $game->ot_timeouts_allowed;
        $this->periodFormat = $game->period_format;
        $this->otMinutes = $game->ot_minutes;
        $this->shotClockSeconds = $game->shot_clock_seconds ?? 0;
        $this->bonusFoulThreshold = $game->bonus_threshold;
        $this->doubleBonusFoulThreshold = $game->double_bonus_threshold;
        $this->playerFoulLimit = $game->player_foul_limit;
        $this->homeTimeoutsRemaining = $game->home_timeouts_remaining;
        $this->awayTimeoutsRemaining = $game->away_timeouts_remaining;
        $this->homeScoreColor = $game->home_team_score_color ?: '#1e40af';
        $this->awayScoreColor = $game->away_team_score_color ?: '#b91c1c';
        $this->broadcastFlip = (bool) ($game->settings['broadcast_flip'] ?? false);

        $officials = $game->settings['officials'] ?? [];
        $this->officialReferee = $officials['referee'] ?? '';
        $this->officialUmpire1 = $officials['umpire1'] ?? '';
        $this->officialUmpire2 = $officials['umpire2'] ?? '';
        $this->officialScorer = $officials['official_scorer'] ?? '';
        $this->officialTimer = $officials['timer'] ?? '';
        $this->officialShotClock = $officials['shot_clock'] ?? '';

        $this->showGameDetailsModal = true;
    }

    public function closeGameDetailsModal()
    {
        $this->showGameDetailsModal = false;
    }

    public function closeSignatureModal()
    {
        // Safe no-op if invoked by universal modal escape
    }

    public function saveGameDetails()
    {
        $game = $this->game;

        $scheduledAt = null;
        if (! empty($this->gameScheduledDate)) {
            $timeStr = ! empty($this->gameScheduledTime) ? $this->gameScheduledTime : '00:00';
            try {
                $scheduledAt = Carbon::parse("{$this->gameScheduledDate} {$timeStr}");
            } catch (Exception $e) {
                $scheduledAt = $game->scheduled_at;
            }
        }

        $totalClockSeconds = max(0, ($this->gameClockMinutes * 60) + $this->gameClockSeconds);

        $settings = $game->settings ?? [];
        $settings['event_name'] = trim($this->gameEventName);
        $settings['period_minutes'] = max(1, $this->gamePeriodMinutes);
        $settings['broadcast_flip'] = (bool) $this->broadcastFlip;

        $rules = $settings['rules'] ?? [];
        $rules['timeouts_full'] = max(0, $this->timeoutsFull);
        $rules['timeouts_30s'] = max(0, $this->timeouts30s);
        $rules['timeouts_ot'] = max(0, $this->timeoutsOt);
        $rules['period_format'] = in_array($this->periodFormat, ['quarters', 'halves']) ? $this->periodFormat : 'quarters';
        $rules['period_minutes'] = max(1, $this->gamePeriodMinutes);
        $rules['ot_minutes'] = max(1, $this->otMinutes);
        $rules['shot_clock_seconds'] = $this->shotClockSeconds > 0 ? $this->shotClockSeconds : null;
        $rules['bonus_foul_threshold'] = max(1, $this->bonusFoulThreshold);
        $rules['double_bonus_foul_threshold'] = max(1, $this->doubleBonusFoulThreshold);
        $rules['player_foul_limit'] = max(1, $this->playerFoulLimit);

        $settings['rules'] = $rules;
        $settings['timeouts_per_game'] = $rules['timeouts_full'] + $rules['timeouts_30s'];

        $settings['officials'] = [
            'referee' => trim($this->officialReferee),
            'umpire1' => trim($this->officialUmpire1),
            'umpire2' => trim($this->officialUmpire2),
            'official_scorer' => trim($this->officialScorer),
            'timer' => trim($this->officialTimer),
            'shot_clock' => trim($this->officialShotClock),
        ];

        $game->scheduled_at = $scheduledAt;
        $game->venue = trim($this->gameVenue);
        $game->status = $this->gameStatus;
        $game->current_period = min(6, max(1, $this->gamePeriod));
        $game->clock_seconds_remaining = $totalClockSeconds;
        $game->home_timeouts_remaining = max(0, $this->homeTimeoutsRemaining);
        $game->away_timeouts_remaining = max(0, $this->awayTimeoutsRemaining);
        $game->home_team_score_color = ! empty($this->homeScoreColor) ? $this->homeScoreColor : '#1e40af';
        $game->away_team_score_color = ! empty($this->awayScoreColor) ? $this->awayScoreColor : '#b91c1c';
        $game->settings = $settings;

        if (! empty($this->gameHomeName)) {
            $game->home_team_name = trim($this->gameHomeName);
        }
        if (! empty($this->gameAwayName)) {
            $game->away_team_name = trim($this->gameAwayName);
        }

        $game->save();

        $this->dispatch('game-colors-updated', [
            'homeColor' => $game->home_team_score_color,
            'awayColor' => $game->away_team_score_color,
            'broadcastFlip' => $this->broadcastFlip,
        ]);

        $this->showGameDetailsModal = false;
        $this->feedbackMessage = 'Game details, team colors, rules, and officials updated.';
        $this->feedbackType = 'success';
    }

    public function deleteGame()
    {
        $game = $this->game;
        $name = "{$game->home_display_name} vs {$game->away_display_name}";
        $game->delete();

        session()->flash('success', "Game '{$name}' deleted successfully.");

        return redirect()->route('dashboard');
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

    protected function syncLineupPlayerToTeamRoster(GameLineup $lineup, ?string $oldJersey = null): void
    {
        if (! $this->syncWithTeamRoster) {
            return;
        }

        $game = $this->game;
        $teamSide = $lineup->team_side;
        $teamId = ($teamSide === 'home') ? $game->home_team_id : $game->away_team_id;

        if (! $teamId) {
            $teamName = ($teamSide === 'home') ? $game->home_team_name : $game->away_team_name;
            if (! empty($teamName) && $game->organization_id) {
                $foundTeam = Team::where('organization_id', $game->organization_id)
                    ->where('sport', $game->sport)
                    ->where('name', $teamName)
                    ->first();
                if ($foundTeam) {
                    $teamId = $foundTeam->id;
                    if ($teamSide === 'home') {
                        $game->home_team_id = $teamId;
                    } else {
                        $game->away_team_id = $teamId;
                    }
                    $game->save();
                }
            }
        }

        if (! $teamId) {
            return;
        }

        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        $jersey = trim((string) $lineup->jersey_number);
        $fullName = trim((string) $lineup->player_name);
        $pos = ! empty($lineup->position) ? strtoupper(trim($lineup->position)) : null;

        if (empty($jersey) || empty($fullName)) {
            return;
        }

        $nameParts = preg_split('/\s+/', $fullName, 2);
        $firstName = $nameParts[0] ?? 'Player';
        $lastName = $nameParts[1] ?? '';

        $player = null;
        if ($lineup->player_id) {
            $player = Player::find($lineup->player_id);
        }

        if (! $player) {
            $searchJerseys = array_filter([$oldJersey, $jersey]);
            $existingRp = RosterPlayer::where('team_id', $teamId)
                ->whereIn('jersey_number', $searchJerseys)
                ->first();

            if ($existingRp && $existingRp->player_id) {
                $player = Player::find($existingRp->player_id);
                $lineup->player_id = $existingRp->player_id;
                $lineup->save();
            }
        }

        if ($player) {
            $player->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'default_jersey_number' => $jersey,
                'position' => $pos ?: $player->position,
            ]);
        } else {
            $player = Player::create([
                'organization_id' => $game->organization_id ?? $team->organization_id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'default_jersey_number' => $jersey,
                'position' => $pos,
            ]);

            $lineup->player_id = $player->id;
            $lineup->save();
        }

        $rosterPlayer = RosterPlayer::where('team_id', $teamId)
            ->where('player_id', $player->id)
            ->first();

        if (! $rosterPlayer && $oldJersey) {
            $rosterPlayer = RosterPlayer::where('team_id', $teamId)
                ->where('jersey_number', $oldJersey)
                ->first();
        }

        if ($rosterPlayer) {
            $rosterPlayer->update([
                'player_id' => $player->id,
                'jersey_number' => $jersey,
                'position' => $pos ?: $rosterPlayer->position,
                'is_starter' => (bool) $lineup->is_starter,
                'is_libero' => (bool) ($lineup->is_libero ?? false),
            ]);
        } else {
            RosterPlayer::create([
                'team_id' => $teamId,
                'player_id' => $player->id,
                'jersey_number' => $jersey,
                'position' => $pos,
                'is_starter' => (bool) $lineup->is_starter,
                'is_libero' => (bool) ($lineup->is_libero ?? false),
            ]);
        }
    }

    public function quickAddLineupPlayer()
    {
        $jersey = trim($this->newJersey);
        $name = trim($this->newName);
        $pos = ! empty($this->newPosition) ? strtoupper(trim($this->newPosition)) : null;

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
        if (! $isOnCourt && $courtCount < 5) {
            $isOnCourt = true;
        }

        $newLineup = GameLineup::create([
            'game_id' => $game->id,
            'team_side' => $this->rosterModalTeam,
            'jersey_number' => $jersey,
            'player_name' => $name,
            'position' => $pos,
            'is_starter' => $this->newIsStarter,
            'is_on_court' => $isOnCourt,
            'court_position' => $isOnCourt ? ($courtCount + 1) : null,
        ]);

        $this->syncLineupPlayerToTeamRoster($newLineup);

        $this->resetNewPlayerFields();
        $this->feedbackMessage = "Added #{$jersey} {$name} to ".strtoupper($this->rosterModalTeam).' roster!';
        $this->feedbackType = 'success';
        $this->rosterModalTab = 'list';
    }

    public function startEditingLineup(int $id)
    {
        $lineup = GameLineup::where('game_id', $this->gameId)->findOrFail($id);
        $this->editingLineupId = $lineup->id;
        $this->editJersey = (string) $lineup->jersey_number;
        $this->editName = $lineup->player_name;
        $this->editPosition = (string) $lineup->position;
        $this->editIsOnCourt = (bool) $lineup->is_on_court;
        $this->editIsStarter = (bool) $lineup->is_starter;
    }

    public function cancelEditingLineup()
    {
        $this->editingLineupId = null;
    }

    public function saveEditedLineup()
    {
        if (! $this->editingLineupId) {
            return;
        }

        $lineup = GameLineup::where('game_id', $this->gameId)->findOrFail($this->editingLineupId);
        $newJersey = trim($this->editJersey);
        $newName = trim($this->editName);
        $newPos = ! empty($this->editPosition) ? strtoupper(trim($this->editPosition)) : null;

        if (empty($newJersey) || empty($newName)) {
            $this->feedbackMessage = 'Jersey number and player name cannot be blank.';
            $this->feedbackType = 'error';

            return;
        }

        $oldJersey = $lineup->jersey_number;
        $oldName = $lineup->player_name;

        $lineup->jersey_number = $newJersey;
        $lineup->player_name = $newName;
        $lineup->position = $newPos;
        $lineup->is_on_court = $this->editIsOnCourt;
        $lineup->is_starter = $this->editIsStarter;
        $lineup->save();

        if ($oldJersey !== $newJersey || $oldName !== $newName) {
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

        $this->syncLineupPlayerToTeamRoster($lineup, $oldJersey);

        $this->editingLineupId = null;
        $this->feedbackMessage = "Updated player #{$newJersey} {$newName} successfully.";
        $this->feedbackType = 'success';
    }

    public function toggleLineupCourtStatus(int $id)
    {
        $lineup = GameLineup::where('game_id', $this->gameId)->findOrFail($id);
        $lineup->is_on_court = ! $lineup->is_on_court;
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
        if (empty($text)) {
            return;
        }

        $game = $this->game;
        $teamSide = $this->rosterModalTeam;

        if ($this->bulkRosterMode === 'replace') {
            GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->delete();
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $importedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (str_contains($line, "\t")) {
                $parts = array_map('trim', explode("\t", $line));
            } elseif (str_contains($line, ',') && ! preg_match('/^[0-9]+$/', $line)) {
                $parts = array_map('trim', explode(',', $line));
            } else {
                $parts = array_values(array_filter(preg_split('/\s+/', $line)));
            }

            if (empty($parts)) {
                continue;
            }

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

            while (! empty($parts)) {
                $p = array_shift($parts);
                if (in_array(strtolower($p), ['starter', 'start'])) {
                    $isStarter = true;
                } elseif (! $pos && strlen($p) <= 5) {
                    $pos = strtoupper($p);
                }
            }

            if (empty($jersey) || empty($name)) {
                continue;
            }

            $existing = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $jersey)
                ->first();

            if ($existing) {
                $oldJersey = $existing->jersey_number;
                $existing->update([
                    'player_name' => $name,
                    'position' => $pos,
                    'is_starter' => $isStarter,
                ]);
                $this->syncLineupPlayerToTeamRoster($existing, $oldJersey);
            } else {
                $courtCount = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('is_on_court', true)
                    ->count();

                $isOnCourt = ($courtCount < 5) || $isStarter;

                $newLineup = GameLineup::create([
                    'game_id' => $game->id,
                    'team_side' => $teamSide,
                    'jersey_number' => $jersey,
                    'player_name' => $name,
                    'position' => $pos,
                    'is_starter' => $isStarter,
                    'is_on_court' => $isOnCourt,
                    'court_position' => $isOnCourt ? ($courtCount + 1) : null,
                ]);

                $this->syncLineupPlayerToTeamRoster($newLineup);
            }

            $importedCount++;
        }

        $this->bulkRosterInput = '';
        $this->feedbackMessage = "Imported {$importedCount} players into ".strtoupper($teamSide).' roster.';
        $this->feedbackType = 'success';
        $this->rosterModalTab = 'list';
    }

    public function saveRosterSpreadsheet(string $teamSide, array $players)
    {
        $game = $this->game;
        $submittedLineupIds = [];

        foreach ($players as $index => $row) {
            $jersey = trim((string) ($row['jersey_number'] ?? ''));
            $name = trim($row['name'] ?? ($row['player_name'] ?? ''));

            if ($jersey === '' || $name === '') {
                continue;
            }

            $lineupId = ! empty($row['id']) ? (int) $row['id'] : null;
            $isOnCourt = filter_var($row['is_on_court'] ?? false, FILTER_VALIDATE_BOOLEAN);

            // Default first 5 players to on-court if court status not specified
            if (! isset($row['is_on_court']) && count($submittedLineupIds) < 5) {
                $isOnCourt = true;
            }

            $lineup = null;
            if ($lineupId) {
                $lineup = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('id', $lineupId)
                    ->first();
            }

            if (! $lineup) {
                $lineup = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('jersey_number', $jersey)
                    ->first();
            }

            if ($lineup) {
                $oldJersey = $lineup->jersey_number;
                $oldName = $lineup->player_name;

                $lineup->jersey_number = $jersey;
                $lineup->player_name = $name;
                $lineup->is_on_court = $isOnCourt;
                $lineup->save();

                if ($oldJersey !== $jersey || $oldName !== $name) {
                    BasketballStat::where('game_id', $this->gameId)
                        ->where('team_side', $teamSide)
                        ->where('jersey_number', $oldJersey)
                        ->update([
                            'jersey_number' => $jersey,
                            'player_name' => $name,
                        ]);

                    GameEvent::where('game_id', $this->gameId)
                        ->where('team_side', $teamSide)
                        ->where('jersey_number', $oldJersey)
                        ->update([
                            'jersey_number' => $jersey,
                            'player_name' => $name,
                        ]);
                }

                $this->syncLineupPlayerToTeamRoster($lineup, $oldJersey);
                $submittedLineupIds[] = $lineup->id;
            } else {
                $newLineup = GameLineup::create([
                    'game_id' => $game->id,
                    'team_side' => $teamSide,
                    'jersey_number' => $jersey,
                    'player_name' => $name,
                    'is_on_court' => $isOnCourt,
                ]);

                $this->syncLineupPlayerToTeamRoster($newLineup);
                $submittedLineupIds[] = $newLineup->id;
            }
        }

        // Remove lineup players on this team side not in submitted list
        GameLineup::where('game_id', $game->id)
            ->where('team_side', $teamSide)
            ->whereNotIn('id', $submittedLineupIds)
            ->delete();

        $this->feedbackMessage = 'Updated '.strtoupper($teamSide).' roster spreadsheet.';
        $this->feedbackType = 'success';
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
        try {
            $event = $this->statService->adjustScore($this->game, $teamSide, $delta);
            $this->feedbackMessage = $event->description;
            $this->feedbackType = 'info';
        } catch (Exception $e) {
            $this->feedbackMessage = $e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function nextPeriod()
    {
        $game = $this->game;
        if ($game->current_period >= 6) {
            $this->feedbackMessage = 'Already at maximum period (2nd Overtime / Period 6).';
            $this->feedbackType = 'error';

            return;
        }
        $game->current_period += 1;
        $game->home_fouls_current_period = 0;
        $game->away_fouls_current_period = 0;
        $game->save();

        $lastSeq = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;
        GameEvent::create([
            'game_id' => $game->id,
            'sequence' => $lastSeq + 1,
            'period' => $game->current_period,
            'clock_seconds_remaining' => $game->clock_seconds_remaining,
            'team_side' => 'home',
            'player_name' => 'Period Advance',
            'sport' => 'basketball',
            'action_code' => 'PERIOD',
            'action_type' => 'period_change',
            'action_name' => "Start of {$game->period_name}",
            'points' => 0,
            'home_score_after' => $game->home_score,
            'away_score_after' => $game->away_score,
            'description' => "Advanced to {$game->period_name}",
        ]);

        $this->feedbackMessage = "Advanced to {$game->period_name}.";
        $this->feedbackType = 'info';
    }

    public function setPeriod(int $period)
    {
        if ($period < 1 || $period > 6) {
            $this->feedbackMessage = 'Period must be between 1 and 6.';
            $this->feedbackType = 'error';

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

    public function callTimeout(string $teamSide, string $timeoutType = 'full')
    {
        try {
            $event = $this->statService->callTimeout($this->game, $teamSide, $timeoutType);
            $this->feedbackMessage = $event->description;
            $this->feedbackType = 'info';
        } catch (Exception $e) {
            $this->feedbackMessage = $e->getMessage();
            $this->feedbackType = 'error';
        }
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

    public function createManualEvent(array $data)
    {
        try {
            $event = $this->statService->createManualEvent($this->game, $data);
            $this->feedbackMessage = "Logged: {$event->description}";
            $this->feedbackType = 'success';
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

    public function deleteGameEvent(int $eventId)
    {
        $deleted = $this->statService->deleteEvent($this->game, $eventId);
        if ($deleted) {
            $this->feedbackMessage = "Deleted play: {$deleted->description}";
            $this->feedbackType = 'info';
        }
    }

    public function updateGameEvent(int $eventId, string $jersey, string $actionCode, int $period, ?string $description = null, ?int $points = null, ?string $teamSide = null, ?int $clockSeconds = null)
    {
        $payload = [
            'jersey_number' => $jersey,
            'action_code' => $actionCode,
            'period' => $period,
        ];
        if (! is_null($description)) {
            $payload['description'] = $description;
        }
        if (! is_null($points)) {
            $payload['points'] = $points;
        }
        if (! is_null($teamSide)) {
            $payload['team_side'] = $teamSide;
        }
        if (! is_null($clockSeconds)) {
            $payload['clock_seconds_remaining'] = $clockSeconds;
        }

        $updated = $this->statService->updateEvent($this->game, $eventId, $payload);
        if ($updated) {
            $this->feedbackMessage = "Updated play: {$updated->description}";
            $this->feedbackType = 'success';
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
            ->take(100)
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
