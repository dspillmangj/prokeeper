<?php

use App\Livewire\BasketballOperator;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\BasketballStatService;
use Livewire\Livewire;

beforeEach(function () {
    $this->org = Organization::create([
        'name' => 'LCA Athletics',
        'slug' => 'lca-athletics',
    ]);

    $this->user = User::create([
        'name' => 'Coach Spillman',
        'email' => 'spillman@lca.org',
        'password' => bcrypt('secret123'),
        'organization_id' => $this->org->id,
        'role' => 'admin',
    ]);

    $this->homeTeam = Team::create([
        'organization_id' => $this->org->id,
        'name' => 'LCA Eagles',
        'sport' => 'basketball',
    ]);

    $this->awayTeam = Team::create([
        'organization_id' => $this->org->id,
        'name' => 'Heritage Warriors',
        'sport' => 'basketball',
    ]);
});

test('game can be created with custom full and 30s timeout configuration and rules', function () {
    $response = $this->actingAs($this->user)->post(route('games.store'), [
        'sport' => 'basketball',
        'home_team_id' => $this->homeTeam->id,
        'away_team_id' => $this->awayTeam->id,
        'venue' => 'Main Fieldhouse',
        'event_name' => 'Eagle Classic Tournament',
        'preset' => 'standard_halves',
        'timeouts_full' => 4,
        'timeouts_30s' => 2,
        'timeouts_ot' => 1,
        'period_format' => 'halves',
        'period_minutes' => 20,
        'ot_minutes' => 5,
        'shot_clock_seconds' => 30,
        'bonus_foul_threshold' => 7,
        'double_bonus_foul_threshold' => 10,
        'player_foul_limit' => 5,
    ]);

    $game = Game::where('organization_id', $this->org->id)->latest()->first();
    expect($game)->not->toBeNull();
    expect($game->home_timeouts_remaining)->toBe(6); // 4 full + 2 30s
    expect($game->away_timeouts_remaining)->toBe(6);
    expect($game->full_timeouts_allowed)->toBe(4);
    expect($game->thirty_second_timeouts_allowed)->toBe(2);
    expect($game->total_timeouts_allowed)->toBe(6);
    expect($game->period_format)->toBe('halves');
    expect($game->period_minutes)->toBe(20);
    expect($game->clock_seconds_remaining)->toBe(1200); // 20 min in seconds
    expect($game->bonus_threshold)->toBe(7);
    expect($game->double_bonus_threshold)->toBe(10);
    expect($game->period_name)->toBe('1st Half');

    $response->assertRedirect(route('games.operator', $game->uuid));
});

test('tracks full vs 30s timeouts distinctly in stat service and game events', function () {
    $game = Game::create([
        'access_code' => 'TO001',
        'slug' => 'eagles-vs-warriors-to001',
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'sport' => 'basketball',
        'home_team_name' => 'LCA Eagles',
        'away_team_name' => 'Warriors',
        'home_timeouts_remaining' => 5,
        'away_timeouts_remaining' => 5,
        'clock_seconds_remaining' => 480,
        'settings' => [
            'rules' => [
                'timeouts_full' => 3,
                'timeouts_30s' => 2,
            ],
        ],
    ]);

    $service = new BasketballStatService;

    // Call 1 Full TO for Home
    $ev1 = $service->callTimeout($game, 'home', 'full');
    expect($ev1->metadata['timeout_type'])->toBe('full');
    expect($ev1->metadata['timeouts_remaining'])->toBe(4);
    expect($ev1->description)->toContain('Full (60s)');

    // Call 1 30s TO for Home
    $ev2 = $service->callTimeout($game, 'home', '30s');
    expect($ev2->metadata['timeout_type'])->toBe('30s');
    expect($ev2->metadata['timeouts_remaining'])->toBe(3);
    expect($ev2->description)->toContain('30-Second');

    // Call 1 30s TO for Away
    $ev3 = $service->callTimeout($game, 'away', '30s');
    expect($ev3->metadata['timeout_type'])->toBe('30s');
    expect($ev3->metadata['timeouts_remaining'])->toBe(4);

    $game->refresh();
    $homeBreakdown = $game->calculateTimeoutsBreakdown('home');
    expect($homeBreakdown['allowed_full'])->toBe(3);
    expect($homeBreakdown['allowed_30s'])->toBe(2);
    expect($homeBreakdown['used_full'])->toBe(1);
    expect($homeBreakdown['used_30s'])->toBe(1);
    expect($homeBreakdown['rem_full'])->toBe(2);
    expect($homeBreakdown['rem_30s'])->toBe(1);
    expect($homeBreakdown['rem_total'])->toBe(3);

    $awayBreakdown = $game->calculateTimeoutsBreakdown('away');
    expect($awayBreakdown['used_full'])->toBe(0);
    expect($awayBreakdown['used_30s'])->toBe(1);
    expect($awayBreakdown['rem_full'])->toBe(3);
    expect($awayBreakdown['rem_30s'])->toBe(1);
    expect($awayBreakdown['rem_total'])->toBe(4);
});

test('rebuilds game from events respecting configured timeout total', function () {
    $game = Game::create([
        'access_code' => 'TO002',
        'slug' => 'eagles-vs-warriors-to002',
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'sport' => 'basketball',
        'home_team_name' => 'LCA Eagles',
        'away_team_name' => 'Warriors',
        'home_timeouts_remaining' => 5,
        'away_timeouts_remaining' => 5,
        'clock_seconds_remaining' => 480,
        'settings' => [
            'rules' => [
                'timeouts_full' => 4,
                'timeouts_30s' => 2,
            ],
        ],
    ]);

    $service = new BasketballStatService;
    $service->callTimeout($game, 'home', 'full');
    $service->callTimeout($game, 'home', '30s');

    // Total allowed is 6, used 2 -> 4 remaining
    $service->rebuildGameFromEvents($game);
    $game->refresh();
    expect($game->home_timeouts_remaining)->toBe(4);
    expect($game->away_timeouts_remaining)->toBe(6);
});

test('evaluates bonus rules dynamically for quarters vs halves', function () {
    // Quarters standard: bonus at 5 fouls
    $qtrGame = Game::create([
        'access_code' => 'BNS001',
        'slug' => 'qtr-game',
        'organization_id' => $this->org->id,
        'sport' => 'basketball',
        'home_fouls_current_period' => 4,
        'settings' => [
            'rules' => [
                'period_format' => 'quarters',
                'bonus_foul_threshold' => 5,
                'double_bonus_foul_threshold' => 5,
            ],
        ],
    ]);

    expect($qtrGame->isBonus('home'))->toBeFalse();
    $qtrGame->home_fouls_current_period = 5;
    expect($qtrGame->isBonus('home'))->toBeTrue();
    expect($qtrGame->isDoubleBonus('home'))->toBeTrue();

    // Halves standard: 7 for 1-and-1 bonus, 10 for double bonus
    $halfGame = Game::create([
        'access_code' => 'BNS002',
        'slug' => 'half-game',
        'organization_id' => $this->org->id,
        'sport' => 'basketball',
        'home_fouls_current_period' => 6,
        'settings' => [
            'rules' => [
                'period_format' => 'halves',
                'bonus_foul_threshold' => 7,
                'double_bonus_foul_threshold' => 10,
            ],
        ],
    ]);

    expect($halfGame->isBonus('home'))->toBeFalse();
    $halfGame->home_fouls_current_period = 7;
    expect($halfGame->isBonus('home'))->toBeTrue();
    expect($halfGame->isDoubleBonus('home'))->toBeFalse();
    $halfGame->home_fouls_current_period = 10;
    expect($halfGame->isDoubleBonus('home'))->toBeTrue();
});

test('basketball operator livewire component updates timeouts and rules in modal', function () {
    $game = Game::create([
        'access_code' => 'OPR001',
        'slug' => 'operator-test',
        'organization_id' => $this->org->id,
        'sport' => 'basketball',
        'home_team_name' => 'LCA Eagles',
        'away_team_name' => 'Heritage',
        'home_timeouts_remaining' => 5,
        'away_timeouts_remaining' => 5,
        'clock_seconds_remaining' => 480,
    ]);

    Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('openGameDetailsModal')
        ->assertSet('timeoutsFull', 3)
        ->assertSet('timeouts30s', 2)
        ->set('timeoutsFull', 4)
        ->set('timeouts30s', 1)
        ->set('periodFormat', 'halves')
        ->set('gamePeriodMinutes', 20)
        ->set('bonusFoulThreshold', 7)
        ->set('doubleBonusFoulThreshold', 10)
        ->set('homeTimeoutsRemaining', 5)
        ->set('awayTimeoutsRemaining', 5)
        ->call('saveGameDetails')
        ->assertHasNoErrors();

    $game->refresh();
    expect($game->full_timeouts_allowed)->toBe(4);
    expect($game->thirty_second_timeouts_allowed)->toBe(1);
    expect($game->period_format)->toBe('halves');
    expect($game->period_minutes)->toBe(20);
    expect($game->bonus_threshold)->toBe(7);
    expect($game->double_bonus_threshold)->toBe(10);
});

test('operator livewire can call 30s timeout specifically', function () {
    $game = Game::create([
        'access_code' => 'OPR002',
        'slug' => 'operator-to-test',
        'organization_id' => $this->org->id,
        'sport' => 'basketball',
        'home_team_name' => 'LCA Eagles',
        'away_team_name' => 'Heritage',
        'home_timeouts_remaining' => 5,
        'away_timeouts_remaining' => 5,
        'clock_seconds_remaining' => 480,
    ]);

    Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('callTimeout', 'home', '30s')
        ->assertHasNoErrors();

    $game->refresh();
    expect($game->home_timeouts_remaining)->toBe(4);

    $lastEvent = GameEvent::where('game_id', $game->id)->latest()->first();
    expect($lastEvent->action_code)->toBe('TIMEOUT');
    expect($lastEvent->metadata['timeout_type'])->toBe('30s');
});
