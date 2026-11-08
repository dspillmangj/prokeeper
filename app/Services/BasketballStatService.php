<?php

namespace App\Services;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BasketballStatService
{
    public function __construct(
        protected ?EventSourcedReplayService $replayService = null
    ) {
        $this->replayService = $replayService ?? app(EventSourcedReplayService::class);
    }

    /**
     * Action code mapping matching legacy syntax and expanded stat options.
     */
    public const ACTIONS = [
        'X' => ['name' => '2pt MAKE', 'type' => '2pt_make', 'points' => 2, 'stat' => 'fgm', 'attempts' => 'fga', 'category' => 'scoring'],
        'Z' => ['name' => '2pt Miss', 'type' => '2pt_miss', 'points' => 0, 'attempts' => 'fga', 'category' => 'scoring'],
        'M' => ['name' => '3pt MAKE', 'type' => '3pt_make', 'points' => 3, 'stat' => 'fg3m', 'attempts' => 'fg3a', 'category' => 'scoring'],
        'N' => ['name' => '3pt Miss', 'type' => '3pt_miss', 'points' => 0, 'attempts' => 'fg3a', 'category' => 'scoring'],
        'B' => ['name' => 'FT MAKE', 'type' => 'ft_make', 'points' => 1, 'stat' => 'ftm', 'attempts' => 'fta', 'category' => 'scoring'],
        'V' => ['name' => 'FT Miss', 'type' => 'ft_miss', 'points' => 0, 'attempts' => 'fta', 'category' => 'scoring'],
        'D' => ['name' => 'Def Reb', 'type' => 'def_reb', 'points' => 0, 'stat' => 'dreb', 'category' => 'rebounds'],
        'O' => ['name' => 'Off Reb', 'type' => 'off_reb', 'points' => 0, 'stat' => 'oreb', 'category' => 'rebounds'],
        'A' => ['name' => 'Assist', 'type' => 'assist', 'points' => 0, 'stat' => 'ast', 'category' => 'ball_movement'],
        'S' => ['name' => 'Steal', 'type' => 'steal', 'points' => 0, 'stat' => 'stl', 'category' => 'defense'],
        'K' => ['name' => 'Block', 'type' => 'block', 'points' => 0, 'stat' => 'blk', 'category' => 'defense'],
        'W' => ['name' => 'Swat', 'type' => 'swat', 'points' => 0, 'stat' => 'swat', 'category' => 'defense'],
        'P' => ['name' => 'Passing Turnover', 'type' => 'turnover_pass', 'points' => 0, 'stat' => 'to_pass', 'category' => 'turnovers'],
        'U' => ['name' => 'Fumble Turnover', 'type' => 'turnover_fumble', 'points' => 0, 'stat' => 'to_fumble', 'category' => 'turnovers'],
        'I' => ['name' => 'Violation Turnover', 'type' => 'turnover_violation', 'points' => 0, 'stat' => 'to_violation', 'category' => 'turnovers'],
        'F' => ['name' => 'Pers Foul', 'type' => 'foul_pers', 'points' => 0, 'stat' => 'fouls_pers', 'category' => 'fouls'],
        'R' => ['name' => 'Off Foul', 'type' => 'foul_off', 'points' => 0, 'stat' => 'fouls_off', 'category' => 'fouls'],
        'T' => ['name' => 'Tech Foul', 'type' => 'foul_tech', 'points' => 0, 'stat' => 'fouls_tech', 'category' => 'fouls'],
        'G' => ['name' => 'Flagrant Foul', 'type' => 'foul_flagrant', 'points' => 0, 'stat' => 'fouls_flagrant', 'category' => 'fouls'],
        'FL' => ['name' => 'Flagrant Foul', 'type' => 'foul_flagrant', 'points' => 0, 'stat' => 'fouls_flagrant', 'category' => 'fouls'],
        'FLAG' => ['name' => 'Flagrant Foul', 'type' => 'foul_flagrant', 'points' => 0, 'stat' => 'fouls_flagrant', 'category' => 'fouls'],
        'H' => ['name' => 'Drawn Foul', 'type' => 'foul_drawn', 'points' => 0, 'stat' => 'fouls_forced', 'category' => 'fouls'],
        'TIMEOUT' => ['name' => 'Timeout', 'type' => 'timeout', 'points' => 0, 'category' => 'administrative'],
        'SCORE_ADJ' => ['name' => 'Score Adjustment', 'type' => 'score_adjustment', 'points' => 0, 'category' => 'administrative'],
        'SUB' => ['name' => 'Substitution', 'type' => 'substitution', 'points' => 0, 'category' => 'administrative'],
        'PERIOD' => ['name' => 'Period Advance', 'type' => 'period_change', 'points' => 0, 'category' => 'administrative'],
        'NOTE' => ['name' => 'Audit Note', 'type' => 'audit_note', 'points' => 0, 'category' => 'administrative'],
    ];

    /**
     * Process raw keyboard input string: e.g. "23-X", "11=M", "05-D"
     */
    public function processKeyboardInput(Game $game, string $input, ?int $clockSeconds = null): array
    {
        $input = trim($input);
        if (empty($input)) {
            throw new Exception('No input provided.');
        }

        // Fast physical scoreboard clock sync shortcut: e.g. t2000, t0800, t0530, t0005, t00055, t8:00
        if (preg_match('/^t([0-9]{1,5}|[0-9]{1,2}:[0-9]{2})$/i', $input)) {
            $parsedSeconds = $this->replayService->parseClockInput($input);
            if ($parsedSeconds !== null) {
                $event = $this->replayService->syncClock($game, $parsedSeconds);

                return [
                    'success' => true,
                    'message' => $event->description,
                    'event' => $event,
                    'game' => $game->fresh(),
                ];
            }
        }

        // Support formats: 23-X (Home) or 23=X (Away), with optional spaces and optional assist (e.g. 23-X:10, 23-X/10, 23-XA10, 23-xa7)
        if (! preg_match('/^([0-9]{1,3}|00)\s*([=\-])\s*([A-Za-z]+?)(?:(?:\s*[:\/]\s*|\s*[aA]\s*)([0-9]{1,3}|00))?$/', $input, $matches)) {
            throw new Exception("Invalid input format. Use '<Jersey>-<Action>' for Home or '<Jersey>=<Action>' for Away (e.g. 23-X or 23-X:10 with assist).");
        }

        $jersey = $matches[1];
        $teamIndicator = $matches[2];
        $actionCode = strtoupper($matches[3]);
        $assistJersey = ! empty($matches[4]) ? $matches[4] : null;

        $teamSide = ($teamIndicator === '-') ? 'home' : 'away';

        if (! isset(self::ACTIONS[$actionCode])) {
            throw new Exception("Unknown action '{$actionCode}'. Supported actions: ".implode(', ', array_keys(self::ACTIONS)));
        }

        return $this->recordStat($game, $teamSide, $jersey, $actionCode, $input, $clockSeconds, $assistJersey);
    }

    /**
     * Record a specific stat action for a player.
     */
    public function recordStat(Game $game, string $teamSide, string $jersey, string $actionCode, ?string $rawInput = null, ?int $clockSeconds = null, ?string $assistJersey = null): array
    {
        $actionDef = self::ACTIONS[$actionCode] ?? ['name' => $actionCode, 'type' => 'custom', 'points' => 0];

        return DB::transaction(function () use ($game, $teamSide, $jersey, $actionCode, $actionDef, $rawInput, $clockSeconds, $assistJersey) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $lineup = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $jersey)
                ->first();

            $playerName = $lineup?->player_name ?? "Player #{$jersey}";
            $playerId = $lineup?->player_id;

            $assistPlayerId = null;
            $assistPlayerName = null;
            if (! empty($assistJersey)) {
                $assistLineup = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('jersey_number', $assistJersey)
                    ->first();
                $assistPlayerId = $assistLineup?->player_id;
                $assistPlayerName = $assistLineup?->player_name ?? "Player #{$assistJersey}";
            }

            $clock = $clockSeconds ?? $game->clock_seconds_remaining;
            $period = $game->current_period;

            // Score calculation
            $points = $actionDef['points'] ?? 0;
            $homeScoreAfter = $game->home_score + ($teamSide === 'home' ? $points : 0);
            $awayScoreAfter = $game->away_score + ($teamSide === 'away' ? $points : 0);

            // Sequence count
            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;

            $desc = strtoupper($teamSide)." #{$jersey} {$playerName}: ".($actionDef['name'] ?? $actionCode);
            if ($points > 0) {
                $desc .= " (+{$points} pts)";
            }
            if (! empty($assistJersey)) {
                $desc .= " (Ast: #{$assistJersey} {$assistPlayerName})";
            }

            // Create Game Event
            $event = GameEvent::create([
                'game_id' => $game->id,
                'uuid' => (string) Str::uuid(),
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => $clock,
                'wall_clock_time' => now(),
                'team_side' => $teamSide,
                'player_id' => $playerId,
                'jersey_number' => $jersey,
                'player_name' => $playerName,
                'assist_player_id' => $assistPlayerId,
                'assist_jersey_number' => $assistJersey,
                'sport' => 'basketball',
                'action_code' => $actionCode,
                'action_type' => $actionDef['type'] ?? 'stat',
                'action_name' => $actionDef['name'] ?? $actionCode,
                'raw_input' => $rawInput ?? "{$jersey}".($teamSide === 'home' ? '-' : '=').$actionCode.(! empty($assistJersey) ? ":{$assistJersey}" : ''),
                'points' => $points,
                'home_score_after' => $homeScoreAfter,
                'away_score_after' => $awayScoreAfter,
                'is_undone' => false,
                'description' => $desc,
            ]);

            // Deterministic Replay: updates scores, period fouls, stats, and materialized snapshots on events
            $this->rebuildGameFromEvents($game);

            $message = strtoupper($teamSide)." - {$playerName} (#{$jersey}): ".($actionDef['name'] ?? $actionCode).($points > 0 ? " (+{$points} pts)" : '');
            if (! empty($assistJersey)) {
                $message .= " (Ast: #{$assistJersey} {$assistPlayerName})";
            }

            return [
                'success' => true,
                'message' => $message,
                'event' => $event,
                'game' => $game,
            ];
        });
    }

    /**
     * Attach an assist to the last made field goal (or specified event) for a team.
     */
    public function attachAssist(Game $game, string $teamSide, string $assistJersey, ?int $eventId = null): array
    {
        return DB::transaction(function () use ($game, $teamSide, $assistJersey, $eventId) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $eventQuery = GameEvent::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->whereIn('action_code', ['X', 'M'])
                ->where('is_undone', false);

            if ($eventId) {
                $event = (clone $eventQuery)->where('id', $eventId)->first()
                    ?? $eventQuery->orderBy('sequence', 'desc')->first();
            } else {
                $event = $eventQuery->orderBy('sequence', 'desc')->first();
            }

            if (! $event) {
                // Fallback: If no previous shot event found, record an assist standalone
                return $this->recordStat($game, $teamSide, $assistJersey, 'A');
            }

            $assistLineup = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $assistJersey)
                ->first();
            $assistPlayerId = $assistLineup?->player_id;
            $assistPlayerName = $assistLineup?->player_name ?? "Player #{$assistJersey}";

            // If this event already had a different assist, decrement the old player's ast
            if (! empty($event->assist_jersey_number) && (string) $event->assist_jersey_number !== (string) $assistJersey) {
                $oldStat = BasketballStat::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('jersey_number', $event->assist_jersey_number)
                    ->first();
                if ($oldStat && $oldStat->ast > 0) {
                    $oldStat->ast -= 1;
                    $oldStat->save();
                }
            }

            $event->assist_jersey_number = $assistJersey;
            $event->assist_player_id = $assistPlayerId;

            // Update description with assist suffix
            $cleanDesc = preg_replace('/\s*\(Ast:\s*#[^)]+\)/i', '', $event->description);
            $event->description = "{$cleanDesc} (Ast: #{$assistJersey} {$assistPlayerName})";
            $event->save();

            // Apply assist stat to player
            $this->applyStatToPlayer($game->id, $teamSide, $assistJersey, $assistPlayerName, $assistPlayerId, self::ACTIONS['A']);

            $message = strtoupper($teamSide)." #{$event->jersey_number} {$event->player_name}: Assist credited to #{$assistJersey} {$assistPlayerName}";

            return [
                'success' => true,
                'message' => $message,
                'event' => $event,
                'game' => $game,
            ];
        });
    }

    /**
     * Log and charge a timeout.
     */
    public function callTimeout(Game $game, string $teamSide, string $timeoutType = 'full', ?int $clockSeconds = null): GameEvent
    {
        return DB::transaction(function () use ($game, $teamSide, $timeoutType, $clockSeconds) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $field = ($teamSide === 'home') ? 'home_timeouts_remaining' : 'away_timeouts_remaining';
            if ($game->$field > 0) {
                $game->$field -= 1;
            }
            $game->save();

            $clock = $clockSeconds ?? $game->clock_seconds_remaining;
            $period = $game->current_period;
            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;

            $teamName = ($teamSide === 'home') ? ($game->home_team_name ?: 'HOME') : ($game->away_team_name ?: 'AWAY');
            $remaining = $game->$field;
            $typeLabel = ($timeoutType === '30s') ? '30-Second' : 'Full (60s)';

            $breakdown = $game->calculateTimeoutsBreakdown($teamSide);
            $remFull = $breakdown['rem_full'];
            $rem30s = $breakdown['rem_30s'];
            $usedFull = $breakdown['used_full'];
            $used30s = $breakdown['used_30s'];
            if ($timeoutType === '30s') {
                $rem30s = max(0, $rem30s - 1);
                $used30s++;
            } else {
                $remFull = max(0, $remFull - 1);
                $usedFull++;
            }
            $usedTotal = $usedFull + $used30s;

            $event = GameEvent::create([
                'game_id' => $game->id,
                'uuid' => (string) Str::uuid(),
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => $clock,
                'wall_clock_time' => now(),
                'team_side' => $teamSide,
                'jersey_number' => null,
                'player_name' => $teamName,
                'sport' => 'basketball',
                'action_code' => 'TIMEOUT',
                'action_type' => 'timeout',
                'action_name' => "{$typeLabel} Timeout",
                'raw_input' => "TIMEOUT {$teamSide} {$timeoutType}",
                'points' => 0,
                'home_score_after' => $game->home_score,
                'away_score_after' => $game->away_score,
                'is_undone' => false,
                'description' => strtoupper($teamSide)." {$typeLabel} Timeout Called ({$usedTotal} Taken [{$usedFull} Full, {$used30s} 30s] • {$remaining} Remain [{$remFull} Full, {$rem30s} 30s])",
                'metadata' => [
                    'timeout_type' => $timeoutType,
                    'timeouts_remaining' => $remaining,
                    'full_timeouts_remaining' => $remFull,
                    'thirty_second_timeouts_remaining' => $rem30s,
                    'timeouts_taken' => $usedTotal,
                    'full_timeouts_taken' => $usedFull,
                    'thirty_second_timeouts_taken' => $used30s,
                ],
            ]);

            $this->rebuildGameFromEvents($game);

            return $event;
        });
    }

    /**
     * Log a manual score adjustment.
     */
    public function adjustScore(Game $game, string $teamSide, int $delta, ?int $clockSeconds = null, ?string $reason = null): GameEvent
    {
        return DB::transaction(function () use ($game, $teamSide, $delta, $clockSeconds, $reason) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $clock = $clockSeconds ?? $game->clock_seconds_remaining;
            $period = $game->current_period;

            $defaultScores = ($game->period_format === 'halves') ? [0, 0] : [0, 0, 0, 0];

            if ($teamSide === 'home') {
                $game->home_score = max(0, $game->home_score + $delta);
                $scores = $game->home_period_scores ?? $defaultScores;
                while (count($scores) < $period) {
                    $scores[] = 0;
                }
                $scores[$period - 1] = max(0, $scores[$period - 1] + $delta);
                $game->home_period_scores = $scores;
            } else {
                $game->away_score = max(0, $game->away_score + $delta);
                $scores = $game->away_period_scores ?? $defaultScores;
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

            $event = GameEvent::create([
                'game_id' => $game->id,
                'uuid' => (string) Str::uuid(),
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => $clock,
                'wall_clock_time' => now(),
                'team_side' => $teamSide,
                'jersey_number' => null,
                'player_name' => 'Official Scorer',
                'sport' => 'basketball',
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

            $this->rebuildGameFromEvents($game);

            return $event;
        });
    }

    /**
     * Create a manual / retroactively inserted event in the audit log.
     */
    public function createManualEvent(Game $game, array $data): GameEvent
    {
        return DB::transaction(function () use ($game, $data) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $teamSide = $data['team_side'] ?? 'home';
            $jersey = isset($data['jersey_number']) ? trim((string) $data['jersey_number']) : null;
            $period = (int) ($data['period'] ?? $game->current_period);
            $clock = isset($data['clock_seconds_remaining']) ? (int) $data['clock_seconds_remaining'] : $game->clock_seconds_remaining;
            $actionCode = $data['action_code'] ?? 'NOTE';
            $actionDef = self::ACTIONS[$actionCode] ?? ['name' => $actionCode, 'type' => 'custom', 'points' => 0];

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

            $points = isset($data['points']) ? (int) $data['points'] : ($actionDef['points'] ?? 0);
            $actionName = $actionDef['name'] ?? $actionCode;
            $description = $data['description'] ?? (
                $jersey
                    ? strtoupper($teamSide)." #{$jersey} {$playerName}: {$actionName}".($points > 0 ? " (+{$points} pts)" : '')
                    : strtoupper($teamSide).": {$actionName}".($points > 0 ? " (+{$points} pts)" : '')
            );

            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;

            $event = GameEvent::create([
                'game_id' => $game->id,
                'uuid' => (string) Str::uuid(),
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => $clock,
                'wall_clock_time' => now(),
                'team_side' => $teamSide,
                'player_id' => $playerId,
                'jersey_number' => $jersey,
                'player_name' => $playerName,
                'sport' => 'basketball',
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
     * Apply the stat increment to the basketball_stats table.
     */
    protected function applyStatToPlayer(int $gameId, string $teamSide, string $jersey, string $playerName, ?int $playerId, array $actionDef): void
    {
        $stat = BasketballStat::firstOrCreate(
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

        if (! empty($actionDef['points'])) {
            $stat->points += $actionDef['points'];
        }

        // Increment specific count column
        if (isset($actionDef['stat'])) {
            $col = $actionDef['stat'];
            $stat->$col += 1;

            if (in_array($col, ['dreb', 'oreb'])) {
                $stat->reb = $stat->dreb + $stat->oreb;
            }
            if (in_array($col, ['to_pass', 'to_fumble', 'to_violation'])) {
                $stat->turnovers = $stat->to_pass + $stat->to_fumble + $stat->to_violation;
            }
            if (in_array($col, ['fouls_pers', 'fouls_off', 'fouls_tech', 'fouls_flagrant'])) {
                $stat->total_fouls = $stat->fouls_pers + $stat->fouls_off + $stat->fouls_tech + ($stat->fouls_flagrant ?? 0);
            }
        }

        // If it was a make or attempt
        if (isset($actionDef['attempts'])) {
            $attCol = $actionDef['attempts'];
            $stat->$attCol += 1;

            // If it's a 3pt shot, it also counts as an overall FGA
            if ($attCol === 'fg3a') {
                $stat->fga += 1;
                if (! empty($actionDef['stat']) && $actionDef['stat'] === 'fg3m') {
                    $stat->fgm += 1;
                }
            }
        }

        $stat->save();
    }

    /**
     * Undo the most recent active event in the game.
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

            // Rebuild stats and scores from active events to guarantee 100% mathematical consistency
            $this->rebuildGameFromEvents($game);

            return $event;
        });
    }

    /**
     * Completely recompute score, fouls, timeouts, and stats from chronological events.
     */
    public function rebuildGameFromEvents(Game $game): void
    {
        $this->replayService->rebuildGameFromEvents($game);
    }

    public function recalculateGameTotals(Game $game): void
    {
        $this->rebuildGameFromEvents($game);
    }

    /**
     * Perform on-court player substitution.
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

            // Record substitution event
            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;
            GameEvent::create([
                'game_id' => $game->id,
                'uuid' => (string) Str::uuid(),
                'sequence' => $lastSequence + 1,
                'period' => $game->current_period,
                'clock_seconds_remaining' => $game->clock_seconds_remaining,
                'wall_clock_time' => now(),
                'team_side' => $teamSide,
                'jersey_number' => $subInJersey,
                'player_name' => $subIn?->player_name ?? "#{$subInJersey}",
                'sport' => 'basketball',
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

            $this->rebuildGameFromEvents($game);
        });
    }

    /**
     * Delete a specific event by ID and recompute game state.
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
                $event->points = $actionDef['points'] ?? 0;
            }

            if (isset($data['points'])) {
                $event->points = (int) $data['points'];
            }

            if (isset($data['period'])) {
                $event->period = (int) $data['period'];
            }

            if (isset($data['clock_seconds_remaining'])) {
                $event->clock_seconds_remaining = (int) $data['clock_seconds_remaining'];
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
}
