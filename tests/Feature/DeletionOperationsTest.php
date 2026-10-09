<?php

use App\Livewire\BasketballOperator;
use App\Livewire\VolleyballOperator;
use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\Player;
use App\Models\RosterPlayer;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can delete a game and all its related records', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-del-game']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Dan',
        'email' => 'coach-del@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'Varsity Boys',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $game = Game::create([
        'access_code' => 'DEL001',
        'slug' => 'eagles-vs-hawks-del',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_id' => $team->id,
        'home_team_name' => 'Eagles',
        'away_team_name' => 'Hawks',
    ]);

    $lineup = GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '23',
        'player_name' => 'Michael Jordan',
    ]);

    $event = GameEvent::create([
        'game_id' => $game->id,
        'period' => 1,
        'team_side' => 'home',
        'action_code' => 'X',
        'action_type' => '2pt_make',
        'action_name' => '2pt MAKE',
    ]);

    $stat = BasketballStat::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '23',
        'player_name' => 'Michael Jordan',
        'points' => 2,
    ]);

    $response = $this->actingAs($user)
        ->delete(route('games.destroy', $game->id));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success');

    expect(Game::find($game->id))->toBeNull();
    expect(GameLineup::find($lineup->id))->toBeNull();
    expect(GameEvent::find($event->id))->toBeNull();
    expect(BasketballStat::find($stat->id))->toBeNull();
});

test('user cannot delete a game belonging to another organization', function () {
    $org1 = Organization::create(['name' => 'Org One', 'slug' => 'org-one']);
    $user1 = User::create([
        'organization_id' => $org1->id,
        'name' => 'User One',
        'email' => 'user1@example.com',
        'password' => bcrypt('password'),
    ]);

    $org2 = Organization::create(['name' => 'Org Two', 'slug' => 'org-two']);
    $user2 = User::create([
        'organization_id' => $org2->id,
        'name' => 'User Two',
        'email' => 'user2@example.com',
        'password' => bcrypt('password'),
    ]);

    $game = Game::create([
        'access_code' => 'SEC001',
        'slug' => 'other-org-game',
        'organization_id' => $org2->id,
        'created_by_user_id' => $user2->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Team A',
        'away_team_name' => 'Team B',
    ]);

    $response = $this->actingAs($user1)
        ->delete(route('games.destroy', $game->id));

    $response->assertNotFound();
    expect(Game::find($game->id))->not->toBeNull();
});

test('authenticated user can delete a team and its roster entries', function () {
    $org = Organization::create(['name' => 'Tigers Athletics', 'slug' => 'tigers-del']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Mike',
        'email' => 'mike@example.com',
        'password' => bcrypt('password'),
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'JV Volleyball',
        'sport' => 'volleyball',
        'gender' => 'girls',
        'level' => 'jv',
        'season' => '2026-2027',
    ]);

    $player = Player::create([
        'organization_id' => $org->id,
        'first_name' => 'Kerri',
        'last_name' => 'Walsh',
        'default_jersey_number' => '1',
    ]);

    $rp = RosterPlayer::create([
        'team_id' => $team->id,
        'player_id' => $player->id,
        'jersey_number' => '1',
    ]);

    $response = $this->actingAs($user)
        ->delete(route('teams.destroy', $team->id));

    $response->assertRedirect(route('teams.index'));
    $response->assertSessionHas('success');

    expect(Team::find($team->id))->toBeNull();
    expect(RosterPlayer::find($rp->id))->toBeNull();
});

test('user cannot delete a team belonging to another organization', function () {
    $org1 = Organization::create(['name' => 'Org A', 'slug' => 'org-a']);
    $user1 = User::create([
        'organization_id' => $org1->id,
        'name' => 'User A',
        'email' => 'a@example.com',
        'password' => bcrypt('password'),
    ]);

    $org2 = Organization::create(['name' => 'Org B', 'slug' => 'org-b']);
    $team2 = Team::create([
        'organization_id' => $org2->id,
        'name' => 'Opponent Team',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $response = $this->actingAs($user1)
        ->delete(route('teams.destroy', $team2->id));

    $response->assertNotFound();
    expect(Team::find($team2->id))->not->toBeNull();
});

test('authenticated user can clear a team roster', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-clear-roster']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Dan',
        'email' => 'dan-clear@example.com',
        'password' => bcrypt('password'),
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'Varsity Basketball',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $p1 = Player::create(['organization_id' => $org->id, 'first_name' => 'P1', 'last_name' => 'L1', 'default_jersey_number' => '10']);
    $p2 = Player::create(['organization_id' => $org->id, 'first_name' => 'P2', 'last_name' => 'L2', 'default_jersey_number' => '11']);

    RosterPlayer::create(['team_id' => $team->id, 'player_id' => $p1->id, 'jersey_number' => '10']);
    RosterPlayer::create(['team_id' => $team->id, 'player_id' => $p2->id, 'jersey_number' => '11']);

    expect($team->rosterPlayers()->count())->toBe(2);

    $response = $this->actingAs($user)
        ->delete(route('teams.roster.clear', $team->id));

    $response->assertRedirect(route('teams.show', $team->id));
    $response->assertSessionHas('success');

    expect($team->rosterPlayers()->count())->toBe(0);
});

test('authenticated user can remove individual player from team roster', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-rem-player']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Dan',
        'email' => 'dan-rem@example.com',
        'password' => bcrypt('password'),
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'Varsity Basketball',
        'sport' => 'basketball',
        'gender' => 'boys',
        'level' => 'varsity',
        'season' => '2026-2027',
    ]);

    $p1 = Player::create(['organization_id' => $org->id, 'first_name' => 'John', 'last_name' => 'Doe', 'default_jersey_number' => '5']);
    $rp = RosterPlayer::create(['team_id' => $team->id, 'player_id' => $p1->id, 'jersey_number' => '5']);

    $response = $this->actingAs($user)
        ->delete(route('teams.players.remove', ['id' => $team->id, 'playerId' => $rp->id]));

    $response->assertSessionHas('success');
    expect(RosterPlayer::find($rp->id))->toBeNull();
});

test('operator can delete game from livewire basketball operator', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-lw-del']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Operator User',
        'email' => 'op-del@example.com',
        'password' => bcrypt('password'),
    ]);

    $game = Game::create([
        'access_code' => 'OPDEL1',
        'slug' => 'test-operator-del',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'basketball',
        'status' => 'in_progress',
        'home_team_name' => 'Home',
        'away_team_name' => 'Away',
    ]);

    Livewire::actingAs($user)
        ->test(BasketballOperator::class, ['gameId' => $game->id])
        ->call('deleteGame')
        ->assertRedirect(route('dashboard'));

    expect(Game::find($game->id))->toBeNull();
});

test('operator can delete game from livewire volleyball operator', function () {
    $org = Organization::create(['name' => 'Eagles Athletics', 'slug' => 'eagles-vb-del']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'VB Operator User',
        'email' => 'vb-op-del@example.com',
        'password' => bcrypt('password'),
    ]);

    $game = Game::create([
        'access_code' => 'VBDEL1',
        'slug' => 'test-vb-operator-del',
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'volleyball',
        'status' => 'in_progress',
        'home_team_name' => 'Home VB',
        'away_team_name' => 'Away VB',
    ]);

    Livewire::actingAs($user)
        ->test(VolleyballOperator::class, ['gameId' => $game->id])
        ->call('deleteGame')
        ->assertRedirect(route('dashboard'));

    expect(Game::find($game->id))->toBeNull();
});
