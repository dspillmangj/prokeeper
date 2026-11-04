<?php

namespace Tests\Feature;

use App\Livewire\BasketballOperator;
use App\Livewire\VolleyballOperator;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GameFidelityTest extends TestCase
{
    use RefreshDatabase;

    public function test_basketball_operator_loads_and_dispatches_all_events_beyond_100_plays(): void
    {
        $org = Organization::create(['name' => 'Fidelity Org', 'slug' => 'fidelity-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Eagles', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Hawks', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'FID100',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Eagles',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Hawks',
            'sport' => 'basketball',
            'status' => 'in_progress',
            'home_score' => 0,
            'away_score' => 0,
            'current_period' => 3,
        ]);

        GameLineup::create([
            'game_id' => $game->id,
            'team_side' => 'home',
            'jersey_number' => '10',
            'player_name' => 'Shooter',
            'is_on_court' => true,
        ]);

        // Create 150 game events spanning Period 1, 2, and 3 (simulating a game halfway through / 3rd quarter)
        for ($i = 1; $i <= 150; $i++) {
            $period = $i <= 50 ? 1 : ($i <= 100 ? 2 : 3);
            GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $i,
                'period' => $period,
                'team_side' => 'home',
                'jersey_number' => '10',
                'player_name' => 'Shooter',
                'sport' => 'basketball',
                'action_code' => 'D',
                'action_type' => 'rebound',
                'action_name' => 'Defensive Rebound',
                'points' => 0,
                'description' => "Event #{$i} in Period {$period}",
                'is_undone' => false,
            ]);
        }

        $this->actingAs($user);

        $component = Livewire::test(BasketballOperator::class, ['gameId' => $game->id]);

        // Verify that the view renders with all 150 events, NOT truncated to 100
        $recentEvents = $component->viewData('recentEvents');
        $this->assertCount(150, $recentEvents);

        // Verify dispatchGameStateUpdate also sends all 150 events
        $component->call('dispatchGameStateUpdate');
        $component->assertDispatched('game-state-updated', function ($eventName, $params) {
            $payload = $params[0] ?? $params;

            return isset($payload['recentEvents']) && count($payload['recentEvents']) === 150;
        });
    }

    public function test_volleyball_operator_loads_and_dispatches_all_events_beyond_100_plays(): void
    {
        $org = Organization::create(['name' => 'Fidelity VB Org', 'slug' => 'fidelity-vb-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Knights', 'sport' => 'volleyball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Spartans', 'sport' => 'volleyball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'FIDVB100',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Knights',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Spartans',
            'sport' => 'volleyball',
            'status' => 'in_progress',
            'current_period' => 2,
        ]);

        for ($i = 1; $i <= 130; $i++) {
            $period = $i <= 65 ? 1 : 2;
            GameEvent::create([
                'game_id' => $game->id,
                'sequence' => $i,
                'period' => $period,
                'team_side' => 'home',
                'jersey_number' => '5',
                'player_name' => 'Spiker',
                'sport' => 'volleyball',
                'action_code' => 'DIG',
                'action_type' => 'defense',
                'action_name' => 'Dig',
                'points' => 0,
                'description' => "VB Event #{$i}",
                'is_undone' => false,
            ]);
        }

        $this->actingAs($user);

        $component = Livewire::test(VolleyballOperator::class, ['gameId' => $game->id]);
        $recentEvents = $component->viewData('recentEvents');
        $this->assertCount(130, $recentEvents);

        $component->call('dispatchGameStateUpdate');
        $component->assertDispatched('game-state-updated', function ($eventName, $params) {
            $payload = $params[0] ?? $params;

            return isset($payload['recentEvents']) && count($payload['recentEvents']) === 130;
        });
    }

    public function test_force_client_state_cannot_wipe_or_downgrade_scores_below_active_game_events(): void
    {
        $org = Organization::create(['name' => 'Safe Org', 'slug' => 'safe-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Lions', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Tigers', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'SAFE999',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Lions',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Tigers',
            'sport' => 'basketball',
            'status' => 'in_progress',
            'home_score' => 45,
            'away_score' => 38,
            'home_period_scores' => [22, 23, 0, 0],
            'away_period_scores' => [18, 20, 0, 0],
            'current_period' => 2,
        ]);

        // Create 20 scoring plays in period 1 (22 pts) and period 2 (23 pts)
        GameEvent::create([
            'game_id' => $game->id,
            'sequence' => 1,
            'period' => 1,
            'team_side' => 'home',
            'points' => 22,
            'action_code' => 'X',
            'action_type' => 'stat',
            'action_name' => 'Scoring Run',
            'description' => 'Period 1 home plays',
            'is_undone' => false,
        ]);
        GameEvent::create([
            'game_id' => $game->id,
            'sequence' => 2,
            'period' => 2,
            'team_side' => 'home',
            'points' => 23,
            'action_code' => 'X',
            'action_type' => 'stat',
            'action_name' => 'Scoring Run',
            'description' => 'Period 2 home plays',
            'is_undone' => false,
        ]);
        GameEvent::create([
            'game_id' => $game->id,
            'sequence' => 3,
            'period' => 1,
            'team_side' => 'away',
            'points' => 18,
            'action_code' => 'X',
            'action_type' => 'stat',
            'action_name' => 'Scoring Run',
            'description' => 'Period 1 away plays',
            'is_undone' => false,
        ]);
        GameEvent::create([
            'game_id' => $game->id,
            'sequence' => 4,
            'period' => 2,
            'team_side' => 'away',
            'points' => 20,
            'action_code' => 'X',
            'action_type' => 'stat',
            'action_name' => 'Scoring Run',
            'description' => 'Period 2 away plays',
            'is_undone' => false,
        ]);

        $this->actingAs($user);

        // Simulate a corrupted/truncated client snapshot:
        // Client only has Q2 (23 pts) and thinks Q1 is 0, attempting to downgrade home score to 23 and Q1 to 0!
        $truncatedClientSnapshot = [
            'homeScore' => 23,
            'awayScore' => 20,
            'homePeriodScores' => [0, 23, 0, 0],
            'awayPeriodScores' => [0, 20, 0, 0],
            'currentPeriod' => 2,
        ];

        Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->call('forceClientState', $truncatedClientSnapshot, ['domain' => 'stats']);

        $game->refresh();

        // FIDELITY CHECK: The scores MUST NOT be downgraded below the active DB events!
        $this->assertEquals(45, $game->home_score);
        $this->assertEquals(38, $game->away_score);
        $this->assertEquals(22, $game->home_period_scores[0]);
        $this->assertEquals(23, $game->home_period_scores[1]);
        $this->assertEquals(18, $game->away_period_scores[0]);
        $this->assertEquals(20, $game->away_period_scores[1]);
    }

    public function test_volleyball_force_client_state_protects_existing_set_points(): void
    {
        $org = Organization::create(['name' => 'Safe VB Org', 'slug' => 'safe-vb-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Knights', 'sport' => 'volleyball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Spartans', 'sport' => 'volleyball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'SAFEVB1',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Knights',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Spartans',
            'sport' => 'volleyball',
            'status' => 'in_progress',
            'home_score' => 25,
            'away_score' => 22,
            'home_period_scores' => [25, 0, 0, 0, 0],
            'away_period_scores' => [22, 0, 0, 0, 0],
            'current_period' => 2,
        ]);

        GameEvent::create([
            'game_id' => $game->id,
            'sequence' => 1,
            'period' => 1,
            'team_side' => 'home',
            'points' => 25,
            'action_code' => 'K',
            'action_type' => 'stat',
            'action_name' => 'Kill',
            'description' => 'Set 1 kills',
            'is_undone' => false,
        ]);
        GameEvent::create([
            'game_id' => $game->id,
            'sequence' => 2,
            'period' => 1,
            'team_side' => 'away',
            'points' => 22,
            'action_code' => 'K',
            'action_type' => 'stat',
            'action_name' => 'Kill',
            'description' => 'Set 1 kills',
            'is_undone' => false,
        ]);

        $this->actingAs($user);

        // Corrupted snapshot attempting to reset Set 1 to 0
        $staleSnapshot = [
            'homeScore' => 0,
            'awayScore' => 0,
            'homePeriodScores' => [0, 0, 0, 0, 0],
            'awayPeriodScores' => [0, 0, 0, 0, 0],
            'currentPeriod' => 2,
        ];

        Livewire::test(VolleyballOperator::class, ['gameId' => $game->id])
            ->call('forceClientState', $staleSnapshot, ['domain' => 'score']);

        $game->refresh();

        $this->assertEquals(25, $game->home_score);
        $this->assertEquals(22, $game->away_score);
        $this->assertEquals(25, $game->home_period_scores[0]);
        $this->assertEquals(22, $game->away_period_scores[0]);
    }

    public function test_operator_feedback_banner_renders_with_error_and_success_states(): void
    {
        $org = Organization::create(['name' => 'Banner Org', 'slug' => 'banner-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Eagles', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Hawks', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'BAN101',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Eagles',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Hawks',
            'sport' => 'basketball',
            'status' => 'in_progress',
            'current_period' => 1,
        ]);

        $this->actingAs($user);

        // Test basketball operator renders feedback banner
        $component = Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->assertSeeHtml('data-testid="operator-feedback-banner"');

        // Trigger an invalid period change to verify error feedbackType
        $component->call('setPeriod', 99);
        $this->assertEquals('error', $component->get('feedbackType'));
        $this->assertEquals('Period must be between 1 and 6.', $component->get('feedbackMessage'));

        // Verify dispatchGameStateUpdate includes error feedback state
        $component->assertDispatched('game-state-updated', function ($eventName, $params) {
            $payload = $params[0] ?? $params;

            return isset($payload['feedbackType']) && $payload['feedbackType'] === 'error'
                && isset($payload['feedbackMessage']) && $payload['feedbackMessage'] === 'Period must be between 1 and 6.';
        });

        // Test volleyball operator renders feedback banner
        $vbGame = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'BANVB1',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Eagles VB',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Hawks VB',
            'sport' => 'volleyball',
            'status' => 'in_progress',
            'current_period' => 1,
        ]);

        $vbComponent = Livewire::test(VolleyballOperator::class, ['gameId' => $vbGame->id])
            ->assertSeeHtml('data-testid="operator-feedback-banner"');

        $vbComponent->call('setSet', 99);
        $this->assertEquals('error', $vbComponent->get('feedbackType'));
    }
}
