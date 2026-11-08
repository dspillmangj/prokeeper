<?php

use App\Mail\GameSharedMail;
use App\Mail\RosterSharedMail;
use App\Models\Game;
use App\Models\Organization;
use App\Models\RosterShare;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('organization members have instant access to all organization rosters and games', function () {
    $org = Organization::create(['name' => 'United Athletics', 'slug' => 'united-ath']);
    $user1 = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach One',
        'email' => 'c1@united.org',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);
    $user2 = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Two',
        'email' => 'c2@united.org',
        'password' => bcrypt('password'),
        'role' => 'coach',
        'email_verified_at' => now(),
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'United Eagles',
        'sport' => 'basketball',
    ]);

    $game = Game::create([
        'organization_id' => $org->id,
        'created_by_user_id' => $user1->id,
        'sport' => 'basketball',
        'home_team_name' => 'United Eagles',
        'away_team_name' => 'Rivals',
    ]);

    expect($team->canBeViewedBy($user2))->toBeTrue();
    expect($team->canBeEditedBy($user2))->toBeTrue();
    expect($game->canBeOperatedBy($user2))->toBeTrue();
});

test('user can share roster with external email with edit permission', function () {
    $org1 = Organization::create(['name' => 'School A', 'slug' => 'school-a']);
    $user1 = User::create([
        'organization_id' => $org1->id,
        'name' => 'Coach A',
        'email' => 'coachA@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    $org2 = Organization::create(['name' => 'School B', 'slug' => 'school-b']);
    $user2 = User::create([
        'organization_id' => $org2->id,
        'name' => 'Coach B',
        'email' => 'coachB@example.com',
        'password' => bcrypt('password'),
        'role' => 'coach',
        'email_verified_at' => now(),
    ]);

    $team = Team::create([
        'organization_id' => $org1->id,
        'name' => 'School A Varsity',
        'sport' => 'basketball',
    ]);

    // Before sharing, Coach B cannot view or edit
    expect($team->canBeViewedBy($user2))->toBeFalse();
    expect($team->canBeEditedBy($user2))->toBeFalse();

    // Coach A shares team with Coach B as edit
    $response = $this->actingAs($user1)->post(route('teams.share', $team->id), [
        'email' => 'coachB@example.com',
        'permission' => 'edit',
    ]);
    $response->assertRedirect();

    expect($team->canBeViewedBy($user2))->toBeTrue();
    expect($team->canBeEditedBy($user2))->toBeTrue();

    // Shared team appears in Coach B's teams index
    $indexResponse = $this->actingAs($user2)->get(route('teams.index'));
    $indexResponse->assertStatus(200);
    $indexResponse->assertSee('School A Varsity');
});

test('user can share game with external email and revoke access', function () {
    $org = Organization::create(['name' => 'Org X', 'slug' => 'org-x']);
    $user1 = User::create([
        'organization_id' => $org->id,
        'name' => 'Host User',
        'email' => 'host@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    $user2 = User::create([
        'name' => 'Guest User',
        'email' => 'guest@example.com',
        'password' => bcrypt('password'),
        'role' => 'scorekeeper',
        'email_verified_at' => now(),
    ]);

    $game = Game::create([
        'organization_id' => $org->id,
        'created_by_user_id' => $user1->id,
        'sport' => 'basketball',
        'home_team_name' => 'Host Home',
        'away_team_name' => 'Host Away',
    ]);

    expect($game->canBeOperatedBy($user2))->toBeFalse();

    // Share with guest
    $this->actingAs($user1)->post(route('games.share', $game->uuid), [
        'email' => 'guest@example.com',
        'permission' => 'edit',
    ])->assertRedirect();

    expect($game->canBeOperatedBy($user2))->toBeTrue();

    // Revoke share
    $share = $game->gameShares()->first();
    $this->actingAs($user1)->delete(route('games.share.remove', ['uuid' => $game->uuid, 'shareId' => $share->id]))
        ->assertRedirect();

    expect($game->canBeOperatedBy($user2))->toBeFalse();
});

test('sharing roster sends email notification when notify is enabled and skips when disabled', function () {
    Mail::fake();

    $org = Organization::create(['name' => 'Athletics Org', 'slug' => 'ath-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Coach Carter',
        'email' => 'carter@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    $team = Team::create([
        'organization_id' => $org->id,
        'name' => 'Oilers Varsity',
        'sport' => 'basketball',
    ]);

    // 1. Share with notify enabled (default)
    $this->actingAs($user)->post(route('teams.share', $team->id), [
        'email' => 'assistant@example.com',
        'permission' => 'edit',
        'notify' => '1',
        'message' => 'Please review starting lineup for Friday.',
    ])->assertRedirect();

    Mail::assertSent(RosterSharedMail::class, function ($mail) {
        return $mail->hasTo('assistant@example.com') &&
            $mail->permission === 'edit' &&
            $mail->note === 'Please review starting lineup for Friday.' &&
            $mail->team->name === 'Oilers Varsity';
    });

    // 2. Share with notify disabled
    Mail::fake();
    $this->actingAs($user)->post(route('teams.share', $team->id), [
        'email' => 'silent@example.com',
        'permission' => 'view',
        'notify' => '0',
    ])->assertRedirect();

    Mail::assertNotSent(RosterSharedMail::class);
});

test('sharing game sends email notification with custom message', function () {
    Mail::fake();

    $org = Organization::create(['name' => 'Conference Org', 'slug' => 'conf-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Operator Smith',
        'email' => 'smith@example.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'email_verified_at' => now(),
    ]);

    $game = Game::create([
        'organization_id' => $org->id,
        'created_by_user_id' => $user->id,
        'sport' => 'volleyball',
        'home_team_name' => 'Hawks',
        'away_team_name' => 'Eagles',
    ]);

    $this->actingAs($user)->post(route('games.share', $game->uuid), [
        'email' => 'referee@example.com',
        'permission' => 'view',
        'notify' => '1',
        'message' => 'Here is the live match sheet for tonight.',
    ])->assertRedirect();

    Mail::assertSent(GameSharedMail::class, function ($mail) {
        return $mail->hasTo('referee@example.com') &&
            $mail->permission === 'view' &&
            $mail->note === 'Here is the live match sheet for tonight.' &&
            $mail->game->home_team_name === 'Hawks';
    });
});

