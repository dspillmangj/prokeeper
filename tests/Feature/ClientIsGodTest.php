<?php

namespace Tests\Feature;

use App\Livewire\BasketballOperator;
use App\Livewire\VolleyballOperator;
use App\Models\Game;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientIsGodTest extends TestCase
{
    use RefreshDatabase;

    public function test_basketball_operator_forces_client_state_authoritatively(): void
    {
        $org = Organization::create(['name' => 'Athletics Org', 'slug' => 'athletics-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Lions', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Tigers', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'BSK777',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Lions',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Tigers',
            'sport' => 'basketball',
            'status' => 'in_progress',
            'home_score' => 10,
            'away_score' => 8,
            'current_period' => 1,
            'home_fouls_current_period' => 2,
            'away_fouls_current_period' => 1,
            'home_timeouts_remaining' => 5,
            'away_timeouts_remaining' => 5,
            'possession_arrow' => 'home',
        ]);

        $this->actingAs($user);

        // Client has disconnected / suffered WiFi delay and accumulated state: Home 42, Away 39
        $clientSnapshot = [
            'homeScore' => 42,
            'awayScore' => 39,
            'homePeriodScores' => [22, 20, 0, 0, 0, 0],
            'awayPeriodScores' => [18, 21, 0, 0, 0, 0],
            'currentPeriod' => 2,
            'possession' => 'away',
            'homeFouls' => 4,
            'awayFouls' => 3,
            'homeTimeouts' => 3,
            'awayTimeouts' => 2,
        ];

        Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->call('forceClientState', $clientSnapshot);

        $game->refresh();

        $this->assertEquals(42, $game->home_score);
        $this->assertEquals(39, $game->away_score);
        $this->assertEquals([22, 20, 0, 0, 0, 0], $game->home_period_scores);
        $this->assertEquals([18, 21, 0, 0, 0, 0], $game->away_period_scores);
        $this->assertEquals(2, $game->current_period);
        $this->assertEquals('away', $game->possession_arrow);
        $this->assertEquals(4, $game->home_fouls_current_period);
        $this->assertEquals(3, $game->away_fouls_current_period);
        $this->assertEquals(3, $game->home_timeouts_remaining);
        $this->assertEquals(2, $game->away_timeouts_remaining);
    }

    public function test_volleyball_operator_forces_client_state_authoritatively(): void
    {
        $org = Organization::create(['name' => 'Volley Org', 'slug' => 'volley-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Knights', 'sport' => 'volleyball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Spartans', 'sport' => 'volleyball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'VLY888',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Knights',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Spartans',
            'sport' => 'volleyball',
            'status' => 'in_progress',
            'home_score' => 5,
            'away_score' => 4,
            'current_period' => 1,
            'current_server' => 'home',
            'home_rotation' => 1,
            'away_rotation' => 1,
            'home_timeouts_remaining' => 2,
            'away_timeouts_remaining' => 2,
        ]);

        $this->actingAs($user);

        $clientSnapshot = [
            'homeScore' => 25,
            'awayScore' => 23,
            'homePeriodScores' => [25, 0, 0, 0],
            'awayPeriodScores' => [23, 0, 0, 0],
            'currentPeriod' => 1,
            'server' => 'away',
            'homeRotation' => 3,
            'awayRotation' => 4,
            'homeTimeouts' => 1,
            'awayTimeouts' => 0,
        ];

        Livewire::test(VolleyballOperator::class, ['gameId' => $game->id])
            ->call('forceClientState', $clientSnapshot);

        $game->refresh();

        $this->assertEquals(25, $game->home_score);
        $this->assertEquals(23, $game->away_score);
        $this->assertEquals([25, 0, 0, 0], $game->home_period_scores);
        $this->assertEquals([23, 0, 0, 0], $game->away_period_scores);
        $this->assertEquals('away', $game->current_server);
        $this->assertEquals(3, $game->home_rotation);
        $this->assertEquals(4, $game->away_rotation);
        $this->assertEquals(1, $game->home_timeouts_remaining);
        $this->assertEquals(0, $game->away_timeouts_remaining);
    }
}
