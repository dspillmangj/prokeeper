<?php

use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\BasketballStatService;
use App\Services\EventSourcedReplayService;
use Carbon\Carbon;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->org = Organization::create(['name' => 'Event Sourced Org', 'slug' => 'event-org']);
    $this->user = User::create([
        'organization_id' => $this->org->id,
        'name' => 'Operator A',
        'email' => 'operatorA@example.com',
        'password' => bcrypt('secret'),
        'role' => 'admin',
    ]);

    $this->homeTeam = Team::create([
        'organization_id' => $this->org->id,
        'name' => 'Home Wildcats',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $this->awayTeam = Team::create([
        'organization_id' => $this->org->id,
        'name' => 'Away Bulldogs',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $this->game = Game::create([
        'access_code' => 'EVTS01',
        'slug' => 'event-sourced-game',
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_id' => $this->homeTeam->id,
        'home_team_name' => 'Home Wildcats',
        'away_team_id' => $this->awayTeam->id,
        'away_team_name' => 'Away Bulldogs',
        'current_period' => 1,
        'clock_seconds_remaining' => 480,
        'possession_arrow' => 'home',
    ]);

    // Starters for Home
    foreach (['3', '11', '23', '33', '45'] as $num) {
        GameLineup::create([
            'game_id' => $this->game->id,
            'team_side' => 'home',
            'jersey_number' => $num,
            'player_name' => "Home Player #{$num}",
            'is_on_court' => true,
            'is_starter' => true,
        ]);
    }
    // Bench for Home
    GameLineup::create([
        'game_id' => $this->game->id,
        'team_side' => 'home',
        'jersey_number' => '12',
        'player_name' => 'Home Sub #12',
        'is_on_court' => false,
        'is_starter' => false,
    ]);

    // Starters for Away
    foreach (['1', '5', '10', '20', '30'] as $num) {
        GameLineup::create([
            'game_id' => $this->game->id,
            'team_side' => 'away',
            'jersey_number' => $num,
            'player_name' => "Away Player #{$num}",
            'is_on_court' => true,
            'is_starter' => true,
        ]);
    }

    $this->replayService = app(EventSourcedReplayService::class);
    $this->statService = app(BasketballStatService::class);
});

test('every action writes wall clock world time and point-in-time materialized snapshots', function () {
    $now = Carbon::parse('2026-11-07 19:30:00');
    Carbon::setTestNow($now);

    // 1. Home 2pt make
    $res1 = $this->statService->recordStat($this->game, 'home', '23', 'X', '23-X', 460);
    $event1 = GameEvent::find($res1['event']->id);

    expect($event1->wall_clock_time)->not->toBeNull();
    expect($event1->wall_clock_time->toIso8601String())->toBe($now->toIso8601String());
    expect($event1->home_score_after)->toBe(2);
    expect($event1->away_score_after)->toBe(0);
    expect($event1->possession_arrow_after)->toBe('home');
    expect($event1->home_fouls_after)->toBe(0);

    // 2. Tie-up / Held ball event swaps possession arrow
    $posEvent = $this->replayService->togglePossession($this->game, 'Tie-up at midcourt');
    expect($posEvent->action_code)->toBe('POSSESSION');
    expect($posEvent->possession_arrow_after)->toBe('away');
    expect($this->game->fresh()->possession_arrow)->toBe('away');

    // 3. Away Personal Foul
    $res2 = $this->statService->recordStat($this->game, 'away', '10', 'F', '10=F', 440);
    $event2 = GameEvent::find($res2['event']->id);
    expect($event2->away_fouls_after)->toBe(1);
    expect($event2->possession_arrow_after)->toBe('away');

    Carbon::setTestNow();
});

test('editing an earlier event dynamically recomputes all downstream totals and timeline snapshots', function () {
    // Event 1: Home 2pt make (seq 1) -> Score 2 - 0
    $res1 = $this->statService->recordStat($this->game, 'home', '23', 'X', '23-X', 470);
    // Event 2: Away 3pt make (seq 2) -> Score 2 - 3
    $res2 = $this->statService->recordStat($this->game, 'away', '5', 'M', '5=M', 450);
    // Event 3: Home 2pt make (seq 3) -> Score 4 - 3
    $res3 = $this->statService->recordStat($this->game, 'home', '33', 'X', '33-X', 430);

    $e3 = GameEvent::find($res3['event']->id);
    expect($e3->home_score_after)->toBe(4);
    expect($e3->away_score_after)->toBe(3);

    // Now operator realizes Event 1 was actually a 3-pointer!
    $this->replayService->updateEvent($this->game, $res1['event']->id, [
        'action_code' => 'M',
        'points' => 3,
    ]);

    $this->game->refresh();
    expect($this->game->home_score)->toBe(5); // 3 + 2 = 5
    expect($this->game->away_score)->toBe(3);

    // Downstream event 3's materialized snapshot must be recalculated!
    $e3->refresh();
    expect($e3->home_score_after)->toBe(5);
    expect($e3->away_score_after)->toBe(3);
});

test('fast clock sync hotkeys parse various scoreboard formats and record clock events', function () {
    // 1. t2000 -> 20 minutes (1200 seconds)
    $res1 = $this->statService->processKeyboardInput($this->game, 't2000');
    expect($res1['success'])->toBeTrue();
    expect($this->game->fresh()->clock_seconds_remaining)->toBe(1200);

    // 2. t0800 -> 8 minutes (480 seconds)
    $res2 = $this->statService->processKeyboardInput($this->game, 't0800');
    expect($res2['success'])->toBeTrue();
    expect($this->game->fresh()->clock_seconds_remaining)->toBe(480);

    // 3. t0530 -> 5 minutes 30 seconds (330 seconds)
    $res3 = $this->statService->processKeyboardInput($this->game, 't0530');
    expect($res3['success'])->toBeTrue();
    expect($this->game->fresh()->clock_seconds_remaining)->toBe(330);

    // 4. t0005 -> 5 seconds
    $res4 = $this->statService->processKeyboardInput($this->game, 't0005');
    expect($res4['success'])->toBeTrue();
    expect($this->game->fresh()->clock_seconds_remaining)->toBe(5);

    // 5. t8:00 colon syntax
    $res5 = $this->statService->processKeyboardInput($this->game, 't8:00');
    expect($res5['success'])->toBeTrue();
    expect($this->game->fresh()->clock_seconds_remaining)->toBe(480);

    // Verify clock events are logged in the event stream
    $clockEvents = GameEvent::where('game_id', $this->game->id)->where('action_code', 'CLOCK_SYNC')->get();
    expect($clockEvents->count())->toBe(5);
});

test('clock start and stop creates events and sets running state', function () {
    $startEvent = $this->replayService->toggleClock($this->game, true, 480);
    expect($startEvent->action_code)->toBe('CLOCK_START');
    expect($this->game->fresh()->clock_running)->toBeTrue();

    $stopEvent = $this->replayService->toggleClock($this->game, false, 465);
    expect($stopEvent->action_code)->toBe('CLOCK_STOP');
    expect($this->game->fresh()->clock_running)->toBeFalse();
    expect($this->game->fresh()->clock_seconds_remaining)->toBe(465);
});

test('optimistic client uuid deduplication prevents duplicate event insertion', function () {
    $uuid = (string) Str::uuid();

    $event1 = $this->replayService->recordEvent($this->game, [
        'uuid' => $uuid,
        'action_code' => 'X',
        'points' => 2,
        'team_side' => 'home',
        'jersey_number' => '23',
    ]);

    // Client retries with same UUID (e.g. temporary network blip)
    $event2 = $this->replayService->recordEvent($this->game, [
        'uuid' => $uuid,
        'action_code' => 'X',
        'points' => 2,
        'team_side' => 'home',
        'jersey_number' => '23',
    ]);

    expect($event1->id)->toBe($event2->id);
    expect(GameEvent::where('game_id', $this->game->id)->count())->toBe(1);
    expect($this->game->fresh()->home_score)->toBe(2);
});

test('substitutions are event-sourced and replayed to determine active court 5', function () {
    // Initially #23 is on court and #12 is on bench
    $this->replayService->recordEvent($this->game, [
        'action_code' => 'SUB',
        'action_type' => 'substitution',
        'team_side' => 'home',
        'jersey_number' => '12',
        'metadata' => [
            'sub_out_jersey' => '23',
            'sub_in_jersey' => '12',
        ],
    ]);

    $courtLineups = GameLineup::where('game_id', $this->game->id)
        ->where('team_side', 'home')
        ->where('is_on_court', true)
        ->pluck('jersey_number')
        ->toArray();

    expect($courtLineups)->toContain('12');
    expect($courtLineups)->not->toContain('23');
});
