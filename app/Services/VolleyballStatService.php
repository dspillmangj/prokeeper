<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\VolleyballStat;
use Exception;
use Illuminate\Support\Facades\DB;

class VolleyballStatService
{
    /**
     * Volleyball actions mapping
     */
    public const ACTIONS = [
        'K' => ['name' => 'Kill', 'type' => 'kill', 'point_team' => 'same', 'stat' => 'kills', 'attempt' => true, 'category' => 'attack'],
        'E' => ['name' => 'Attack Error', 'type' => 'attack_error', 'point_team' => 'opp', 'stat' => 'attack_errors', 'attempt' => true, 'category' => 'attack'],
        'T' => ['name' => 'Attack Attempt', 'type' => 'attack_attempt', 'point_team' => null, 'attempt' => true, 'category' => 'attack'],
        'A' => ['name' => 'Service Ace', 'type' => 'service_ace', 'point_team' => 'same', 'stat' => 'service_aces', 'serve_attempt' => true, 'category' => 'serve'],
        'S' => ['name' => 'Service Error', 'type' => 'service_error', 'point_team' => 'opp', 'stat' => 'service_errors', 'serve_attempt' => true, 'category' => 'serve'],
        'D' => ['name' => 'Dig', 'type' => 'dig', 'point_team' => null, 'stat' => 'digs', 'category' => 'defense'],
        'B' => ['name' => 'Block Solo', 'type' => 'block_solo', 'point_team' => 'same', 'stat' => 'block_solos', 'category' => 'defense'],
        'C' => ['name' => 'Block Assist', 'type' => 'block_assist', 'point_team' => 'same', 'stat' => 'block_assists', 'category' => 'defense'],
        'H' => ['name' => 'Ball Handling Error', 'type' => 'ball_handling_error', 'point_team' => 'opp', 'stat' => 'ball_handling_errors', 'category' => 'errors'],
        'R' => ['name' => 'Reception Error', 'type' => 'reception_error', 'point_team' => 'opp', 'stat' => 'reception_errors', 'category' => 'errors'],
        'Z' => ['name' => 'Set Assist', 'type' => 'set_assist', 'point_team' => null, 'stat' => 'assists', 'category' => 'ball_movement'],
        'TIMEOUT' => ['name' => 'Timeout', 'type' => 'timeout', 'point_team' => null, 'points' => 0, 'category' => 'administrative'],
        'SCORE_ADJ' => ['name' => 'Score Adjustment', 'type' => 'score_adjustment', 'point_team' => null, 'points' => 0, 'category' => 'administrative'],
        'SUB' => ['name' => 'Substitution', 'type' => 'substitution', 'point_team' => null, 'points' => 0, 'category' => 'administrative'],
        'PERIOD' => ['name' => 'Set Advance', 'type' => 'period_change', 'point_team' => null, 'points' => 0, 'category' => 'administrative'],
        'ROTATE' => ['name' => 'Rotation', 'type' => 'rotation', 'point_team' => null, 'points' => 0, 'category' => 'administrative'],
        'NOTE' => ['name' => 'Audit Note', 'type' => 'audit_note', 'point_team' => null, 'points' => 0, 'category' => 'administrative'],
    ];

    /**
     * Process raw keyboard input string: e.g. "12-K" (Home Kill) or "07=A" (Away Ace)
     */
    public function processKeyboardInput(Game $game, string $input): array
    {
        $input = trim($input);
        if (empty($input)) {
            throw new Exception('No input provided.');
        }

        if (! preg_match('/^([0-9]{1,3}|00)\s*([=\-])\s*([A-Za-z]+)$/', $input, $matches)) {
            throw new Exception("Invalid input format. Use '<Jersey>-<Action>' for Home or '<Jersey>=<Action>' for Away (e.g. 12-K).");
        }

        $jersey = $matches[1];
        $teamIndicator = $matches[2];
        $actionCode = strtoupper($matches[3]);
        $teamSide = ($teamIndicator === '-') ? 'home' : 'away';

        if (! isset(self::ACTIONS[$actionCode])) {
            throw new Exception("Unknown action '{$actionCode}'. Supported actions: ".implode(', ', array_keys(self::ACTIONS)));
        }

        return $this->recordStat($game, $teamSide, $jersey, $actionCode, $input);
    }

    /**
     * Record a volleyball stat action.
     */
    public function recordStat(Game $game, string $teamSide, string $jersey, string $actionCode, ?string $rawInput = null): array
    {
        $actionDef = self::ACTIONS[$actionCode] ?? ['name' => $actionCode, 'type' => 'custom', 'point_team' => null];

        return DB::transaction(function () use ($game, $teamSide, $jersey, $actionCode, $actionDef, $rawInput) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $lineup = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $jersey)
                ->first();

            $playerName = $lineup?->player_name ?? "Player #{$jersey}";
            $playerId = $lineup?->player_id;

            $pointSide = null;
            if (isset($actionDef['point_team'])) {
                if ($actionDef['point_team'] === 'same') {
                    $pointSide = $teamSide;
                } elseif ($actionDef['point_team'] === 'opp') {
                    $pointSide = ($teamSide === 'home') ? 'away' : 'home';
                }
            }

            // Points update
            $homeScoreAfter = $game->home_score + ($pointSide === 'home' ? 1 : 0);
            $awayScoreAfter = $game->away_score + ($pointSide === 'away' ? 1 : 0);

            // Check for side-out rotation
            $sideOutOccurred = false;
            if ($pointSide !== null && $pointSide !== $game->current_server) {
                // Side-out: serving switches to winning team, that team rotates
                $sideOutOccurred = true;
                $game->current_server = $pointSide;
                if ($pointSide === 'home') {
                    $game->home_rotation = ($game->home_rotation % 6) + 1;
                } else {
                    $game->away_rotation = ($game->away_rotation % 6) + 1;
                }
            }

            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;

            $event = GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $lastSequence + 1,
                'period' => $game->current_period,
                'clock_seconds_remaining' => 0,
                'team_side' => $teamSide,
                'player_id' => $playerId,
                'jersey_number' => $jersey,
                'player_name' => $playerName,
                'sport' => 'volleyball',
                'action_code' => $actionCode,
                'action_type' => $actionDef['type'] ?? 'stat',
                'action_name' => $actionDef['name'] ?? $actionCode,
                'raw_input' => $rawInput ?? "{$jersey}".($teamSide === 'home' ? '-' : '=').$actionCode,
                'points' => ($pointSide !== null) ? 1 : 0,
                'home_score_after' => $homeScoreAfter,
                'away_score_after' => $awayScoreAfter,
                'is_undone' => false,
                'description' => strtoupper($teamSide)." #{$jersey} {$playerName}: ".($actionDef['name'] ?? $actionCode).($pointSide ? ' (Point '.strtoupper($pointSide).')' : ''),
            ]);

            $game->home_score = $homeScoreAfter;
            $game->away_score = $awayScoreAfter;

            // Update set scores
            $set = $game->current_period;
            $homePeriodScores = $game->home_period_scores ?? [0, 0, 0, 0, 0];
            $awayPeriodScores = $game->away_period_scores ?? [0, 0, 0, 0, 0];

            while (count($homePeriodScores) < $set) {
                $homePeriodScores[] = 0;
            }
            while (count($awayPeriodScores) < $set) {
                $awayPeriodScores[] = 0;
            }

            if ($pointSide === 'home') {
                $homePeriodScores[$set - 1] += 1;
            }
            if ($pointSide === 'away') {
                $awayPeriodScores[$set - 1] += 1;
            }

            $game->home_period_scores = $homePeriodScores;
            $game->away_period_scores = $awayPeriodScores;

            // Check if set is won (Set 1-4 to 25 win by 2, Set 5 to 15 win by 2)
            $targetScore = ($set === 5) ? 15 : 25;
            $homeSetScore = $homePeriodScores[$set - 1];
            $awaySetScore = $awayPeriodScores[$set - 1];

            $setWon = false;
            $setWinner = null;
            if ($homeSetScore >= $targetScore && ($homeSetScore - $awaySetScore) >= 2) {
                $setWon = true;
                $setWinner = 'home';
            } elseif ($awaySetScore >= $targetScore && ($awaySetScore - $homeSetScore) >= 2) {
                $setWon = true;
                $setWinner = 'away';
            }

            $game->save();

            // Update player volleyball stats
            $this->applyStatToPlayer($game->id, $teamSide, $jersey, $playerName, $playerId, $actionDef);

            return [
                'success' => true,
                'message' => strtoupper($teamSide)." - {$playerName} (#{$jersey}): ".($actionDef['name'] ?? $actionCode).($pointSide ? ' (Pt '.strtoupper($pointSide).')' : ''),
                'event' => $event,
                'game' => $game,
                'set_won' => $setWon,
                'set_winner' => $setWinner,
                'side_out' => $sideOutOccurred,
            ];
        });
    }

    /**
     * Log and charge a timeout.
     */
    public function callTimeout(Game $game, string $teamSide): GameEvent
    {
        return DB::transaction(function () use ($game, $teamSide) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $field = ($teamSide === 'home') ? 'home_timeouts_remaining' : 'away_timeouts_remaining';
            if ($game->$field > 0) {
                $game->$field -= 1;
            }
            $game->save();

            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;
            $teamName = ($teamSide === 'home') ? ($game->home_team_name ?: 'HOME') : ($game->away_team_name ?: 'AWAY');
            $remaining = $game->$field;

            return GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $lastSequence + 1,
                'period' => $game->current_period,
                'clock_seconds_remaining' => 0,
                'team_side' => $teamSide,
                'jersey_number' => null,
                'player_name' => $teamName,
                'sport' => 'volleyball',
                'action_code' => 'TIMEOUT',
                'action_type' => 'timeout',
                'action_name' => 'Timeout',
                'raw_input' => "TIMEOUT {$teamSide}",
                'points' => 0,
                'home_score_after' => $game->home_score,
                'away_score_after' => $game->away_score,
                'is_undone' => false,
                'description' => strtoupper($teamSide)." Timeout Called ({$remaining} remaining in Set {$game->current_period})",
                'metadata' => [
                    'timeouts_remaining' => $remaining,
                ],
            ]);
        });
    }

    /**
     * Log a manual score adjustment.
     */
    public function adjustScore(Game $game, string $teamSide, int $delta, ?string $reason = null): GameEvent
    {
        return DB::transaction(function () use ($game, $teamSide, $delta, $reason) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();
            $period = $game->current_period;

            if ($teamSide === 'home') {
                $game->home_score = max(0, $game->home_score + $delta);
                $scores = $game->home_period_scores ?? [0, 0, 0, 0, 0];
                while (count($scores) < $period) {
                    $scores[] = 0;
                }
                $scores[$period - 1] = max(0, $scores[$period - 1] + $delta);
                $game->home_period_scores = $scores;
            } else {
                $game->away_score = max(0, $game->away_score + $delta);
                $scores = $game->away_period_scores ?? [0, 0, 0, 0, 0];
                while (count($scores) < $period) {
                    $scores[] = 0;
                }
                $scores[$period - 1] = max(0, $scores[$period - 1] + $delta);
                $game->away_period_scores = $scores;
            }
            $game->save();

            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;
            $ptsText = ($delta > 0 ? "+{$delta}" : "{$delta}").' pts';
            $desc = strtoupper($teamSide)." Score Adjustment: {$ptsText}".($reason ? " ({$reason})" : '');

            return GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => 0,
                'team_side' => $teamSide,
                'jersey_number' => null,
                'player_name' => 'Official Scorer',
                'sport' => 'volleyball',
                'action_code' => 'SCORE_ADJ',
                'action_type' => 'score_adjustment',
                'action_name' => 'Score Adjustment',
                'raw_input' => "SCORE_ADJ {$teamSide} {$delta}",
                'points' => $delta,
                'home_score_after' => $game->home_score,
                'away_score_after' => $game->away_score,
                'is_undone' => false,
                'description' => $desc,
                'metadata' => [
                    'delta' => $delta,
                    'reason' => $reason,
                ],
            ]);
        });
    }

    /**
     * Create a manual / retroactively inserted event.
     */
    public function createManualEvent(Game $game, array $data): GameEvent
    {
        return DB::transaction(function () use ($game, $data) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $teamSide = $data['team_side'] ?? 'home';
            $jersey = isset($data['jersey_number']) ? trim((string) $data['jersey_number']) : null;
            $period = (int) ($data['period'] ?? $game->current_period);
            $actionCode = $data['action_code'] ?? 'NOTE';
            $actionDef = self::ACTIONS[$actionCode] ?? ['name' => $actionCode, 'type' => 'custom', 'point_team' => null];

            $playerName = $data['player_name'] ?? null;
            $playerId = null;
            if ($jersey) {
                $lineup = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('jersey_number', $jersey)
                    ->first();
                if ($lineup) {
                    $playerName = $playerName ?: $lineup->player_name;
                    $playerId = $lineup->player_id;
                } else {
                    $playerName = $playerName ?: "Player #{$jersey}";
                }
            }

            $points = isset($data['points']) ? (int) $data['points'] : (isset($actionDef['point_team']) && $actionDef['point_team'] !== null ? 1 : 0);
            $actionName = $actionDef['name'] ?? $actionCode;
            $description = $data['description'] ?? (
                $jersey
                    ? strtoupper($teamSide)." #{$jersey} {$playerName}: {$actionName}".($points > 0 ? " (+{$points} pts)" : '')
                    : strtoupper($teamSide).": {$actionName}".($points > 0 ? " (+{$points} pts)" : '')
            );

            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;

            $event = GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => 0,
                'team_side' => $teamSide,
                'player_id' => $playerId,
                'jersey_number' => $jersey,
                'player_name' => $playerName,
                'sport' => 'volleyball',
                'action_code' => $actionCode,
                'action_type' => $actionDef['type'] ?? 'manual_event',
                'action_name' => $actionName,
                'raw_input' => $data['raw_input'] ?? 'MANUAL_ENTRY',
                'points' => $points,
                'home_score_after' => $game->home_score,
                'away_score_after' => $game->away_score,
                'is_undone' => false,
                'description' => $description,
                'metadata' => $data['metadata'] ?? null,
            ]);

            $this->rebuildGameFromEvents($game);

            return $event;
        });
    }

    /**
     * Apply stat to player
     */
    protected function applyStatToPlayer(int $gameId, string $teamSide, string $jersey, string $playerName, ?int $playerId, array $actionDef): void
    {
        $stat = VolleyballStat::firstOrCreate(
            [
                'game_id' => $gameId,
                'team_side' => $teamSide,
                'jersey_number' => $jersey,
            ],
            [
                'player_id' => $playerId,
                'player_name' => $playerName,
            ]
        );

        if (! empty($actionDef['stat'])) {
            $col = $actionDef['stat'];
            $stat->$col += 1;
        }

        if (! empty($actionDef['attempt'])) {
            $stat->attack_attempts += 1;
        }

        if (! empty($actionDef['serve_attempt'])) {
            $stat->service_attempts += 1;
        }

        $stat->total_blocks = $stat->block_solos + ($stat->block_assists * 0.5);
        $stat->total_points = $stat->kills + $stat->service_aces + $stat->block_solos + ($stat->block_assists * 0.5);

        if ($stat->attack_attempts > 0) {
            $stat->hitting_percentage = round(($stat->kills - $stat->attack_errors) / $stat->attack_attempts, 3);
        }

        $stat->save();
    }

    /**
     * Completely recompute score and stats from chronological events.
     */
    public function rebuildGameFromEvents(Game $game): void
    {
        $events = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->orderBy('sequence', 'asc')
            ->get();

        $homeScore = 0;
        $awayScore = 0;
        $homePeriodScores = [0, 0, 0, 0, 0];
        $awayPeriodScores = [0, 0, 0, 0, 0];
        $homeTimeoutsUsed = 0;
        $awayTimeoutsUsed = 0;

        VolleyballStat::where('game_id', $game->id)->update([
            'kills' => 0,
            'attack_errors' => 0,
            'attack_attempts' => 0,
            'service_aces' => 0,
            'service_errors' => 0,
            'service_attempts' => 0,
            'digs' => 0,
            'block_solos' => 0,
            'block_assists' => 0,
            'total_blocks' => 0,
            'ball_handling_errors' => 0,
            'reception_errors' => 0,
            'assists' => 0,
            'total_points' => 0,
            'hitting_percentage' => 0,
        ]);

        foreach ($events as $event) {
            $period = (int) $event->period;
            while (count($homePeriodScores) < $period) {
                $homePeriodScores[] = 0;
            }
            while (count($awayPeriodScores) < $period) {
                $awayPeriodScores[] = 0;
            }

            $points = (int) $event->points;
            if ($points > 0) {
                if ($event->team_side === 'home') {
                    $homeScore += $points;
                    $homePeriodScores[$period - 1] += $points;
                } elseif ($event->team_side === 'away') {
                    $awayScore += $points;
                    $awayPeriodScores[$period - 1] += $points;
                }
            }

            if ($event->action_code === 'TIMEOUT') {
                if ($event->team_side === 'home') {
                    $homeTimeoutsUsed++;
                }
                if ($event->team_side === 'away') {
                    $awayTimeoutsUsed++;
                }
            }

            $event->home_score_after = $homeScore;
            $event->away_score_after = $awayScore;
            $event->saveQuietly();

            if (isset(self::ACTIONS[$event->action_code]) && ! empty($event->jersey_number)) {
                $this->applyStatToPlayer(
                    $game->id,
                    $event->team_side,
                    $event->jersey_number,
                    $event->player_name ?? "Player #{$event->jersey_number}",
                    $event->player_id,
                    self::ACTIONS[$event->action_code]
                );
            }
        }

        $game->home_score = max(0, $homeScore);
        $game->away_score = max(0, $awayScore);
        $game->home_period_scores = $homePeriodScores;
        $game->away_period_scores = $awayPeriodScores;

        $maxTimeouts = (int) ($game->settings['rules']['timeouts_per_set'] ?? ($game->settings['timeouts_per_set'] ?? 2));
        $game->home_timeouts_remaining = max(0, $maxTimeouts - $homeTimeoutsUsed);
        $game->away_timeouts_remaining = max(0, $maxTimeouts - $awayTimeoutsUsed);

        $game->save();
    }

    /**
     * Undo the last active game event.
     */
    public function undoLastEvent(Game $game): ?GameEvent
    {
        return DB::transaction(function () use ($game) {
            $event = GameEvent::where('game_id', $game->id)
                ->where('is_undone', false)
                ->orderBy('sequence', 'desc')
                ->first();

            if (! $event) {
                return null;
            }

            $event->is_undone = true;
            $event->save();

            $this->rebuildGameFromEvents($game);

            return $event;
        });
    }

    /**
     * Delete a specific event by ID.
     */
    public function deleteEvent(Game $game, int $eventId): ?GameEvent
    {
        return DB::transaction(function () use ($game, $eventId) {
            $event = GameEvent::where('game_id', $game->id)->where('id', $eventId)->first();
            if (! $event) {
                return null;
            }

            $event->is_undone = true;
            $event->save();

            $this->rebuildGameFromEvents($game);

            return $event;
        });
    }

    /**
     * Update an event by ID and recompute game state.
     */
    public function updateEvent(Game $game, int $eventId, array $data): ?GameEvent
    {
        return DB::transaction(function () use ($game, $eventId, $data) {
            $event = GameEvent::where('game_id', $game->id)->where('id', $eventId)->first();
            if (! $event) {
                return null;
            }

            if (isset($data['team_side'])) {
                $event->team_side = $data['team_side'];
            }

            if (array_key_exists('jersey_number', $data)) {
                $event->jersey_number = $data['jersey_number'] ? trim((string) $data['jersey_number']) : null;
                if ($event->jersey_number) {
                    $lineup = GameLineup::where('game_id', $game->id)
                        ->where('team_side', $event->team_side)
                        ->where('jersey_number', $event->jersey_number)
                        ->first();
                    if ($lineup) {
                        $event->player_name = $lineup->player_name;
                        $event->player_id = $lineup->player_id;
                    }
                }
            }

            if (isset($data['player_name'])) {
                $event->player_name = $data['player_name'];
            }

            if (isset($data['action_code']) && isset(self::ACTIONS[$data['action_code']])) {
                $actionDef = self::ACTIONS[$data['action_code']];
                $event->action_code = $data['action_code'];
                $event->action_name = $actionDef['name'];
                $event->action_type = $actionDef['type'];
                $event->points = (isset($actionDef['point_team']) && $actionDef['point_team'] !== null) ? 1 : ($actionDef['points'] ?? 0);
            }

            if (isset($data['points'])) {
                $event->points = (int) $data['points'];
            }

            if (isset($data['period'])) {
                $event->period = (int) $data['period'];
            }

            if (isset($data['description'])) {
                $event->description = trim($data['description']);
            } else {
                $pts = (int) $event->points;
                $event->description = strtoupper($event->team_side).($event->jersey_number ? " #{$event->jersey_number} {$event->player_name}" : '').": {$event->action_name}".($pts > 0 ? " (+{$pts} pts)" : '');
            }

            $event->save();

            $this->rebuildGameFromEvents($game);

            return $event;
        });
    }

    /**
     * Advance Set
     */
    public function advanceSet(Game $game): void
    {
        $game->current_period += 1;
        $game->home_timeouts_remaining = (int) ($game->settings['rules']['timeouts_per_set'] ?? ($game->settings['timeouts_per_set'] ?? 2));
        $game->away_timeouts_remaining = (int) ($game->settings['rules']['timeouts_per_set'] ?? ($game->settings['timeouts_per_set'] ?? 2));
        $game->save();

        $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;
        GameEvent::create([
            'game_id' => $game->id,
            'sequence' => $lastSequence + 1,
            'period' => $game->current_period,
            'clock_seconds_remaining' => 0,
            'team_side' => 'home',
            'player_name' => 'Set Advance',
            'sport' => 'volleyball',
            'action_code' => 'PERIOD',
            'action_type' => 'period_change',
            'action_name' => "Set {$game->current_period} Started",
            'points' => 0,
            'home_score_after' => $game->home_score,
            'away_score_after' => $game->away_score,
            'description' => "Set {$game->current_period} Started",
        ]);
    }

    /**
     * Substitution
     */
    public function substitute(Game $game, string $teamSide, string $subOutJersey, string $subInJersey): void
    {
        DB::transaction(function () use ($game, $teamSide, $subOutJersey, $subInJersey) {
            $subOut = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $subOutJersey)
                ->first();

            $subIn = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $subInJersey)
                ->first();

            if ($subOut) {
                $subOut->is_on_court = false;
                $subOut->save();
            }

            if ($subIn) {
                $subIn->is_on_court = true;
                $subIn->save();
            }

            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;
            GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $lastSequence + 1,
                'period' => $game->current_period,
                'clock_seconds_remaining' => 0,
                'team_side' => $teamSide,
                'jersey_number' => $subInJersey,
                'player_name' => $subIn?->player_name ?? "#{$subInJersey}",
                'sport' => 'volleyball',
                'action_code' => 'SUB',
                'action_type' => 'substitution',
                'action_name' => 'Substitution',
                'raw_input' => "SUB {$teamSide} {$subOutJersey}->{$subInJersey}",
                'points' => 0,
                'home_score_after' => $game->home_score,
                'away_score_after' => $game->away_score,
                'description' => strtoupper($teamSide)." Sub: OUT #{$subOutJersey}, IN #{$subInJersey}",
                'metadata' => [
                    'sub_out_jersey' => $subOutJersey,
                    'sub_in_jersey' => $subInJersey,
                ],
            ]);
        });
    }
}
