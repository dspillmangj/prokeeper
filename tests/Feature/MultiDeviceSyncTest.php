<?php

namespace Tests\Feature;

use App\Livewire\BasketballOperator;
use App\Livewire\VolleyballOperator;
use App\Models\Game;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MultiDeviceSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_devices_heartbeat_updates_presence_count(): void
    {
        $org = Organization::create(['name' => 'Athletics Org', 'slug' => 'athletics-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Lions', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Tigers', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'BSK101',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Lions',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Tigers',
            'sport' => 'basketball',
            'status' => 'in_progress',
        ]);

        $this->actingAs($user);

        // Device 1 (Stat Tracker) heartbeats
        $component1 = Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->call('heartbeatDevice', 'device_alpha', 'Table Tablet', 'stat')
            ->assertDispatched('presence-updated');

        $this->assertEquals(1, $component1->get('activeDeviceCount'));

        // Device 2 (Roster Editor) heartbeats on same game
        $component2 = Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->call('heartbeatDevice', 'device_beta', 'Assistant Phone', 'roster')
            ->assertDispatched('presence-updated');

        $this->assertEquals(2, $component2->get('activeDeviceCount'));
    }

    public function test_roster_device_does_not_clobber_stat_device_score_snapshot(): void
    {
        $org = Organization::create(['name' => 'Athletics Org', 'slug' => 'athletics-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Lions', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Tigers', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'BSK102',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Lions',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Tigers',
            'sport' => 'basketball',
            'status' => 'in_progress',
            'home_score' => 54,
            'away_score' => 48,
            'current_period' => 3,
        ]);

        $this->actingAs($user);

        // Device 2 (Roster Device) has an older local snapshot with Home: 0, Away: 0
        // Because Device 2 was only editing rosters, its snapshot is domain-tagged as 'roster'
        $rosterDeviceSnapshot = [
            'homeScore' => 0,
            'awayScore' => 0,
            'currentPeriod' => 1,
        ];

        Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->call('forceClientState', $rosterDeviceSnapshot, ['domain' => 'roster', 'deviceId' => 'device_beta']);

        $game->refresh();

        // The live score remains untouched because Client is God for domain: 'roster'
        $this->assertEquals(54, $game->home_score);
        $this->assertEquals(48, $game->away_score);
        $this->assertEquals(3, $game->current_period);
    }

    public function test_stat_device_updates_scores_with_scoped_domain(): void
    {
        $org = Organization::create(['name' => 'Athletics Org', 'slug' => 'athletics-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Lions', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Tigers', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'BSK103',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Lions',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Tigers',
            'sport' => 'basketball',
            'status' => 'in_progress',
            'home_score' => 10,
            'away_score' => 8,
            'current_period' => 1,
            'home_fouls_current_period' => 1,
            'away_fouls_current_period' => 2,
        ]);

        $this->actingAs($user);

        $statDeviceSnapshot = [
            'homeScore' => 24,
            'awayScore' => 22,
            'homePeriodScores' => [24, 0, 0, 0, 0, 0],
            'awayPeriodScores' => [22, 0, 0, 0, 0, 0],
        ];

        Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->call('forceClientState', $statDeviceSnapshot, ['domain' => 'score', 'deviceId' => 'device_alpha']);

        $game->refresh();

        $this->assertEquals(24, $game->home_score);
        $this->assertEquals(22, $game->away_score);
        // Fouls were not in score domain, so they remained intact
        $this->assertEquals(1, $game->home_fouls_current_period);
        $this->assertEquals(2, $game->away_fouls_current_period);
    }

    public function test_conflict_recording_and_resolution_flow(): void
    {
        $org = Organization::create(['name' => 'Athletics Org', 'slug' => 'athletics-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Lions', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Tigers', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'BSK104',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Lions',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Tigers',
            'sport' => 'basketball',
            'status' => 'in_progress',
        ]);

        $lineup = GameLineup::create([
            'game_id' => $game->id,
            'team_side' => 'home',
            'jersey_number' => '12',
            'player_name' => 'Original Name',
            'is_on_court' => true,
        ]);

        $this->actingAs($user);

        // Record a conflict between Device Alpha and Device Beta on Player #12
        $component = Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->call('recordConflict', 'roster_player', '12', [
                'id' => 'device_alpha',
                'label' => 'Main Table',
                'description' => 'Renamed #12 to "John Smith"',
                'payload' => [
                    'jersey_number' => '12',
                    'team_side' => 'home',
                    'player_name' => 'John Smith',
                ],
            ], [
                'id' => 'device_beta',
                'label' => 'Assistant Phone',
                'description' => 'Renamed #12 to "Johnny Smith Jr."',
                'payload' => [
                    'jersey_number' => '12',
                    'team_side' => 'home',
                    'player_name' => 'Johnny Smith Jr.',
                ],
            ], 'Simultaneous Roster Edit on Jersey #12')
            ->assertDispatched('conflicts-updated');

        $conflicts = $component->get('activeConflicts');
        $this->assertCount(1, $conflicts);
        $conflictId = $conflicts[0]['id'];

        // Operator on device clicks "Accept Other" (Device Beta's name)
        $component->call('resolveConflict', $conflictId, 'other')
            ->assertDispatched('conflicts-updated');

        $this->assertCount(0, $component->get('activeConflicts'));

        $lineup->refresh();
        $this->assertEquals('Johnny Smith Jr.', $lineup->player_name);
    }

    public function test_volleyball_multi_device_presence_and_domain_scoped_sync(): void
    {
        $org = Organization::create(['name' => 'Volley Org', 'slug' => 'volley-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Knights', 'sport' => 'volleyball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Spartans', 'sport' => 'volleyball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'VLY105',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Knights',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Spartans',
            'sport' => 'volleyball',
            'status' => 'in_progress',
            'home_score' => 18,
            'away_score' => 16,
            'current_period' => 2,
            'home_rotation' => 1,
            'away_rotation' => 2,
        ]);

        $this->actingAs($user);

        // Device 1 heartbeats
        $component1 = Livewire::test(VolleyballOperator::class, ['gameId' => $game->id])
            ->call('heartbeatDevice', 'vly_device_1', 'Main Scorer', 'stat')
            ->assertDispatched('presence-updated');

        $this->assertEquals(1, $component1->get('activeDeviceCount'));

        // Device 2 heartbeats
        $component2 = Livewire::test(VolleyballOperator::class, ['gameId' => $game->id])
            ->call('heartbeatDevice', 'vly_device_2', 'Bench Tablet', 'roster')
            ->assertDispatched('presence-updated');

        $this->assertEquals(2, $component2->get('activeDeviceCount'));

        // Device 2 forces roster domain without overwriting score or rotation
        $rosterSnapshot = [
            'homeScore' => 0,
            'awayScore' => 0,
            'homeRotation' => 6,
        ];

        $component2->call('forceClientState', $rosterSnapshot, ['domain' => 'roster', 'deviceId' => 'vly_device_2']);

        $game->refresh();
        $this->assertEquals(18, $game->home_score);
        $this->assertEquals(16, $game->away_score);
        $this->assertEquals(1, $game->home_rotation);
    }
}
