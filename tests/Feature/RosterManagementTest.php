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
            ],
            [
                'id' => null,
                'first_name' => 'Scottie',
                'last_name' => 'Pippen',
                'jersey_number' => '33',
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

test('spreadsheet batch roster update supports single full name column', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-single-name']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Dan',
        'email' => 'coach2@example.com',
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

    $payload = [
        'players' => [
            [
                'id' => null,
                'jersey_number' => '23',
                'name' => 'Michael Jordan',
            ],
            [
                'id' => null,
                'jersey_number' => '33',
                'name' => 'Scottie Pippen',
            ],
            [
                'id' => null,
                'jersey_number' => '91',
                'name' => 'Dennis Rodman',
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->postJson(route('teams.roster.batch', $team->id), $payload);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    expect($team->rosterPlayers()->count())->toBe(3);

    $jordan = RosterPlayer::where('team_id', $team->id)->where('jersey_number', '23')->first();
    expect($jordan)->not->toBeNull();
    expect($jordan->player->first_name)->toBe('Michael');
    expect($jordan->player->last_name)->toBe('Jordan');
});

test('stat operator can save and update lineup via spreadsheet batch', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-live-spreadsheet']);
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

    $spreadsheetRows = [
        ['id' => null, 'jersey_number' => '23', 'name' => 'Michael Jordan', 'is_on_court' => true],
        ['id' => null, 'jersey_number' => '33', 'name' => 'Scottie Pippen', 'is_on_court' => true],
        ['id' => null, 'jersey_number' => '91', 'name' => 'Dennis Rodman', 'is_on_court' => true],
        ['id' => null, 'jersey_number' => '9', 'name' => 'Ron Harper', 'is_on_court' => true],
        ['id' => null, 'jersey_number' => '25', 'name' => 'Steve Kerr', 'is_on_court' => true],
        ['id' => null, 'jersey_number' => '7', 'name' => 'Toni Kukoc', 'is_on_court' => false],
    ];

    Livewire::actingAs($user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('saveRosterSpreadsheet', 'home', $spreadsheetRows);

    expect(GameLineup::where('game_id', $game->id)->where('team_side', 'home')->count())->toBe(6);

    $onCourtCount = GameLineup::where('game_id', $game->id)->where('team_side', 'home')->where('is_on_court', true)->count();
    expect($onCourtCount)->toBe(5);

    $benchPlayer = GameLineup::where('game_id', $game->id)->where('team_side', 'home')->where('jersey_number', '7')->first();
    expect($benchPlayer->player_name)->toBe('Toni Kukoc');
    expect($benchPlayer->is_on_court)->toBeFalse();
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

test('in-game roster edits propagate to saved team roster for future game reuse', function () {
    $org = Organization::create(['name' => 'State University', 'slug' => 'state-univ']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Head Coach',
        'email' => 'coach@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'State Tigers',
        'sport' => 'basketball',
        'gender' => 'mens',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $player1 = Player::create([
        'organization_id' => $org->id,
        'first_name' => 'Stephen',
        'last_name' => 'Curry',
        'default_jersey_number' => '30',
        'position' => 'PG',
    ]);

    $rp = RosterPlayer::create([
        'team_id' => $team->id,
        'player_id' => $player1->id,
        'jersey_number' => '30',
        'position' => 'PG',
        'is_starter' => true,
    ]);

    $game = Game::create([
        'access_code' => 'GAME99',
        'slug' => 'tigers-vs-bulldogs',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_id' => $team->id,
        'home_team_name' => 'State Tigers',
        'away_team_name' => 'Bulldogs',
        'current_period' => 1,
    ]);

    $lineup = GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'player_id' => $player1->id,
        'jersey_number' => '30',
        'player_name' => 'Stephen Curry',
        'position' => 'PG',
        'is_starter' => true,
        'is_on_court' => true,
    ]);

    // 1. Edit existing player during the game (e.g. jersey # change or name correction)
    Livewire::actingAs($user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('startEditingLineup', $lineup->id)
        ->set('editJersey', '33')
        ->set('editName', 'Wardell Curry')
        ->call('saveEditedLineup');

    // Verify propagation to team roster and player record
    $player1->refresh();
    $rp->refresh();
    expect($player1->first_name)->toBe('Wardell');
    expect($player1->last_name)->toBe('Curry');
    expect($player1->default_jersey_number)->toBe('33');
    expect($rp->jersey_number)->toBe('33');

    // 2. Save via in-game spreadsheet with a newly added player
    Livewire::actingAs($user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('saveRosterSpreadsheet', 'home', [
            ['id' => $lineup->id, 'jersey_number' => '33', 'name' => 'Wardell Curry', 'is_on_court' => true],
            ['id' => null, 'jersey_number' => '11', 'name' => 'Klay Thompson', 'is_on_court' => true],
        ]);

    // Verify Klay Thompson was automatically added to the saved team roster
    $klayRp = RosterPlayer::where('team_id', $team->id)->where('jersey_number', '11')->first();
    expect($klayRp)->not->toBeNull();
    expect($klayRp->player)->not->toBeNull();
    expect($klayRp->player->full_name)->toBe('Klay Thompson');
});

test('roster changes in basketball and volleyball operators dispatch lineups-updated events without page reload', function () {
    $org = Organization::create(['name' => 'State University', 'slug' => 'state-univ-realtime']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Head Coach',
        'email' => 'coach_rt@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $game = Game::create([
        'access_code' => 'RTGAME',
        'slug' => 'realtime-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Tigers',
        'away_team_name' => 'Bulldogs',
        'current_period' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('saveRosterSpreadsheet', 'home', [
            ['id' => null, 'jersey_number' => '23', 'name' => 'Michael Jordan', 'is_on_court' => true],
            ['id' => null, 'jersey_number' => '33', 'name' => 'Scottie Pippen', 'is_on_court' => true],
        ])
        ->assertDispatched('lineups-updated');

    $vbGame = Game::create([
        'access_code' => 'RTVB01',
        'slug' => 'realtime-vb-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'volleyball',
        'status' => 'in_progress',
        'home_team_name' => 'Home VB',
        'away_team_name' => 'Away VB',
        'current_period' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(VolleyballOperator::class, ['gameId' => $vbGame->id])
        ->call('saveRosterSpreadsheet', 'home', [
            ['id' => null, 'jersey_number' => '10', 'name' => 'Karch Kiraly', 'is_on_court' => true],
        ])
        ->assertDispatched('lineups-updated');
});

test('updating team roster on team management page instantly syncs to active game lineups', function () {
    $org = Organization::create(['name' => 'State University', 'slug' => 'state-univ-sync']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Head Coach',
        'email' => 'coach_sync@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'Synced Team',
        'sport' => 'basketball',
        'gender' => 'mens',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $game = Game::create([
        'access_code' => 'SYNC01',
        'slug' => 'synced-team-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_id' => $team->id,
        'home_team_name' => 'Synced Team',
        'away_team_name' => 'Rivals',
        'current_period' => 1,
    ]);

    $response = $this->actingAs($user)->postJson(route('teams.roster.batch', $team->id), [
        'players' => [
            ['id' => null, 'jersey_number' => '77', 'name' => 'Luka Doncic'],
        ],
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['success', 'message', 'players']);

    // Check that game lineup has Luka Doncic automatically without reload
    $gameLineup = GameLineup::where('game_id', $game->id)->where('jersey_number', '77')->first();
    expect($gameLineup)->not->toBeNull();
    expect($gameLineup->player_name)->toBe('Luka Doncic');
});

test('batch roster update and in-game spreadsheet save preserve position and starter status for scorebook', function () {
    $org = Organization::create(['name' => 'Scorebook Org', 'slug' => 'scorebook-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Scorebook',
        'email' => 'scorebook_coach@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'Scorebook Stars',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    // 1. Save roster via batch with position and is_starter
    $response = $this->actingAs($user)->postJson(route('teams.roster.batch', $team->id), [
        'players' => [
            ['id' => null, 'jersey_number' => '23', 'name' => 'Michael Jordan', 'position' => 'SG', 'is_starter' => true],
            ['id' => null, 'jersey_number' => '33', 'name' => 'Scottie Pippen', 'position' => 'SF', 'is_starter' => true],
            ['id' => null, 'jersey_number' => '9', 'name' => 'Ron Harper', 'position' => 'PG', 'is_starter' => false],
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $jordanRp = RosterPlayer::where('team_id', $team->id)->where('jersey_number', '23')->first();
    expect($jordanRp->position)->toBe('SG');
    expect($jordanRp->is_starter)->toBeTrue();

    $harperRp = RosterPlayer::where('team_id', $team->id)->where('jersey_number', '9')->first();
    expect($harperRp->position)->toBe('PG');
    expect($harperRp->is_starter)->toBeFalse();

    // 2. In-game spreadsheet save
    $game = Game::create([
        'access_code' => 'SCRB01',
        'slug' => 'scorebook-game',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_id' => $team->id,
        'home_team_name' => 'Scorebook Stars',
        'away_team_name' => 'Visitors',
        'current_period' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('saveRosterSpreadsheet', 'home', [
            ['id' => null, 'jersey_number' => '23', 'name' => 'Michael Jordan', 'position' => 'SG', 'is_starter' => true, 'is_on_court' => true],
            ['id' => null, 'jersey_number' => '33', 'name' => 'Scottie Pippen', 'position' => 'SF', 'is_starter' => true, 'is_on_court' => true],
            ['id' => null, 'jersey_number' => '91', 'name' => 'Dennis Rodman', 'position' => 'PF', 'is_starter' => true, 'is_on_court' => true],
        ]);

    $rodmanLineup = GameLineup::where('game_id', $game->id)->where('jersey_number', '91')->first();
    expect($rodmanLineup->position)->toBe('PF');
    expect($rodmanLineup->is_starter)->toBeTrue();
});
