<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_page_loads_successfully(): void
    {
        $response = $this->get('/install');

        $response->assertStatus(200);
        $response->assertSee('Install', false);
        $response->assertSee('ProKeeper', false);
        $response->assertSee('on Your Device', false);
        $response->assertSee('Choose Your App Icon Theme', false);
        $response->assertSee('Electric Blue', false);
        $response->assertSee('Midnight Obsidian', false);
        $response->assertSee('Pure Quartz', false);
        $response->assertSee('Sign In & Score Games', false);
        $response->assertSee('Enter Game Code', false);
        $response->assertSee('Scoreboard', false);
    }

    public function test_install_page_displays_recent_games_when_present(): void
    {
        $org = Organization::create(['name' => 'Test Org', 'slug' => 'test-org']);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $homeTeam = Team::create(['organization_id' => $org->id, 'name' => 'Eagles', 'sport' => 'basketball']);
        $awayTeam = Team::create(['organization_id' => $org->id, 'name' => 'Hawks', 'sport' => 'basketball']);

        $game = Game::create([
            'organization_id' => $org->id,
            'created_by_user_id' => $user->id,
            'access_code' => 'TEST99',
            'home_team_id' => $homeTeam->id,
            'home_team_name' => 'Eagles',
            'away_team_id' => $awayTeam->id,
            'away_team_name' => 'Hawks',
            'sport' => 'basketball',
            'status' => 'in_progress',
        ]);

        $response = $this->get('/install');

        $response->assertStatus(200);
        $response->assertSee('TEST99');
        $response->assertSee('Eagles vs Hawks');
    }

    public function test_manifest_returns_valid_json_for_different_themes(): void
    {
        // Default / Blue theme
        $responseBlue = $this->get('/manifest.json?theme=blue');
        $responseBlue->assertStatus(200);
        $responseBlue->assertHeader('Content-Type', 'application/manifest+json');
        $this->assertStringContainsString('appicon_blue_pwa_192.png', $responseBlue->getContent());

        // Black / Obsidian theme
        $responseBlack = $this->get('/manifest.json?theme=black');
        $responseBlack->assertStatus(200);
        $this->assertStringContainsString('appicon_black_pwa_192.png', $responseBlack->getContent());
        $this->assertStringContainsString('Midnight Obsidian', $responseBlack->getContent());

        // White / Quartz theme
        $responseWhite = $this->get('/manifest.json?theme=white');
        $responseWhite->assertStatus(200);
        $this->assertStringContainsString('appicon_white_pwa_192.png', $responseWhite->getContent());
        $this->assertStringContainsString('Pure Quartz', $responseWhite->getContent());
    }
}
