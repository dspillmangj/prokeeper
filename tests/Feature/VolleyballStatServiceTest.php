<?php

use App\Models\Game;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\VolleyballStat;
use App\Services\VolleyballStatService;

beforeEach(function () {
    $this->org = Organization::create([
        'name' => 'Test Org',
        'slug' => 'test-org-vb',
    ]);

    $this->game = Game::create([
        'access_code' => 'VB001',
        'slug' => 'lady-eagles-vs-knights-vb1',
        'organization_id' => $this->org->id,
        'sport' => 'volleyball',
        'home_team_name' => 'Lady Eagles',
        'away_team_name' => 'Knights',
        'current_period' => 1,
        'current_server' => 'home',
        'home_rotation' => 1,
        'away_rotation' => 1,
    ]);

    GameLineup::create([
        'game_id' => $this->game->id,
        'team_side' => 'home',
        'jersey_number' => '04',
        'player_name' => 'Hannah Taylor',
        'is_on_court' => true,
    ]);

    GameLineup::create([
        'game_id' => $this->game->id,
        'team_side' => 'away',
        'jersey_number' => '07',
        'player_name' => 'Sophia Baker',
        'is_on_court' => true,
    ]);

    $this->service = new VolleyballStatService;
});

test('records kills, aces, and automatic side-out rotations', function () {
    // Home Kill while serving -> Home point, still home serve
    $res1 = $this->service->processKeyboardInput($this->game, '04-K');
    expect($res1['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->home_score)->toBe(1);
    expect($this->game->current_server)->toBe('home');

    // Away Kill while Home is serving -> Side-out! Away point, Away gains serve, Away rotates to pos 2
    $res2 = $this->service->processKeyboardInput($this->game, '07=K');
    expect($res2['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->away_score)->toBe(1);
    expect($this->game->current_server)->toBe('away');
    expect($this->game->away_rotation)->toBe(2);

    $stat04 = VolleyballStat::where('game_id', $this->game->id)->where('jersey_number', '04')->first();
    expect($stat04->kills)->toBe(1);
    expect($stat04->attack_attempts)->toBe(1);
    expect($stat04->hitting_percentage)->toEqual(1.000);
});
