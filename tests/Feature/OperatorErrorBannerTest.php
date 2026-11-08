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

class OperatorErrorBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_basketball_operator_renders_split_feedback_error_banner(): void
    {
        $org = Organization::create(['name' => 'Error Test Org', 'slug' => 'error-test-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Home Team', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Away Team', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'ERRBB1',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Home Team',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Away Team',
            'sport' => 'basketball',
            'status' => 'in_progress',
            'current_period' => 1,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->assertSeeHtml('data-testid="operator-feedback-banner"')
            ->assertSeeHtml('attentionAlert')
            ->assertSeeHtml('alertFlashActive')
            ->assertSeeHtml('dismissAttentionAlert')
            ->assertSeeHtml('errorQueue')
            ->assertSeeHtml('frontError')
            ->assertSeeHtml('dismissFrontError')
            ->assertSeeHtml('dismissAllErrors')
            ->assertSeeHtml('toggleErrorList')
            ->assertSeeHtml('openErrorHover')
            ->assertSeeHtml('closeErrorHover')
            ->assertSeeHtml('Auto-clears in 30s')
            ->assertSeeHtml('30s auto-clear');

        // Trigger error (invalid period change)
        $component->call('setPeriod', 99);
        $this->assertEquals('error', $component->get('feedbackType'));
        $this->assertEquals('Period must be between 1 and 6.', $component->get('feedbackMessage'));

        // Assert game-state-updated transmits the error back to user
        $component->assertDispatched('game-state-updated', function ($eventName, $params) {
            $payload = $params[0] ?? $params;

            return isset($payload['feedbackType']) && $payload['feedbackType'] === 'error'
                && isset($payload['feedbackMessage']) && $payload['feedbackMessage'] === 'Period must be between 1 and 6.';
        });
    }

    public function test_volleyball_operator_renders_split_feedback_error_banner(): void
    {
        $org = Organization::create(['name' => 'Error Test Org VB', 'slug' => 'error-test-org-vb']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Home VB', 'sport' => 'volleyball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Away VB', 'sport' => 'volleyball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'ERRVB1',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Home VB',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Away VB',
            'sport' => 'volleyball',
            'status' => 'in_progress',
            'current_period' => 1,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(VolleyballOperator::class, ['gameId' => $game->id])
            ->assertSeeHtml('data-testid="operator-feedback-banner"')
            ->assertSeeHtml('attentionAlert')
            ->assertSeeHtml('alertFlashActive')
            ->assertSeeHtml('dismissAttentionAlert')
            ->assertSeeHtml('errorQueue')
            ->assertSeeHtml('frontError')
            ->assertSeeHtml('dismissFrontError')
            ->assertSeeHtml('dismissAllErrors')
            ->assertSeeHtml('toggleErrorList')
            ->assertSeeHtml('openErrorHover')
            ->assertSeeHtml('closeErrorHover')
            ->assertSeeHtml('Auto-clears in 30s')
            ->assertSeeHtml('30s auto-clear');

        // Trigger error (invalid set)
        $component->call('setSet', 99);
        $this->assertEquals('error', $component->get('feedbackType'));
        $this->assertEquals('Set must be between 1 and 5.', $component->get('feedbackMessage'));

        // Assert game-state-updated transmits the error back to user
        $component->assertDispatched('game-state-updated', function ($eventName, $params) {
            $payload = $params[0] ?? $params;

            return isset($payload['feedbackType']) && $payload['feedbackType'] === 'error'
                && isset($payload['feedbackMessage']) && $payload['feedbackMessage'] === 'Set must be between 1 and 5.';
        });
    }

    public function test_basketball_operator_renders_foul_out_and_bonus_attention_elements(): void
    {
        $org = Organization::create(['name' => 'Attention Test Org', 'slug' => 'attention-test-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Home Attention', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Away Attention', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'ATTN01',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Home Attention',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Away Attention',
            'sport' => 'basketball',
            'status' => 'in_progress',
            'current_period' => 1,
            'rules' => [
                'bonus_foul_threshold' => 5,
                'double_bonus_foul_threshold' => 10,
                'player_foul_limit' => 5,
            ],
        ]);

        $this->actingAs($user);

        Livewire::test(BasketballOperator::class, ['gameId' => $game->id])
            ->assertSeeHtml('doubleBonusFoulThreshold')
            ->assertSeeHtml('bonusFoulThreshold')
            ->assertSeeHtml('playerFoulLimit')
            ->assertSeeHtml('hazard-stripes-red')
            ->assertSeeHtml('animate-bombastic-strobe')
            ->assertSeeHtml('attentionAlert')
            ->assertSeeHtml('alertFlashActive')
            ->assertSeeHtml('FOULED OUT')
            ->assertSeeHtml("attentionAlert.type !== 'error'")
            ->assertDontSeeHtml('fixed inset-0 pointer-events-none z-50');
    }
}
