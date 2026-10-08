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
        'K' => ['name' => 'Kill', 'type' => 'kill', 'point_team' => 'same', 'stat' => 'kills', 'attempt' => true],
        'E' => ['name' => 'Attack Error', 'type' => 'attack_error', 'point_team' => 'opp', 'stat' => 'attack_errors', 'attempt' => true],
        'T' => ['name' => 'Attack Attempt', 'type' => 'attack_attempt', 'point_team' => null, 'attempt' => true],
        'A' => ['name' => 'Service Ace', 'type' => 'service_ace', 'point_team' => 'same', 'stat' => 'service_aces', 'serve_attempt' => true],
        'S' => ['name' => 'Service Error', 'type' => 'service_error', 'point_team' => 'opp', 'stat' => 'service_errors', 'serve_attempt' => true],
        'D' => ['name' => 'Dig', 'type' => 'dig', 'point_team' => null, 'stat' => 'digs'],
        'B' => ['name' => 'Block Solo', 'type' => 'block_solo', 'point_team' => 'same', 'stat' => 'block_solos'],
        'C' => ['name' => 'Block Assist', 'type' => 'block_assist', 'point_team' => 'same', 'stat' => 'block_assists'],
        'H' => ['name' => 'Ball Handling Error', 'type' => 'ball_handling_error', 'point_team' => 'opp', 'stat' => 'ball_handling_errors'],
        'R' => ['name' => 'Reception Error', 'type' => 'reception_error', 'point_team' => 'opp', 'stat' => 'reception_errors'],
        'Z' => ['name' => 'Set Assist', 'type' => 'set_assist', 'point_team' => null, 'stat' => 'assists'],
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
        $actionDef = self::ACTIONS[$actionCode];

        return DB::transaction(function () use ($game, $teamSide, $jersey, $actionCode, $actionDef, $rawInput) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $lineup = GameLineup::where('game_id', $game->id)
                ->where('team_side', $teamSide)
                ->where('jersey_number', $jersey)
                ->first();

            $playerName = $lineup?->player_name ?? "Player #{$jersey}";
            $playerId = $lineup?->player_id;

            $pointSide = null;
            if ($actionDef['point_team'] === 'same') {
                $pointSide = $teamSide;
            } elseif ($actionDef['point_team'] === 'opp') {
                $pointSide = ($teamSide === 'home') ? 'away' : 'home';
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
                'action_type' => $actionDef['type'],
                'action_name' => $actionDef['name'],
                'raw_input' => $rawInput ?? "{$jersey}".($teamSide === 'home' ? '-' : '=').$actionCode,
                'points' => ($pointSide !== null) ? 1 : 0,
                'home_score_after' => $homeScoreAfter,
                'away_score_after' => $awayScoreAfter,
                'is_undone' => false,
                'description' => strtoupper($teamSide)." #{$jersey} {$playerName}: ".$actionDef['name'].($pointSide ? ' (Point '.strtoupper($pointSide).')' : ''),
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

            // Update individual player volleyball stats
            $this->applyStatToPlayer($game->id, $teamSide, $jersey, $playerName, $playerId, $actionDef);

            return [
                'success' => true,
                'message' => strtoupper($teamSide)." - {$playerName} (#{$jersey}): {$actionDef['name']}".($pointSide ? ' [Point '.strtoupper($pointSide).']' : '').($sideOutOccurred ? ' (Side-out)' : ''),
                'event' => $event,
                'game' => $game,
                'set_won' => $setWon,
                'set_winner' => $setWinner,
            ];
        });
    }

    /**
     * Apply volleyball stat update to volleyball_stats table.
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

        if (isset($actionDef['stat'])) {
            $col = $actionDef['stat'];
            $stat->$col += 1;
        }

        if (! empty($actionDef['attempt'])) {
            $stat->attack_attempts += 1;
        }

        if (! empty($actionDef['serve_attempt'])) {
            $stat->service_attempts += 1;
        }

        // Recalculate totals
        $stat->total_blocks = $stat->block_solos + ($stat->block_assists * 0.5);
        $stat->total_points = $stat->kills + $stat->service_aces + $stat->block_solos + ($stat->block_assists * 0.5);
        $stat->calculateHittingPercentage();

        $stat->save();
    }

    /**
     * Advance to the next set.
     */
    public function advanceSet(Game $game): void
    {
        DB::transaction(function () use ($game) {
            $game->current_period += 1;
            $game->home_score = 0;
            $game->away_score = 0;
            $game->home_timeouts_remaining = 2;
            $game->away_timeouts_remaining = 2;
            $game->save();
        });
    }
}
