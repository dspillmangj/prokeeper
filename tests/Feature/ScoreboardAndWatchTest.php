<?php

use App\Livewire\NcaaScorebook;
use App\Livewire\WatchGame;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('operator route redirects to login', function () {
    $response = $this->get('/operator');
    $response->assertRedirect(route('login'));
});

test('scoreboard route redirects to public live view', function () {
    $response = $this->get('/scoreboard');
    $response->assertRedirect(route('public.live.prompt'));
});

test('watch route redirects to public live view', function () {
    $response = $this->get('/watch');
    $response->assertRedirect(route('public.live.prompt'));
});

test('scorebook prompt route redirects to public live view', function () {
    $response = $this->get('/scorebook');
    $response->assertRedirect(route('public.live.prompt'));
});

test('live prompt renders successfully without code', function () {
    $response = $this->get('/live');
    $response->assertStatus(200);
    $response->assertSee('Watch Game Live');
    $response->assertSee('Game Access Code');
});

test('scoreboard game code route redirects to live game view', function () {
    $org = Organization::create(['name' => 'Athletics Org', 'slug' => 'ath-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Op',
        'email' => 'op@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'plan' => 'pro',
    ]);

    $game = Game::create([
        'access_code' => 'SB1234',
        'slug' => 'sb-test-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Warriors',
        'away_team_name' => 'Lakers',
        'home_score' => 78,
        'away_score' => 72,
        'current_period' => 3,
        'home_fouls_current_period' => 4,
        'away_fouls_current_period' => 8,
        'home_timeouts_remaining' => 3,
        'away_timeouts_remaining' => 2,
        'possession_arrow' => 'home',
    ]);

    $response = $this->get('/scoreboard/SB1234');
    $response->assertRedirect(route('public.live', 'SB1234'));
});

test('live view allows tabbing between scoreboard, plays, box score, and summary for pro games', function () {
    $org = Organization::create(['name' => 'Athletics Org 2', 'slug' => 'ath-org-2']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Op 2',
        'email' => 'op2@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'plan' => 'pro',
    ]);

    $game = Game::create([
        'access_code' => 'WT5678',
        'slug' => 'watch-test-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Bulls',
        'away_team_name' => 'Celtics',
        'home_score' => 95,
        'away_score' => 91,
        'current_period' => 4,
    ]);

    GameEvent::create([
        'game_id' => $game->id,
        'period' => 4,
        'team_side' => 'home',
        'sport' => 'basketball',
        'action_type' => 'point_2_made',
        'action_code' => '2P',
        'action_name' => '2pt Made',
        'jersey_number' => '23',
        'player_name' => 'Michael Jordan',
        'points' => 2,
        'home_score_after' => 95,
        'away_score_after' => 91,
        'description' => '#23 Michael Jordan made 2pt driving layup',
        'sequence' => 1,
    ]);

    // Test Scoreboard tab
    Livewire::test(WatchGame::class, ['code' => 'WT5678'])
        ->assertSet('activeTab', 'scoreboard')
        ->assertSee('Period Scoring Breakdown')
        ->call('setTab', 'plays')
        ->assertSet('activeTab', 'plays')
        ->assertSee('Michael Jordan made 2pt driving layup')
        ->call('setTab', 'boxscore')
        ->assertSet('activeTab', 'boxscore')
        ->assertSee('Box Score')
        ->call('setTab', 'summary')
        ->assertSet('activeTab', 'summary')
        ->assertSee('Team Statistical Matchup');
});

test('submitting code from prompt navigates directly to live game view', function () {
    $org = Organization::create(['name' => 'Athletics Org 3', 'slug' => 'ath-org-3']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Op 3',
        'email' => 'op3@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'plan' => 'pro',
    ]);

    $game = Game::create([
        'access_code' => 'PRO100',
        'slug' => 'prompt-test-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Eagles',
        'away_team_name' => 'Hawks',
        'current_period' => 1,
    ]);

    Livewire::test(WatchGame::class)
        ->set('inputCode', 'PRO100')
        ->call('submitCode')
        ->assertRedirect(route('public.live', 'PRO100'));
});
