<?php

use App\Models\Game;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->org = Organization::create([
        'name' => 'LCA Athletics',
        'slug' => 'lca-athletics-test',
    ]);

    $this->user = User::create([
        'name' => 'Coach Dan',
        'email' => 'dan@lca.org',
        'password' => bcrypt('password'),
        'organization_id' => $this->org->id,
        'role' => 'admin',
    ]);

    $this->game = Game::create([
        'access_code' => 'EAG999',
        'slug' => 'eagles-vs-knights-eag999',
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'sport' => 'basketball',
        'home_team_name' => 'LCA Eagles',
        'away_team_name' => 'Grace Knights',
    ]);
});

test('public live scoreboard renders successfully', function () {
    $response = $this->get(route('public.live', 'EAG999'));
    $response->assertStatus(200);
    $response->assertSee('LCA Eagles');
    $response->assertSee('Grace Knights');
});

test('public NCAA scorebook renders successfully', function () {
    $response = $this->get(route('public.scorebook', 'EAG999'));
    $response->assertStatus(200);
    $response->assertSee('Official Basketball Scorebook');
    $response->assertSee('Running Score Progression');
});

test('NCAA PDF download route generates PDF stream', function () {
    $response = $this->get(route('games.pdf', 'EAG999'));
    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
});

test('dashboard requires authentication', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));

    $authResponse = $this->actingAs($this->user)->get(route('dashboard'));
    $authResponse->assertStatus(200);
    $authResponse->assertSee('Athletic Command Center');
});
