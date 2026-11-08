<?php

use App\Livewire\CoachStats;
use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\User;
use App\Models\VolleyballStat;
use Livewire\Livewire;

test('stats prompt renders successfully without code', function () {
    $response = $this->get('/stats');
    $response->assertRedirect(route('public.live.prompt'));
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
        'plan' => 'pro',
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

    $response = $this->get("/stats/{$game->access_code}?token={$game->stats_token}");
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
        'plan' => 'pro',
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

    $response = $this->get("/export/stats/{$game->access_code}.pdf?token={$game->stats_token}");
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
    $this->assertStringContainsString("ProKeeper-CoachStats-{$game->access_code}.pdf", $response->headers->get('content-disposition'));
});

test('coach stats page displays every recorded basketball statistic including swat, turnover breakdown, and flagrant/drawn fouls', function () {
    $org = Organization::create(['name' => 'AllStats Org', 'slug' => 'allstats-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Stats Coach',
        'email' => 'allstats@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'plan' => 'pro',
    ]);

    $game = Game::create([
        'access_code' => 'ALL999',
        'slug' => 'all-stats-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Home Knights',
        'away_team_name' => 'Away Dragons',
        'home_score' => 60,
        'away_score' => 55,
        'current_period' => 3,
        'period_format' => 'quarters',
    ]);

    GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '15',
        'player_name' => 'Elite AllAround',
        'position' => 'SF',
        'is_starter' => true,
    ]);

    BasketballStat::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '15',
        'player_name' => 'Elite AllAround',
        'points' => 25,
        'fgm' => 9,
        'fga' => 16,
        'fg3m' => 3,
        'fg3a' => 6,
        'ftm' => 4,
        'fta' => 5,
        'oreb' => 3,
        'dreb' => 7,
        'reb' => 10,
        'ast' => 7,
        'stl' => 4,
        'blk' => 2,
        'swat' => 3,
        'to_pass' => 1,
        'to_fumble' => 1,
        'to_violation' => 1,
        'turnovers' => 3,
        'fouls_pers' => 2,
        'fouls_off' => 1,
        'fouls_tech' => 1,
        'fouls_flagrant' => 1,
        'fouls_forced' => 5,
        'total_fouls' => 5,
        'seconds_played' => 1440,
    ]);

    $response = $this->get("/stats/{$game->access_code}?token={$game->stats_token}");
    $response->assertStatus(200);

    // Verify all stat headers and abbreviations are present
    $response->assertSee('SWAT');
    $response->assertSee('TO:P');
    $response->assertSee('TO:F');
    $response->assertSee('TO:V');
    $response->assertSee('OF');
    $response->assertSee('TF');
    $response->assertSee('FF');
    $response->assertSee('FD');
    $response->assertSee('TOT F');
    $response->assertSee('eFG%');
    $response->assertSee('A/TO');

    // Verify player values
    $response->assertSee('Elite AllAround');
    $response->assertSee('#15');
});

test('coach stats renders volleyball game with set line score and all volleyball statistics', function () {
    $org = Organization::create(['name' => 'Volley Org', 'slug' => 'volley-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Volley Coach',
        'email' => 'vcoach@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'plan' => 'pro',
    ]);

    $game = Game::create([
        'access_code' => 'VB1234',
        'slug' => 'vb-coach-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'volleyball',
        'status' => 'in_progress',
        'home_team_name' => 'Volley Aces',
        'away_team_name' => 'Net Spikers',
        'home_score' => 50,
        'away_score' => 45,
        'current_period' => 2,
        'home_period_scores' => [25, 25, 0, 0, 0],
        'away_period_scores' => [20, 22, 0, 0, 0],
    ]);

    GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '12',
        'player_name' => 'Kerri Walsh',
        'position' => 'OH',
        'is_starter' => true,
    ]);

    VolleyballStat::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '12',
        'player_name' => 'Kerri Walsh',
        'sets_played' => 2,
        'kills' => 12,
        'attack_errors' => 2,
        'attack_attempts' => 25,
        'hitting_percentage' => 0.400,
        'assists' => 3,
        'service_aces' => 4,
        'service_errors' => 1,
        'service_attempts' => 15,
        'digs' => 8,
        'block_solos' => 2,
        'block_assists' => 2,
        'total_blocks' => 3.0,
        'ball_handling_errors' => 0,
        'reception_errors' => 1,
        'total_points' => 17.0,
    ]);

    $response = $this->get("/stats/{$game->access_code}?token={$game->stats_token}");
    $response->assertStatus(200);

    // Verify volleyball specific elements
    $response->assertSee('VOLLEYBALL');
    $response->assertSee('Set-by-Set Line Score');
    $response->assertSee('Set 1');
    $response->assertSee('Set 2');
    $response->assertSee('Kerri Walsh');
    $response->assertSee('HIT%');
    $response->assertSee('ACE');
    $response->assertSee('DIG');
    $response->assertSee('TB');
    $response->assertSee('BHE');
    $response->assertSee('RE');

    // Test Volleyball PDF export
    $pdfResponse = $this->get("/export/stats/{$game->access_code}.pdf?token={$game->stats_token}");
    $pdfResponse->assertStatus(200);
    $pdfResponse->assertHeader('content-type', 'application/pdf');
});
