<?php

use App\Livewire\BasketballOperator;
use App\Livewire\PublicScoreboard;
use App\Livewire\PureScoreboard;
use App\Livewire\VolleyballOperator;
use App\Livewire\WatchGame;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Services\BasketballStatService;
use App\Services\VolleyballStatService;
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
        'email_verified_at' => now(),
        'plan' => 'pro',
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
    expect($ev1->metadata['full_timeouts_remaining'])->toBe(2);
    expect($ev1->metadata['thirty_second_timeouts_remaining'])->toBe(2);
    expect($ev1->description)->toContain('Full (60s)');

    // Call 1 30s TO for Home
    $ev2 = $service->callTimeout($game, 'home', '30s');
    expect($ev2->metadata['timeout_type'])->toBe('30s');
    expect($ev2->metadata['timeouts_remaining'])->toBe(3);
    expect($ev2->metadata['full_timeouts_remaining'])->toBe(2);
    expect($ev2->metadata['thirty_second_timeouts_remaining'])->toBe(1);
    expect($ev2->description)->toContain('30-Second');

    // Call 1 30s TO for Away
    $ev3 = $service->callTimeout($game, 'away', '30s');
    expect($ev3->metadata['timeout_type'])->toBe('30s');
    expect($ev3->metadata['timeouts_remaining'])->toBe(4);
    expect($ev3->metadata['full_timeouts_remaining'])->toBe(3);
    expect($ev3->metadata['thirty_second_timeouts_remaining'])->toBe(1);

    $game->refresh();
    $homeBreakdown = $game->calculateTimeoutsBreakdown('home');
    expect($homeBreakdown['allowed_full'])->toBe(3);
    expect($homeBreakdown['allowed_30s'])->toBe(2);
    expect($homeBreakdown['used_full'])->toBe(1);
    expect($homeBreakdown['used_30s'])->toBe(1);
    expect($homeBreakdown['used_total'])->toBe(2);
    expect($homeBreakdown['rem_full'])->toBe(2);
    expect($homeBreakdown['rem_30s'])->toBe(1);
    expect($homeBreakdown['rem_total'])->toBe(3);
    expect($game->home_timeouts_used)->toBe(2);
    expect($game->home_timeouts_taken)->toBe(2);
    expect($game->home_timeouts_remaining)->toBe(3);

    $awayBreakdown = $game->calculateTimeoutsBreakdown('away');
    expect($awayBreakdown['used_full'])->toBe(0);
    expect($awayBreakdown['used_30s'])->toBe(1);
    expect($awayBreakdown['used_total'])->toBe(1);
    expect($awayBreakdown['rem_full'])->toBe(3);
    expect($awayBreakdown['rem_30s'])->toBe(1);
    expect($awayBreakdown['rem_total'])->toBe(4);
    expect($game->away_timeouts_used)->toBe(1);
    expect($game->away_timeouts_taken)->toBe(1);
    expect($game->away_timeouts_remaining)->toBe(4);
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

test('awards additional timeout in overtime according to ncaa rules', function () {
    $game = Game::create([
        'access_code' => 'NCAA01',
        'slug' => 'ncaa-ot-timeout-test',
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'sport' => 'basketball',
        'current_period' => 4,
        'home_team_name' => 'Home',
        'away_team_name' => 'Away',
        'home_timeouts_remaining' => 1,
        'away_timeouts_remaining' => 1,
        'settings' => [
            'rules' => [
                'period_format' => 'quarters',
                'timeouts_full' => 3,
                'timeouts_30s' => 2,
                'timeouts_ot' => 1,
            ],
        ],
    ]);

    expect($game->total_timeouts_allowed)->toBe(5); // 3 full + 2 30s in regulation

    // Advance to OT1 (Period 5) via Operator
    Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('nextPeriod')
        ->assertHasNoErrors();

    $game->refresh();
    expect($game->current_period)->toBe(5);
    expect($game->total_timeouts_allowed)->toBe(6); // 5 + 1 OT timeout
    $breakdown = $game->calculateTimeoutsBreakdown('home');
    expect($breakdown['allowed_30s'])->toBe(3); // 2 regulation + 1 OT
    expect($breakdown['allowed_full'])->toBe(3);
    expect($game->home_timeouts_remaining)->toBe(6); // recalculateGameTotals gives 6 total allowed - 0 used in event log
});

test('volleyball timeouts reset to set allowance and isolation per set works', function () {
    $vGame = Game::create([
        'access_code' => 'VB001',
        'slug' => 'vb-timeout-test',
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'sport' => 'volleyball',
        'current_period' => 1,
        'home_team_name' => 'Volley Eagles',
        'away_team_name' => 'Spikers',
        'home_timeouts_remaining' => 2,
        'away_timeouts_remaining' => 2,
        'settings' => [
            'rules' => [
                'timeouts_per_set' => 2,
            ],
        ],
    ]);

    $vbService = new VolleyballStatService;

    // Use 2 timeouts in Set 1
    $vbService->callTimeout($vGame, 'home');
    $vbService->callTimeout($vGame, 'home');
    $vGame->refresh();
    expect($vGame->home_timeouts_remaining)->toBe(0);

    // Advance to Set 2
    $vbService->advanceSet($vGame);
    $vGame->refresh();
    expect($vGame->current_period)->toBe(2);
    expect($vGame->home_timeouts_remaining)->toBe(2); // Fresh 2 timeouts for Set 2

    // Run recalculateGameTotals - timeouts should remain 2 for Set 2 despite 2 used in Set 1
    $vbService->recalculateGameTotals($vGame);
    $vGame->refresh();
    expect($vGame->home_timeouts_remaining)->toBe(2);
});

test('operator and public scoreboard respect halves format with 2 halves and overtime progression', function () {
    $game = Game::create([
        'access_code' => 'HALF01',
        'slug' => 'halves-format-test',
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'sport' => 'basketball',
        'current_period' => 1,
        'home_team_name' => 'Halves Home',
        'away_team_name' => 'Halves Away',
        'settings' => [
            'rules' => [
                'period_format' => 'halves',
                'period_minutes' => 20,
                'timeouts_full' => 4,
                'timeouts_30s' => 2,
                'timeouts_ot' => 1,
            ],
        ],
    ]);

    expect($game->period_format)->toBe('halves');
    expect($game->period_name)->toBe('1st Half');

    // Operator view initializes with halves periodFormat and dynamic period buttons
    Livewire::actingAs($this->user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->assertSeeHtml("periodFormat: 'halves'")
        ->assertSeeHtml('canAddOvertime()')
        ->assertSeeHtml('addOvertime()');

    // Advance to 2nd Half (Period 2)
    Livewire::actingAs($this->user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('nextPeriod');

    $game->refresh();
    expect($game->current_period)->toBe(2);
    expect($game->period_name)->toBe('2nd Half');

    // Advance from 2nd Half to Overtime (Period 3)
    Livewire::actingAs($this->user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('nextPeriod');

    $game->refresh();
    expect($game->current_period)->toBe(3);
    expect($game->period_name)->toBe('Overtime');

    // Public scoreboard renders 1H and 2H and OT1 headers instead of Q1-Q4
    Livewire::test(PublicScoreboard::class, ['code' => $game->access_code])
        ->assertSeeHtml('>1H<')
        ->assertSeeHtml('>2H<')
        ->assertSeeHtml('>OT1<')
        ->assertDontSeeHtml('>Q1<')
        ->assertDontSeeHtml('>Q2<');
});

test('shows timeouts taken in addition to timeouts remaining with clear distinction in views', function () {
    $game = Game::create([
        'access_code' => 'TOV001',
        'slug' => 'eagles-vs-warriors-tov001',
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
    // Charge 1 full and 1 30s timeout to Home (2 taken, 3 remain)
    $service->callTimeout($game, 'home', 'full');
    $service->callTimeout($game, 'home', '30s');
    // Charge 1 full timeout to Away (1 taken, 4 remain)
    $service->callTimeout($game, 'away', 'full');

    $game->refresh();
    expect($game->home_timeouts_taken)->toBe(2);
    expect($game->home_timeouts_remaining)->toBe(3);
    expect($game->away_timeouts_taken)->toBe(1);
    expect($game->away_timeouts_remaining)->toBe(4);

    // Basketball Operator renders TAKEN and REM distinctly
    Livewire::actingAs($this->user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->assertSeeHtml('homeTimeoutsTaken: 2')
        ->assertSeeHtml('homeTimeouts: 3')
        ->assertSeeHtml('awayTimeoutsTaken: 1')
        ->assertSeeHtml('awayTimeouts: 4')
        ->assertSeeHtml('TAKEN:')
        ->assertSeeHtml('REM:');

    // Pure Scoreboard renders Taken and Remain columns
    Livewire::test(PureScoreboard::class, ['code' => $game->access_code])
        ->assertSeeHtml('Taken')
        ->assertSeeHtml('Remain')
        ->assertSeeHtml('>2</div>')
        ->assertSeeHtml('>3</div>')
        ->assertSeeHtml('>1</div>')
        ->assertSeeHtml('>4</div>');

    // Watch Game renders both Taken and Rem
    Livewire::test(WatchGame::class, ['code' => $game->access_code])
        ->assertSeeHtml('Taken:')
        ->assertSeeHtml('Rem:')
        ->assertSeeHtml('>2</strong>')
        ->assertSeeHtml('>3</strong>');

    // Volleyball: Test volleyball operator and score tracking
    $vGame = Game::create([
        'access_code' => 'VBTO01',
        'slug' => 'eagles-vs-warriors-vbto01',
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'sport' => 'volleyball',
        'home_team_name' => 'LCA Volleyball',
        'away_team_name' => 'Warriors VB',
        'home_timeouts_remaining' => 2,
        'away_timeouts_remaining' => 2,
        'current_period' => 1,
        'settings' => [
            'rules' => [
                'timeouts_per_set' => 2,
            ],
        ],
    ]);

    $vbService = new VolleyballStatService;
    $vbService->callTimeout($vGame, 'home');
    $vGame->refresh();

    expect($vGame->home_timeouts_taken)->toBe(1);
    expect($vGame->home_timeouts_remaining)->toBe(1);

    Livewire::actingAs($this->user)
        ->test(VolleyballOperator::class, ['gameId' => $vGame->id])
        ->assertSeeHtml('homeTimeoutsTaken: 1')
        ->assertSeeHtml('homeTimeouts: 1')
        ->assertSeeHtml('TAKEN:')
        ->assertSeeHtml('REM:');
});
