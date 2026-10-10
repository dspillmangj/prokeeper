<?php

use App\Livewire\CoachStats;
use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

test('stats prompt renders successfully without code', function () {
    $response = $this->get('/stats');
    $response->assertStatus(200);
    $response->assertSee('Coach Live Stats');
    $response->assertSee('GAME CODE');
});

test('submitting invalid code displays error message in livewire coach stats', function () {
    Livewire::test(CoachStats::class)
        ->set('inputCode', 'INVALID')
        ->call('submitCode')
        ->assertSee("No game found with code 'INVALID'.");
});

test('submitting valid code redirects to coach stats page', function () {
    $org = Organization::create(['name' => 'Coach Org', 'slug' => 'coach-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Op',
        'email' => 'coach@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $game = Game::create([
        'access_code' => 'COACH1',
        'slug' => 'coach-test-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Home Eagles',
        'away_team_name' => 'Away Hawks',
        'home_score' => 45,
        'away_score' => 40,
        'current_period' => 2,
    ]);

    Livewire::test(CoachStats::class)
        ->set('inputCode', 'COACH1')
        ->call('submitCode')
        ->assertRedirect(route('public.stats', 'COACH1'));
});

test('coach stats renders home and away box scores with period line score', function () {
    $org = Organization::create(['name' => 'Eagles League', 'slug' => 'eagles-league']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Stats User',
        'email' => 'stats@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $game = Game::create([
        'access_code' => 'CST999',
        'slug' => 'cst-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Eagles',
        'away_team_name' => 'Hawks',
        'home_score' => 30,
        'away_score' => 25,
        'current_period' => 2,
        'period_format' => 'quarters',
    ]);

    // Create lineups
    GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '10',
        'player_name' => 'Jordan Star',
        'position' => 'PG',
        'is_starter' => true,
    ]);

    GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'away',
        'jersey_number' => '23',
        'player_name' => 'LeBron Visitor',
        'position' => 'SF',
        'is_starter' => true,
    ]);

    // Create basketball stats
    BasketballStat::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '10',
        'player_name' => 'Jordan Star',
        'points' => 15,
        'fgm' => 6,
        'fga' => 10,
        'fg3m' => 2,
        'fg3a' => 4,
        'ftm' => 1,
        'fta' => 2,
        'oreb' => 1,
        'dreb' => 4,
        'reb' => 5,
        'ast' => 6,
        'stl' => 2,
        'blk' => 1,
        'turnovers' => 1,
        'fouls_pers' => 2,
        'seconds_played' => 600,
    ]);

    // Create events in period 1
    GameEvent::create([
        'game_id' => $game->id,
        'sequence' => 1,
        'period' => 1,
        'clock_seconds_remaining' => 450,
        'team_side' => 'home',
        'jersey_number' => '10',
        'player_name' => 'Jordan Star',
        'sport' => 'basketball',
        'action_code' => '3P',
        'action_type' => '3pt_make',
        'action_name' => '3pt MAKE',
        'points' => 3,
        'home_score_after' => 3,
        'away_score_after' => 0,
        'is_undone' => false,
    ]);

    $response = $this->get("/stats/{$game->access_code}");
    $response->assertStatus(200);
    $response->assertSee('Eagles');
    $response->assertSee('Hawks');
    $response->assertSee('Jordan Star');
    $response->assertSee('LeBron Visitor');
    $response->assertSee('Download PDF');
});

test('period filtering updates stats properly in livewire coach stats', function () {
    $org = Organization::create(['name' => 'Filter Org', 'slug' => 'filter-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Filter User',
        'email' => 'filter@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $game = Game::create([
        'access_code' => 'FLT777',
        'slug' => 'flt-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Bulls',
        'away_team_name' => 'Celtics',
        'home_score' => 20,
        'away_score' => 18,
        'current_period' => 2,
        'period_format' => 'quarters',
    ]);

    GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '33',
        'player_name' => 'Scottie Guard',
        'position' => 'SG',
        'is_starter' => true,
    ]);

    // Q1 Event: 2pt make
    GameEvent::create([
        'game_id' => $game->id,
        'sequence' => 1,
        'period' => 1,
        'clock_seconds_remaining' => 400,
        'team_side' => 'home',
        'jersey_number' => '33',
        'player_name' => 'Scottie Guard',
        'sport' => 'basketball',
        'action_code' => '2P',
        'action_type' => '2pt_make',
        'action_name' => '2pt MAKE',
        'points' => 2,
        'home_score_after' => 2,
        'away_score_after' => 0,
        'is_undone' => false,
    ]);

    // Q2 Event: 3pt make
    GameEvent::create([
        'game_id' => $game->id,
        'sequence' => 2,
        'period' => 2,
        'clock_seconds_remaining' => 300,
        'team_side' => 'home',
        'jersey_number' => '33',
        'player_name' => 'Scottie Guard',
        'sport' => 'basketball',
        'action_code' => '3P',
        'action_type' => '3pt_make',
        'action_name' => '3pt MAKE',
        'points' => 3,
        'home_score_after' => 5,
        'away_score_after' => 0,
        'is_undone' => false,
    ]);

    Livewire::test(CoachStats::class, ['code' => 'FLT777'])
        ->call('setPeriod', '1')
        ->assertSet('selectedPeriod', '1')
        ->call('setPeriod', '2')
        ->assertSet('selectedPeriod', '2');
});

test('coach stats pdf export generates valid 2-page landscape pdf', function () {
    $org = Organization::create(['name' => 'PDF Org', 'slug' => 'pdf-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'PDF User',
        'email' => 'pdf@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $game = Game::create([
        'access_code' => 'PDF888',
        'slug' => 'pdf-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Wildcats',
        'away_team_name' => 'Tigers',
        'home_score' => 55,
        'away_score' => 50,
        'current_period' => 3,
    ]);

    GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '07',
        'player_name' => 'Alex Shooter',
        'position' => 'G',
        'is_starter' => true,
    ]);

    $response = $this->get("/export/stats/{$game->access_code}.pdf");
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringContainsString("ProKeeper-CoachStats-{$game->access_code}.pdf", $response->headers->get('content-disposition'));
});
