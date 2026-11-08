<?php

namespace App\Services;

use App\Events\GameEventLogged;
use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Player;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventSourcedReplayService
{
    /**
     * Complete basketball actions dictionary.
     */
    public const BASKETBALL_ACTIONS = [
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
        'POSSESSION' => ['name' => 'Possession Arrow Toggle', 'type' => 'possession_arrow', 'points' => 0, 'category' => 'administrative'],
        'POSSESSION_SET' => ['name' => 'Possession Arrow Set', 'type' => 'possession_arrow', 'points' => 0, 'category' => 'administrative'],
        'CLOCK_SYNC' => ['name' => 'Clock Sync', 'type' => 'clock_sync', 'points' => 0, 'category' => 'administrative'],
        'CLOCK_START' => ['name' => 'Clock Started', 'type' => 'clock_state', 'points' => 0, 'category' => 'administrative'],
        'CLOCK_STOP' => ['name' => 'Clock Stopped', 'type' => 'clock_state', 'points' => 0, 'category' => 'administrative'],
        'ROSTER_PLAYER_ADDED' => ['name' => 'Player Added to Roster', 'type' => 'roster_management', 'points' => 0, 'category' => 'administrative'],
    ];

    /**
     * Record a new event atomically, recompute projections, and broadcast over WebSockets.
     */
    public function recordEvent(Game $game, array $data, ?User $actor = null): GameEvent
    {
        return DB::transaction(function () use ($game, $data, $actor) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            $clientUuid = $data['uuid'] ?? (string) Str::uuid();

            // Deduplication check: if client retried with same UUID, return existing event
            $existing = GameEvent::where('game_id', $game->id)->where('uuid', $clientUuid)->first();
            if ($existing) {
                return $existing;
            }

            $lastSequence = GameEvent::where('game_id', $game->id)->max('sequence') ?? 0;
            $actionCode = strtoupper(trim($data['action_code'] ?? ''));
            $actionDef = self::BASKETBALL_ACTIONS[$actionCode] ?? [
                'name' => $data['action_name'] ?? $actionCode,
                'type' => $data['action_type'] ?? 'custom',
                'points' => (int) ($data['points'] ?? 0),
            ];

            $teamSide = $data['team_side'] ?? 'home';
            $jersey = ! empty($data['jersey_number']) ? trim($data['jersey_number']) : null;
            $playerName = $data['player_name'] ?? null;
            $playerId = $data['player_id'] ?? null;

            if ($jersey && empty($playerId)) {
                $lineup = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('jersey_number', $jersey)
                    ->first();
                $playerId = $lineup?->player_id;
                $playerName = $playerName ?: ($lineup?->player_name ?? "Player #{$jersey}");
            }

            $assistJersey = ! empty($data['assist_jersey_number']) ? trim($data['assist_jersey_number']) : null;
            $assistPlayerId = $data['assist_player_id'] ?? null;
            if ($assistJersey && empty($assistPlayerId)) {
                $assistLineup = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $teamSide)
                    ->where('jersey_number', $assistJersey)
                    ->first();
                $assistPlayerId = $assistLineup?->player_id;
            }

            $period = isset($data['period']) ? (int) $data['period'] : (int) $game->current_period;
            $clockSeconds = isset($data['clock_seconds_remaining']) ? (int) $data['clock_seconds_remaining'] : (int) $game->clock_seconds_remaining;
            $wallClockTime = ! empty($data['wall_clock_time']) ? Carbon::parse($data['wall_clock_time']) : now();
            $points = isset($data['points']) ? (int) $data['points'] : (int) ($actionDef['points'] ?? 0);

            $description = $data['description'] ?? null;
            if (! $description) {
                $description = strtoupper($teamSide);
                if ($jersey) {
                    $description .= " #{$jersey} {$playerName}: ";
                } else {
                    $description .= ': ';
                }
                $description .= ($actionDef['name'] ?? $actionCode);
                if ($points > 0) {
                    $description .= " (+{$points} pts)";
                }
                if ($assistJersey) {
                    $description .= " (Ast: #{$assistJersey})";
                }
            }

            $event = GameEvent::create([
                'game_id' => $game->id,
                'uuid' => $clientUuid,
                'sequence' => $lastSequence + 1,
                'period' => $period,
                'clock_seconds_remaining' => $clockSeconds,
                'wall_clock_time' => $wallClockTime,
                'team_side' => $teamSide,
                'player_id' => $playerId,
                'jersey_number' => $jersey,
                'player_name' => $playerName ?? '',
                'assist_player_id' => $assistPlayerId,
                'assist_jersey_number' => $assistJersey,
                'sport' => $game->sport ?? 'basketball',
                'action_code' => $actionCode,
                'action_type' => $actionDef['type'] ?? 'stat',
                'action_name' => $actionDef['name'] ?? $actionCode,
                'raw_input' => $data['raw_input'] ?? null,
                'points' => $points,
                'home_score_after' => $game->home_score,
                'away_score_after' => $game->away_score,
                'is_undone' => false,
                'description' => $description,
                'metadata' => $data['metadata'] ?? null,
                'actor_id' => $actor?->id,
            ]);

            // Deterministic Replay Rebuild
            $gameState = $this->rebuildGameFromEvents($game);

            // Broadcast real-time update
            $this->broadcastUpdate($game, 'append', $gameState, $event->toArray(), $clientUuid);

            return $event->fresh();
        });
    }

    /**
     * Edit a past event, recalculate all subsequent projections, and broadcast update.
     */
    public function updateEvent(Game $game, int $eventId, array $updates, ?User $actor = null): GameEvent
    {
        return DB::transaction(function () use ($game, $eventId, $updates) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();
            $event = GameEvent::where('game_id', $game->id)->where('id', $eventId)->firstOrFail();

            if (isset($updates['action_code'])) {
                $code = strtoupper(trim($updates['action_code']));
                $event->action_code = $code;
                if (isset(self::BASKETBALL_ACTIONS[$code])) {
                    $event->action_name = self::BASKETBALL_ACTIONS[$code]['name'];
                    $event->action_type = self::BASKETBALL_ACTIONS[$code]['type'];
                    $event->points = self::BASKETBALL_ACTIONS[$code]['points'];
                }
            }

            if (isset($updates['points'])) {
                $event->points = (int) $updates['points'];
            }
            if (isset($updates['team_side'])) {
                $event->team_side = $updates['team_side'];
            }
            if (isset($updates['jersey_number'])) {
                $event->jersey_number = $updates['jersey_number'];
                $lineup = GameLineup::where('game_id', $game->id)
                    ->where('team_side', $event->team_side)
                    ->where('jersey_number', $event->jersey_number)
                    ->first();
                $event->player_id = $lineup?->player_id;
                $event->player_name = $lineup?->player_name ?? "Player #{$event->jersey_number}";
            }
            if (isset($updates['period'])) {
                $event->period = (int) $updates['period'];
            }
            if (isset($updates['clock_seconds_remaining'])) {
                $event->clock_seconds_remaining = (int) $updates['clock_seconds_remaining'];
            }
            if (isset($updates['description'])) {
                $event->description = $updates['description'];
            }
            if (isset($updates['is_undone'])) {
                $event->is_undone = (bool) $updates['is_undone'];
            }

            $event->save();

            // Full deterministic rebuild
            $gameState = $this->rebuildGameFromEvents($game);

            $this->broadcastUpdate($game, 'update', $gameState, $event->toArray(), $event->uuid);

            return $event->fresh();
        });
    }

    /**
     * Mark an event undone or soft-delete it, then trigger full rebuild.
     */
    public function deleteOrUndoEvent(Game $game, int $eventId, ?User $actor = null): ?GameEvent
    {
        return DB::transaction(function () use ($game, $eventId) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();
            $event = GameEvent::where('game_id', $game->id)->where('id', $eventId)->first();
            if (! $event) {
                return null;
            }

            $event->is_undone = true;
            $event->save();

            $gameState = $this->rebuildGameFromEvents($game);

            $this->broadcastUpdate($game, 'delete', $gameState, $event->toArray(), $event->uuid);

            return $event;
        });
    }

    /**
     * Insert a missed event at a specific point in the sequence or time.
     */
    public function insertEventAt(Game $game, int $targetSequence, array $data, ?User $actor = null): GameEvent
    {
        return DB::transaction(function () use ($game, $targetSequence, $data, $actor) {
            $game = Game::where('id', $game->id)->lockForUpdate()->first();

            // Shift all events at or above targetSequence up by 1
            GameEvent::where('game_id', $game->id)
                ->where('sequence', '>=', $targetSequence)
                ->increment('sequence');

            $data['sequence'] = $targetSequence;
            $clientUuid = $data['uuid'] ?? (string) Str::uuid();

            $event = GameEvent::create(array_merge($data, [
                'game_id' => $game->id,
                'uuid' => $clientUuid,
                'sequence' => $targetSequence,
                'wall_clock_time' => ! empty($data['wall_clock_time']) ? Carbon::parse($data['wall_clock_time']) : now(),
                'is_undone' => false,
                'actor_id' => $actor?->id,
            ]));

            $gameState = $this->rebuildGameFromEvents($game);

            $this->broadcastUpdate($game, 'rebuild', $gameState, $event->toArray(), $clientUuid);

            return $event->fresh();
        });
    }

    /**
     * Deterministic Replay Engine:
     * Calculates score, period scores, player stats, team fouls, timeouts, possession arrow,
     * and on-court lineup completely from chronological active events.
     */
    public function rebuildGameFromEvents(Game $game): array
    {
        return DB::transaction(function () use ($game) {
            $events = GameEvent::where('game_id', $game->id)
                ->where('is_undone', false)
                ->orderBy('sequence', 'asc')
                ->get();

            $homeScore = 0;
            $awayScore = 0;
            $homePeriodScores = ($game->period_format === 'halves') ? [0, 0] : [0, 0, 0, 0];
            $awayPeriodScores = ($game->period_format === 'halves') ? [0, 0] : [0, 0, 0, 0];

            $periodFouls = [
                'home' => [],
                'away' => [],
            ];

            // Configurable timeout counts using game rules and overtime allowance
            $timeoutsTotal = (int) $game->total_timeouts_allowed;
            $homeTimeoutsRemaining = $timeoutsTotal;
            $awayTimeoutsRemaining = $timeoutsTotal;

            // Possession arrow tracking: begins with immutable initial game setting
            $currentPossession = $game->settings['initial_possession'] ?? 'home';

            // Reset basketball stats for players
            if ($game->sport === 'basketball') {
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
                    'fouls_flagrant' => 0,
                    'fouls_forced' => 0,
                    'total_fouls' => 0,
                ]);
            }

            // On-court lineup tracking: starts from starter flag in GameLineup
            $activeOnCourt = [
                'home' => GameLineup::where('game_id', $game->id)->where('team_side', 'home')->where('is_starter', true)->pluck('jersey_number')->toArray(),
                'away' => GameLineup::where('game_id', $game->id)->where('team_side', 'away')->where('is_starter', true)->pluck('jersey_number')->toArray(),
            ];

            foreach ($events as $event) {
                $period = (int) $event->period;
                $points = (int) $event->points;

                while (count($homePeriodScores) < $period) {
                    $homePeriodScores[] = 0;
                }
                while (count($awayPeriodScores) < $period) {
                    $awayPeriodScores[] = 0;
                }
                if (! isset($periodFouls['home'][$period])) {
                    $periodFouls['home'][$period] = 0;
                }
                if (! isset($periodFouls['away'][$period])) {
                    $periodFouls['away'][$period] = 0;
                }

                $side = $event->team_side;

                // 1. Scoring
                if ($side === 'home') {
                    $homeScore += $points;
                    $homePeriodScores[$period - 1] += $points;
                } elseif ($side === 'away') {
                    $awayScore += $points;
                    $awayPeriodScores[$period - 1] += $points;
                }

                // 2. Fouls (personal, offensive, flagrant count toward team fouls in period)
                if (in_array($event->action_code, ['F', 'R', 'G', 'FL', 'FLAG'])) {
                    if ($side === 'home') {
                        $periodFouls['home'][$period] += 1;
                    } elseif ($side === 'away') {
                        $periodFouls['away'][$period] += 1;
                    }
                }

                // 3. Timeouts
                if ($event->action_code === 'TIMEOUT') {
                    if ($side === 'home' && $homeTimeoutsRemaining > 0) {
                        $homeTimeoutsRemaining -= 1;
                    } elseif ($side === 'away' && $awayTimeoutsRemaining > 0) {
                        $awayTimeoutsRemaining -= 1;
                    }
                }

                // 4. Possession Arrow Events
                if ($event->action_code === 'POSSESSION') {
                    // Toggle arrow
                    $currentPossession = ($currentPossession === 'home') ? 'away' : 'home';
                } elseif ($event->action_code === 'POSSESSION_SET') {
                    $targetTeam = $event->metadata['target_team'] ?? $side;
                    if (in_array($targetTeam, ['home', 'away'])) {
                        $currentPossession = $targetTeam;
                    }
                }

                // 5. Substitution Events
                if ($event->action_code === 'SUB') {
                    $subOut = $event->metadata['sub_out_jersey'] ?? null;
                    $subIn = $event->metadata['sub_in_jersey'] ?? null;
                    if ($subOut && $subIn && isset($activeOnCourt[$side])) {
                        $activeOnCourt[$side] = array_values(array_diff($activeOnCourt[$side], [$subOut]));
                        if (! in_array($subIn, $activeOnCourt[$side])) {
                            $activeOnCourt[$side][] = $subIn;
                        }
                    }
                }

                // 6. Apply individual player stats
                if (! empty($event->jersey_number) && isset(self::BASKETBALL_ACTIONS[$event->action_code])) {
                    $this->applyStatToPlayer($game, $side, $event->jersey_number, $event->action_code, $points);
                }
                if (! empty($event->assist_jersey_number) && $points > 0) {
                    $this->applyStatToPlayer($game, $side, $event->assist_jersey_number, 'A', 0);
                }

                // Write materialized point-in-time state onto the event record itself
                $event->home_score_after = $homeScore;
                $event->away_score_after = $awayScore;
                $event->possession_arrow_after = $currentPossession;
                $event->home_fouls_after = $periodFouls['home'][$period] ?? 0;
                $event->away_fouls_after = $periodFouls['away'][$period] ?? 0;
                $event->home_timeouts_after = $homeTimeoutsRemaining;
                $event->away_timeouts_after = $awayTimeoutsRemaining;
                $event->saveQuietly();
            }

            // Sync GameLineup on-court status
            foreach (['home', 'away'] as $teamSide) {
                if (! empty($activeOnCourt[$teamSide])) {
                    GameLineup::where('game_id', $game->id)
                        ->where('team_side', $teamSide)
                        ->update(['is_on_court' => false]);

                    GameLineup::where('game_id', $game->id)
                        ->where('team_side', $teamSide)
                        ->whereIn('jersey_number', $activeOnCourt[$teamSide])
                        ->update(['is_on_court' => true]);
                }
            }

            // Update Game master model
            $currentPeriod = (int) $game->current_period;
            $game->home_score = $homeScore;
            $game->away_score = $awayScore;
            $game->home_period_scores = $homePeriodScores;
            $game->away_period_scores = $awayPeriodScores;
            $game->home_fouls_current_period = $periodFouls['home'][$currentPeriod] ?? 0;
            $game->away_fouls_current_period = $periodFouls['away'][$currentPeriod] ?? 0;
            $game->home_timeouts_remaining = $homeTimeoutsRemaining;
            $game->away_timeouts_remaining = $awayTimeoutsRemaining;
            $game->possession_arrow = $currentPossession;
            $game->save();

            return [
                'gameId' => $game->id,
                'homeScore' => $homeScore,
                'awayScore' => $awayScore,
                'homePeriodScores' => $homePeriodScores,
                'awayPeriodScores' => $awayPeriodScores,
                'currentPeriod' => $currentPeriod,
                'homeFouls' => $game->home_fouls_current_period,
                'awayFouls' => $game->away_fouls_current_period,
                'homeTimeouts' => $homeTimeoutsRemaining,
                'awayTimeouts' => $awayTimeoutsRemaining,
                'possessionArrow' => $currentPossession,
                'clockSeconds' => $game->clock_seconds_remaining,
                'clockRunning' => (bool) $game->clock_running,
                'activeOnCourt' => $activeOnCourt,
            ];
        });
    }

    /**
     * Apply individual player stat increments.
     */
    protected function applyStatToPlayer(Game $game, string $teamSide, string $jersey, string $actionCode, int $points): void
    {
        $actionDef = self::BASKETBALL_ACTIONS[$actionCode] ?? null;
        if (! $actionDef) {
            return;
        }

        $lineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', $teamSide)
            ->where('jersey_number', $jersey)
            ->first();

        $stat = BasketballStat::firstOrCreate(
            [
                'game_id' => $game->id,
                'team_side' => $teamSide,
                'jersey_number' => $jersey,
            ],
            [
                'player_id' => $lineup?->player_id,
                'player_name' => $lineup?->player_name ?? "Player #{$jersey}",
            ]
        );

        if ($points > 0) {
            $stat->points += $points;
        }

        if (isset($actionDef['stat'])) {
            $col = $actionDef['stat'];
            $stat->{$col} += 1;
        }

        if (isset($actionDef['attempts'])) {
            $attCol = $actionDef['attempts'];
            $stat->{$attCol} += 1;

            if ($attCol === 'fg3a') {
                $stat->fga += 1;
                if (! empty($actionDef['stat']) && $actionDef['stat'] === 'fg3m') {
                    $stat->fgm += 1;
                }
            }
        }

        if (in_array($actionCode, ['D', 'O'])) {
            $stat->reb = $stat->oreb + $stat->dreb;
        }

        if (in_array($actionCode, ['P', 'U', 'I'])) {
            $stat->turnovers = $stat->to_pass + $stat->to_fumble + $stat->to_violation;
        }

        if (in_array($actionCode, ['F', 'R', 'T', 'G', 'FL', 'FLAG'])) {
            $stat->total_fouls = $stat->fouls_pers + $stat->fouls_off + $stat->fouls_tech + $stat->fouls_flagrant;
        }

        $stat->save();
    }

    /**
     * Parse scoreboard clock input strings like 't2000' (20:00), 't0800' (8:00), 't0530' (5:30), 't0005' (5s), 't8:00'.
     */
    public function parseClockInput(string $input): ?int
    {
        $input = strtolower(trim($input));

        // Format 1: tMMSS (e.g. t2000, t0800, t0530, t0005)
        if (preg_match('/^t([0-9]{2})([0-9]{2})$/', $input, $m)) {
            $minutes = (int) $m[1];
            $seconds = (int) $m[2];

            return ($minutes * 60) + $seconds;
        }

        // Format 2: tM:SS or tMM:SS (e.g. t20:00, t8:00, t0:45)
        if (preg_match('/^t([0-9]{1,2}):([0-9]{2})$/', $input, $m)) {
            $minutes = (int) $m[1];
            $seconds = (int) $m[2];

            return ($minutes * 60) + $seconds;
        }

        // Format 3: tSS (e.g. t45 -> 45 seconds, t5 -> 5 seconds)
        if (preg_match('/^t([0-9]{1,2})$/', $input, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Synchronize game clock with physical scoreboard.
     */
    public function syncClock(Game $game, int $clockSeconds, ?User $actor = null): GameEvent
    {
        $game->clock_seconds_remaining = max(0, $clockSeconds);
        $game->save();

        $min = floor($clockSeconds / 60);
        $sec = str_pad((string) ($clockSeconds % 60), 2, '0', STR_PAD_LEFT);

        return $this->recordEvent($game, [
            'action_code' => 'CLOCK_SYNC',
            'action_type' => 'clock_sync',
            'action_name' => 'Clock Synced',
            'clock_seconds_remaining' => $clockSeconds,
            'description' => "Clock synced to {$min}:{$sec}",
            'metadata' => ['clock_seconds' => $clockSeconds],
        ], $actor);
    }

    /**
     * Start or stop game clock.
     */
    public function toggleClock(Game $game, bool $running, ?int $clockSeconds = null, ?User $actor = null): GameEvent
    {
        $game->clock_running = $running;
        if ($clockSeconds !== null) {
            $game->clock_seconds_remaining = max(0, $clockSeconds);
        }
        $game->clock_last_started_at = $running ? now() : null;
        $game->save();

        $actionCode = $running ? 'CLOCK_START' : 'CLOCK_STOP';
        $desc = $running ? 'Clock started' : 'Clock stopped';

        return $this->recordEvent($game, [
            'action_code' => $actionCode,
            'action_type' => 'clock_state',
            'action_name' => $desc,
            'clock_seconds_remaining' => $game->clock_seconds_remaining,
            'description' => $desc,
            'metadata' => ['clock_running' => $running],
        ], $actor);
    }

    /**
     * Toggle possession arrow deterministically.
     */
    public function togglePossession(Game $game, ?string $reason = null, ?User $actor = null): GameEvent
    {
        $newPossession = ($game->possession_arrow === 'home') ? 'away' : 'home';

        return $this->recordEvent($game, [
            'action_code' => 'POSSESSION',
            'action_type' => 'possession_arrow',
            'action_name' => 'Possession Arrow Toggle',
            'team_side' => $newPossession,
            'description' => 'Possession arrow toggled to '.strtoupper($newPossession).($reason ? " ({$reason})" : ''),
            'metadata' => ['target_team' => $newPossession, 'reason' => $reason],
        ], $actor);
    }

    /**
     * Broadcast an update over Laravel Reverb WebSockets.
     */
    protected function broadcastUpdate(Game $game, string $mutationType, array $gameState, ?array $eventData = null, ?string $clientUuid = null): void
    {
        try {
            broadcast(new GameEventLogged(
                $game->id,
                $mutationType,
                $gameState,
                $eventData,
                $clientUuid
            ))->toOthers();
        } catch (\Throwable $e) {
            // Gracefully handle environments without active Reverb worker (fallback to local/DB)
        }
    }
}
