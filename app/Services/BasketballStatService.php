<?php

namespace App\Services;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use Exception;
use Illuminate\Support\Facades\DB;

class BasketballStatService
{
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
        'H' => ['name' => 'Forc Foul', 'type' => 'foul_forced', 'points' => 0, 'stat' => 'fouls_forced', 'category' => 'fouls'],
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

        // Support formats: 23-X (Home) or 23=X (Away), with optional spaces
        if (! preg_match('/^([0-9]{1,3}|00)\s*([=\-])\s*([A-Za-z]+)$/', $input, $matches)) {
            throw new Exception("Invalid input format. Use '<Jersey>-<Action>' for Home or '<Jersey>=<Action>' for Away (e.g. 23-X).");
        }

        $jersey = $matches[1];
        $teamIndicator = $matches[2];
        $actionCode = strtoupper($matches[3]);

        $teamSide = ($teamIndicator === '-') ? 'home' : 'away';

        if (! isset(self::ACTIONS[$actionCode])) {
            throw new Exception("Unknown action '{$actionCode}'. Supported actions: ".implode(', ', array_keys(self::ACTIONS)));
        }

        return $this->recordStat($game, $teamSide, $jersey, $actionCode, $input, $clockSeconds);
    }

    /**
     * Record a specific stat action for a player.
     */
    public function recordStat(Game $game, string $teamSide, string $jersey, string $actionCode, ?string $rawInput = null, ?int $clockSeconds = null): array
    {
        $actionDef = self::ACTIONS[$actionCode] ?? ['name' => $actionCode, 'type' => 'custom', 'points' => 0];

        return DB::transaction(function () use ($game, $teamSide, $jersey, $actionCode, $actionDef, $rawInput, $clockSeconds) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $lineup = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $jersey)
                ->first();

            $playerName = $lineup?->player_name ?? "Player #{$jersey}";
            $playerId = $lineup?->player_id;

            $clock = $clockSeconds ?? $game->clock_seconds_remaining;
            $period = $game->current_period;

            // Score calculation
            $points = $actionDef['points'] ?? 0;
            $homeScoreAfter = $game->home_score + ($teamSide === 'home' ? $points : 0);
            $awayScoreAfter = $game->away_score + ($teamSide === 'away' ? $points : 0);

            // Sequence count
            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;

            // Create Game Event
            $event = GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => $clock,
                'team_side' => $teamSide,
                'player_id' => $playerId,
                'jersey_number' => $jersey,
                'player_name' => $playerName,
                'sport' => 'basketball',
                'action_code' => $actionCode,
                'action_type' => $actionDef['type'] ?? 'stat',
                'action_name' => $actionDef['name'] ?? $actionCode,
                'raw_input' => $rawInput ?? "{$jersey}".($teamSide === 'home' ? '-' : '=').$actionCode,
                'points' => $points,
                'home_score_after' => $homeScoreAfter,
                'away_score_after' => $awayScoreAfter,
                'is_undone' => false,
                'description' => strtoupper($teamSide)." #{$jersey} {$playerName}: ".($actionDef['name'] ?? $actionCode),
            ]);

            // Update Game Score
            $game->home_score = $homeScoreAfter;
            $game->away_score = $awayScoreAfter;

            // Update period scores JSON
            $homePeriodScores = $game->home_period_scores ?? [0, 0, 0, 0];
            $awayPeriodScores = $game->away_period_scores ?? [0, 0, 0, 0];

            while (count($homePeriodScores) < $period) {
                $homePeriodScores[] = 0;
            }
            while (count($awayPeriodScores) < $period) {
                $awayPeriodScores[] = 0;
            }

            if ($teamSide === 'home') {
                $homePeriodScores[$period - 1] += $points;
            } else {
                $awayPeriodScores[$period - 1] += $points;
            }

            $game->home_period_scores = $homePeriodScores;
            $game->away_period_scores = $awayPeriodScores;

            // Handle Fouls & Bonus
            if (in_array($actionCode, ['F', 'R', 'T'])) {
                if ($teamSide === 'home') {
                    $game->home_fouls_current_period += 1;
                } else {
                    $game->away_fouls_current_period += 1;
                }
            }

            // Turnover / Defensive Rebound implies possession switch
            if (in_array($actionCode, ['D', 'S', 'P', 'U', 'I', 'R'])) {
                $game->possession_arrow = ($teamSide === 'home') ? 'away' : 'home';
            }

            $game->save();

            // Update BasketballStat table
            $this->applyStatToPlayer($game->id, $teamSide, $jersey, $playerName, $playerId, $actionDef);

            return [
                'success' => true,
                'message' => strtoupper($teamSide)." - {$playerName} (#{$jersey}): ".($actionDef['name'] ?? $actionCode).($points > 0 ? " (+{$points} pts)" : ''),
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
            if ($timeoutType === '30s') {
                $rem30s = max(0, $rem30s - 1);
            } else {
                $remFull = max(0, $remFull - 1);
            }

            return GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => $clock,
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
                'description' => strtoupper($teamSide)." {$typeLabel} Timeout Called ({$remFull} Full, {$rem30s} 30s remaining)",
                'metadata' => [
                    'timeout_type' => $timeoutType,
                    'timeouts_remaining' => $remaining,
                    'full_timeouts_remaining' => $remFull,
                    'thirty_second_timeouts_remaining' => $rem30s,
                ],
            ]);
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

            if ($teamSide === 'home') {
                $game->home_score = max(0, $game->home_score + $delta);
                $scores = $game->home_period_scores ?? [0, 0, 0, 0];
                while (count($scores) < $period) {
                    $scores[] = 0;
                }
                $scores[$period - 1] = max(0, $scores[$period - 1] + $delta);
                $game->home_period_scores = $scores;
            } else {
                $game->away_score = max(0, $game->away_score + $delta);
                $scores = $game->away_period_scores ?? [0, 0, 0, 0];
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
                'clock_seconds_remaining' => $clock,
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
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => $clock,
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
            if (in_array($col, ['fouls_pers', 'fouls_off', 'fouls_tech'])) {
                $stat->total_fouls = $stat->fouls_pers + $stat->fouls_off + $stat->fouls_tech;
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
        $events = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->orderBy('sequence', 'asc')
            ->get();

        // Reset game scores
        $homeScore = 0;
        $awayScore = 0;
        $homePeriodScores = [0, 0, 0, 0];
        $awayPeriodScores = [0, 0, 0, 0];
        $homeFouls = 0;
        $awayFouls = 0;
        $homeTimeoutsUsed = 0;
        $awayTimeoutsUsed = 0;

        // Reset basketball_stats
        BasketballStat::where('game_id', $game->id)->update([
            'points' => 0,
            'fgm' => 0,
            'fga' => 0,
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
            'to_pass' => 0,
            'to_fumble' => 0,
            'to_violation' => 0,
            'turnovers' => 0,
            'fouls_pers' => 0,
            'fouls_off' => 0,
            'fouls_tech' => 0,
            'fouls_forced' => 0,
            'total_fouls' => 0,
        ]);

        foreach ($events as $event) {
            $points = (int) $event->points;
            $period = (int) $event->period;

            while (count($homePeriodScores) < $period) {
                $homePeriodScores[] = 0;
            }
            while (count($awayPeriodScores) < $period) {
                $awayPeriodScores[] = 0;
            }

            if ($event->team_side === 'home') {
                $homeScore += $points;
                $homePeriodScores[$period - 1] += $points;
                if (in_array($event->action_code, ['F', 'R', 'T']) && $period === $game->current_period) {
                    $homeFouls += 1;
                }
                if ($event->action_code === 'TIMEOUT') {
                    $homeTimeoutsUsed += 1;
                }
            } elseif ($event->team_side === 'away') {
                $awayScore += $points;
                $awayPeriodScores[$period - 1] += $points;
                if (in_array($event->action_code, ['F', 'R', 'T']) && $period === $game->current_period) {
                    $awayFouls += 1;
                }
                if ($event->action_code === 'TIMEOUT') {
                    $awayTimeoutsUsed += 1;
                }
            }

            // Update cumulative running score on the event record
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
        $game->home_fouls_current_period = $homeFouls;
        $game->away_fouls_current_period = $awayFouls;

        $maxTimeouts = $game->total_timeouts_allowed;
        $game->home_timeouts_remaining = max(0, $maxTimeouts - $homeTimeoutsUsed);
        $game->away_timeouts_remaining = max(0, $maxTimeouts - $awayTimeoutsUsed);

        $game->save();
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
                'sequence' => $lastSequence + 1,
                'period' => $game->current_period,
                'clock_seconds_remaining' => $game->clock_seconds_remaining,
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
