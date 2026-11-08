<?php

use App\Models\Game;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;

test('free plan allows creating up to two rosters and blocks the third', function () {
    $org = Organization::create(['name' => 'Free Org', 'slug' => 'free-org', 'plan' => 'free']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Free Coach',
        'email' => 'free@example.com',
        'password' => bcrypt('password'),
        'role' => 'coach',
        'email_verified_at' => now(),
        'plan' => 'free',
    ]);

    // Roster 1 - Success
    $res1 = $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'Varsity Boys',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);
    $res1->assertRedirect();
    expect(Team::where('organization_id', $org->id)->count())->toBe(1);

    // Roster 2 - Success
    $res2 = $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'JV Boys',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'jv',
        'season' => '2026-2027',
    ]);
    $res2->assertRedirect();
    expect(Team::where('organization_id', $org->id)->count())->toBe(2);

    // Roster 3 - Blocked, redirected to billing
    $res3 = $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'Freshman Boys',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'freshman',
        'season' => '2026-2027',
    ]);
    $res3->assertRedirect(route('billing.index'));
    expect(Team::where('organization_id', $org->id)->count())->toBe(2);
});

test('pro plan allows creating unlimited rosters', function () {
    $org = Organization::create(['name' => 'Pro Org', 'slug' => 'pro-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Pro Coach',
        'email' => 'pro@example.com',
        'password' => bcrypt('password'),
        'role' => 'coach',
        'email_verified_at' => now(),
        'plan' => 'pro',
    ]);

    for ($i = 1; $i <= 4; $i++) {
        $this->actingAs($user)->post(route('teams.store'), [
            'name' => "Team {$i}",
            'sport' => 'basketball',
            'gender' => 'boys',
            'level' => 'varsity',
            'season' => '2026',
        ])->assertRedirect();
    }

    expect(Team::where('organization_id', $org->id)->count())->toBe(4);
});

test('free plan games cannot be viewed by anonymous visitors on live view', function () {
    $org = Organization::create(['name' => 'Free Org 2', 'slug' => 'free-org-2']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Free Host',
        'email' => 'freehost@example.com',
        'password' => bcrypt('password'),
        'role' => 'coach',
        'plan' => 'free',
    ]);

    $game = Game::create([
        'access_code' => 'FREE01',
        'slug' => 'free-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'home_team_name' => 'Eagles',
        'away_team_name' => 'Hawks',
    ]);

    // Anonymous visitor tries to view
    $response = $this->get(route('public.live', $game->access_code));
    $response->assertStatus(200);
    $response->assertSee('Private Game');
    $response->assertSee('This game is hosted on a Free plan');

    // Authenticated host CAN view
    $hostResponse = $this->actingAs($user)->get(route('public.live', $game->access_code));
    $hostResponse->assertStatus(200);
    $hostResponse->assertDontSee('This game is hosted on a Free plan');
    $hostResponse->assertSee('Eagles');
});

