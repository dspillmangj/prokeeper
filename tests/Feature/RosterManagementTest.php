<?php

use App\Livewire\BasketballOperator;
use App\Livewire\VolleyballOperator;
use App\Models\Game;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\Player;
use App\Models\RosterPlayer;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('spreadsheet batch roster update creates, updates, and deletes team players', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Dan',
        'email' => 'coach@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'Varsity Basketball',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    // Initial player
    $player1 = Player::create([
        'organization_id' => $org->id,
        'first_name' => 'Michael',
        'last_name' => 'Jordan',
        'default_jersey_number' => '23',
    ]);
    $rp1 = RosterPlayer::create([
        'team_id' => $team->id,
        'player_id' => $player1->id,
        'jersey_number' => '23',
        'position' => 'SG',
        'is_starter' => true,
    ]);

    // Player to be deleted by omission
    $player2 = Player::create([
        'organization_id' => $org->id,
        'first_name' => 'Old',
        'last_name' => 'Player',
        'default_jersey_number' => '99',
    ]);
    $rp2 = RosterPlayer::create([
        'team_id' => $team->id,
        'player_id' => $player2->id,
        'jersey_number' => '99',
    ]);

    $payload = [
        'players' => [
            [
                'id' => $rp1->id,
                'first_name' => 'Michael',
                'last_name' => 'Jordan',
                'jersey_number' => '23',
                'position' => 'SG',
                'is_starter' => true,
                'is_libero' => false,
            ],
            [
                'id' => null,
                'first_name' => 'Scottie',
                'last_name' => 'Pippen',
                'jersey_number' => '33',
                'position' => 'SF',
                'is_starter' => true,
                'is_libero' => false,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->postJson(route('teams.roster.batch', $team->id), $payload);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    expect($team->rosterPlayers()->count())->toBe(2);
    expect(RosterPlayer::where('id', $rp2->id)->exists())->toBeFalse();
    expect(RosterPlayer::where('team_id', $team->id)->where('jersey_number', '33')->exists())->toBeTrue();
});

test('stat operator can add players on the fly right on stat entry screen', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-live']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Scorekeeper',
        'email' => 'scorekeeper@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $homeTeam = Team::create([
        'organization_id' => $org->id,
        'name' => 'LBS Eagles',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $game = Game::create([
        'access_code' => 'TEST01',
        'slug' => 'lbs-vs-opp',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_id' => $homeTeam->id,
        'home_team_name' => 'LBS Eagles',
        'away_team_name' => 'Opponents',
        'current_period' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('openRosterModal', 'home', 'create')
        ->set('newJersey', '15')
        ->set('newName', 'Nikola Jokic')
        ->set('newPosition', 'C')
        ->set('newIsOnCourt', true)
        ->set('newIsStarter', true)
        ->call('quickAddLineupPlayer')
        ->assertSet('showRosterModal', true);

    $lineup = GameLineup::where('game_id', $game->id)
        ->where('team_side', 'home')
        ->where('jersey_number', '15')
        ->first();

    expect($lineup)->not->toBeNull();
    expect($lineup->player_name)->toBe('Nikola Jokic');
    expect($lineup->is_on_court)->toBeTrue();

    // Verify it synced to team roster in DB
    expect(RosterPlayer::where('team_id', $homeTeam->id)->where('jersey_number', '15')->exists())->toBeTrue();
});

test('stat operator can edit players and bulk import on the fly in volleyball', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-vb']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Volleyball Op',
        'email' => 'vb@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $game = Game::create([
        'access_code' => 'VB0001',
        'slug' => 'vb-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'volleyball',
        'status' => 'in_progress',
        'home_team_name' => 'Home VB',
        'away_team_name' => 'Away VB',
        'current_period' => 1,
    ]);

    $bulkText = "10 Karch Kiraly OH Starter\n5 Misty May S Starter\n7 Logan Tom L Libero";

    Livewire::actingAs($user)
        ->test(VolleyballOperator::class, ['gameId' => $game->id])
        ->call('openRosterModal', 'home', 'paste')
        ->set('bulkRosterInput', $bulkText)
        ->call('importBulkRosterToGame')
        ->assertSet('showRosterModal', true);

    expect(GameLineup::where('game_id', $game->id)->where('team_side', 'home')->count())->toBe(3);

    $libero = GameLineup::where('game_id', $game->id)->where('jersey_number', '7')->first();
    expect($libero->is_libero)->toBeTrue();

    // Test editing a player lineup
    Livewire::actingAs($user)
        ->test(VolleyballOperator::class, ['gameId' => $game->id])
        ->call('startEditingLineup', $libero->id)
        ->set('editJersey', '77')
        ->set('editName', 'Logan Tom Updated')
        ->call('saveEditedLineup');

    $updatedLibero = GameLineup::find($libero->id);
    expect($updatedLibero->jersey_number)->toBe('77');
    expect($updatedLibero->player_name)->toBe('Logan Tom Updated');
});
