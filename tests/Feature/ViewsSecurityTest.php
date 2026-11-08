<?php

use App\Models\Game;
use App\Models\Organization;
use App\Models\User;

test('scoreboard and watch routes redirect to live view', function () {
    $this->get('/scoreboard')->assertRedirect(route('public.live.prompt'));
    $this->get('/scoreboard/ABCDEF')->assertRedirect(route('public.live', 'ABCDEF'));
    $this->get('/watch')->assertRedirect(route('public.live.prompt'));
    $this->get('/watch/ABCDEF')->assertRedirect(route('public.live', 'ABCDEF'));
});

test('scorebook and stats require token or operator authorization', function () {
    $org = Organization::create(['name' => 'View Sec Org', 'slug' => 'vsec-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Sec Coach',
        'email' => 'sec@example.com',
        'password' => bcrypt('password'),
        'role' => 'coach',
        'plan' => 'pro',
    ]);

    $game = Game::create([
        'access_code' => 'SEC100',
        'slug' => 'sec-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'home_team_name' => 'Home',
        'away_team_name' => 'Away',
    ]);

    // Missing token: Scorebook shows restricted screen
    $resScorebookNoToken = $this->get(route('public.scorebook', $game->access_code));
    $resScorebookNoToken->assertStatus(200);
    $resScorebookNoToken->assertSee('Scorebook Access Restricted');

    // Valid token: Scorebook renders
    $resScorebookWithToken = $this->get(route('public.scorebook', [
        'code' => $game->access_code,
        'token' => $game->scorebook_token,
    ]));
    $resScorebookWithToken->assertStatus(200);
    $resScorebookWithToken->assertDontSee('Scorebook Access Restricted');

    // Missing token: Stats shows restricted screen
    $resStatsNoToken = $this->get(route('public.stats', $game->access_code));
    $resStatsNoToken->assertStatus(200);
    $resStatsNoToken->assertSee('Coach Statistics Restricted');

    // Valid token: Stats renders
    $resStatsWithToken = $this->get(route('public.stats', [
        'code' => $game->access_code,
        'token' => $game->stats_token,
    ]));
    $resStatsWithToken->assertStatus(200);
    $resStatsWithToken->assertDontSee('Coach Statistics Restricted');

    // PDF export without token is blocked (403)
    $this->get(route('games.pdf', $game->access_code))->assertStatus(403);
    $this->get(route('games.stats.pdf', $game->access_code))->assertStatus(403);

    // PDF export with token is permitted (200)
    $this->get(route('games.pdf', ['code' => $game->access_code, 'token' => $game->scorebook_token]))->assertStatus(200);
    $this->get(route('games.stats.pdf', ['code' => $game->access_code, 'token' => $game->stats_token]))->assertStatus(200);
});

