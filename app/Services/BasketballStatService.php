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
        'X' => ['name' => '2pt MAKE', 'type' => '2pt_make', 'points' => 2, 'stat' => 'fgm', 'attempts' => 'fga'],
        'Z' => ['name' => '2pt Miss', 'type' => '2pt_miss', 'points' => 0, 'attempts' => 'fga'],
        'M' => ['name' => '3pt MAKE', 'type' => '3pt_make', 'points' => 3, 'stat' => 'fg3m', 'attempts' => 'fg3a'],
        'N' => ['name' => '3pt Miss', 'type' => '3pt_miss', 'points' => 0, 'attempts' => 'fg3a'],
        'B' => ['name' => 'FT MAKE', 'type' => 'ft_make', 'points' => 1, 'stat' => 'ftm', 'attempts' => 'fta'],
        'V' => ['name' => 'FT Miss', 'type' => 'ft_miss', 'points' => 0, 'attempts' => 'fta'],
        'D' => ['name' => 'Def Reb', 'type' => 'def_reb', 'points' => 0, 'stat' => 'dreb'],
        'O' => ['name' => 'Off Reb', 'type' => 'off_reb', 'points' => 0, 'stat' => 'oreb'],
        'A' => ['name' => 'Assist', 'type' => 'assist', 'points' => 0, 'stat' => 'ast'],
        'S' => ['name' => 'Steal', 'type' => 'steal', 'points' => 0, 'stat' => 'stl'],
        'K' => ['name' => 'Block', 'type' => 'block', 'points' => 0, 'stat' => 'blk'],
        'W' => ['name' => 'Swat', 'type' => 'swat', 'points' => 0, 'stat' => 'swat'],
        'P' => ['name' => 'Passing Turnover', 'type' => 'turnover_pass', 'points' => 0, 'stat' => 'to_pass'],
        'U' => ['name' => 'Fumble Turnover', 'type' => 'turnover_fumble', 'points' => 0, 'stat' => 'to_fumble'],
        'I' => ['name' => 'Violation Turnover', 'type' => 'turnover_violation', 'points' => 0, 'stat' => 'to_violation'],
        'F' => ['name' => 'Pers Foul', 'type' => 'foul_pers', 'points' => 0, 'stat' => 'fouls_pers'],
        'R' => ['name' => 'Off Foul', 'type' => 'foul_off', 'points' => 0, 'stat' => 'fouls_off'],
        'T' => ['name' => 'Tech Foul', 'type' => 'foul_tech', 'points' => 0, 'stat' => 'fouls_tech'],
        'H' => ['name' => 'Forc Foul', 'type' => 'foul_forced', 'points' => 0, 'stat' => 'fouls_forced'],
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
        $actionDef = self::ACTIONS[$actionCode];

        return DB::transaction(function () use ($game, $teamSide, $jersey, $actionCode, $actionDef, $rawInput, $clockSeconds) {
            // Refresh game lock
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            // Find or create player lineup entry
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
                'action_type' => $actionDef['type'],
                'action_name' => $actionDef['name'],
                'raw_input' => $rawInput ?? "{$jersey}".($teamSide === 'home' ? '-' : '=').$actionCode,
                'points' => $points,
                'home_score_after' => $homeScoreAfter,
                'away_score_after' => $awayScoreAfter,
                'is_undone' => false,
                'description' => strtoupper($teamSide)." #{$jersey} {$playerName}: ".$actionDef['name'],
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
                'message' => strtoupper($teamSide)." - {$playerName} (#{$jersey}): ".$actionDef['name'].($points > 0 ? " (+{$points} pts)" : ''),
                'event' => $event,
                'game' => $game,
            ];
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
            if ($attCol === 'fga' && ! empty($actionDef['stat']) && $actionDef['stat'] === 'fgm') {
                // fgm already handled by stat col
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
     * Completely recompute score and stats from chronological events.
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
            $points = $event->points;
            $period = $event->period;

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
            } else {
                $awayScore += $points;
                $awayPeriodScores[$period - 1] += $points;
                if (in_array($event->action_code, ['F', 'R', 'T']) && $period === $game->current_period) {
                    $awayFouls += 1;
                }
            }

            if (isset(self::ACTIONS[$event->action_code])) {
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

        $game->home_score = $homeScore;
        $game->away_score = $awayScore;
        $game->home_period_scores = $homePeriodScores;
        $game->away_period_scores = $awayPeriodScores;
        $game->home_fouls_current_period = $homeFouls;
        $game->away_fouls_current_period = $awayFouls;
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

            if (isset($data['jersey_number'])) {
                $event->jersey_number = $data['jersey_number'];
                $lineup = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $event->team_side)
                    ->where('jersey_number', $data['jersey_number'])
                    ->first();
                if ($lineup) {
                    $event->player_name = $lineup->player_name;
                    $event->player_id = $lineup->player_id;
                }
            }

            if (isset($data['action_code']) && isset(self::ACTIONS[$data['action_code']])) {
                $actionDef = self::ACTIONS[$data['action_code']];
                $event->action_code = $data['action_code'];
                $event->action_name = $actionDef['name'];
                $event->action_type = $actionDef['type'];
                $event->points = $actionDef['points'];
            }

            if (isset($data['period'])) {
                $event->period = (int) $data['period'];
            }

            $event->description = strtoupper($event->team_side)." #{$event->jersey_number} {$event->player_name}: {$event->action_name}";
            $event->save();

            $this->rebuildGameFromEvents($game);

            return $event;
        });
    }
}
