<?php

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->org = \App\Models\Organization::create([
        'name' => 'LCA Athletics',
        'slug' => 'lca-athletics-ncaa',
    ]);

    $this->user = User::create([
        'name' => 'Coach Dan',
        'email' => 'dan_ncaa@lca.org',
        'password' => bcrypt('password'),
        'organization_id' => $this->org->id,
        'role' => 'admin',
    ]);

    $this->game = Game::create([
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'home_team_name' => 'Duke Blue Devils',
        'away_team_name' => 'UNC Tar Heels',
        'sport' => 'basketball',
        'status' => 'active',
        'period' => 2,
        'home_score' => 45,
        'away_score' => 42,
        'home_team_fouls' => 6,
        'away_team_fouls' => 8,
        'home_timeouts_remaining' => 4,
        'away_timeouts_remaining' => 3,
        'access_code' => 'NCAA2026',
        'slug' => 'duke-vs-unc-ncaa2026',
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
    ]);

    // Add Starters
    GameLineup::create([
        'game_id' => $this->game->id,
        'team_side' => 'home',
        'jersey_number' => '5',
        'player_name' => 'Cooper Flagg',
        'position' => 'F',
        'is_starter' => true,
        'is_on_court' => true,
    ]);

    GameLineup::create([
        'game_id' => $this->game->id,
        'team_side' => 'away',
        'jersey_number' => '4',
        'player_name' => 'RJ Davis',
        'position' => 'G',
        'is_starter' => true,
        'is_on_court' => true,
    ]);

    // Scoring Events
    GameEvent::create([
        'game_id' => $this->game->id,
        'team_side' => 'home',
        'jersey_number' => '5',
        'action_code' => '3P',
        'action_type' => 'score',
        'action_name' => '3pt Made',
        'points' => 3,
        'period' => 1,
        'sequence' => 1,
    ]);

    GameEvent::create([
        'game_id' => $this->game->id,
        'team_side' => 'away',
        'jersey_number' => '4',
        'action_code' => '2P',
        'action_type' => 'score',
        'action_name' => '2pt Made',
        'points' => 2,
        'period' => 1,
        'sequence' => 2,
    ]);
});

test('operator header displays Official Scorebook button for basketball games', function () {
    $response = $this->actingAs($this->user)->get(route('games.operator', $this->game->uuid));
    $response->assertStatus(200);
    $response->assertSee('Official Scorebook');
    $response->assertSee(route('public.scorebook', $this->game->access_code));
});

test('operator header displays Share button with escapable modal and all three spectator routes', function () {
    $response = $this->actingAs($this->user)->get(route('games.operator', $this->game->uuid));
    $response->assertStatus(200);
    $response->assertDontSee('<span>Fan Live</span>', false);
    $response->assertSee('<span>Share</span>', false);
    $response->assertSee($this->game->access_code);
    $response->assertSee(route('public.scoreboard', $this->game->access_code));
    $response->assertSee(route('public.scorebook', $this->game->access_code));
    $response->assertSee(route('public.live', $this->game->access_code));
    $response->assertSee('showShareModal');
});


test('scorebook renders 2-page official ledger layout with team sheets and running score', function () {
    $response = $this->get(route('public.scorebook', $this->game->access_code));
    $response->assertStatus(200);
    
    // Page 1 Home & Page 2 Away
    $response->assertSee('PAGE 1 OF 2');
    $response->assertSee('PAGE 2 OF 2');
    $response->assertSee('HOME TEAM SCOREBOOK PAGE');
    $response->assertSee('VISITING TEAM SCOREBOOK PAGE');
    
    // Check Players
    $response->assertSee('Cooper Flagg');
    $response->assertSee('RJ Davis');

    // Check Running Score Matrix
    $response->assertSee('Official Running Score Progression');
    $response->assertSee('Team Fouls Cumulative Tracker');
    $response->assertSee('Bonus (1+1)');
    $response->assertSee('Double Bonus (2 Shots)');
    $response->assertSee('OFFICIAL SCORER');
    $response->assertSee('REFEREE');
    $response->assertSee('UMPIRE 1');
    $response->assertSee('UMPIRE 2');
});

test('operator can configure game-level details including date, venue, officials, and clock', function () {
    \Livewire\Livewire::actingAs($this->user)
        ->test(\App\Livewire\BasketballOperator::class, ['gameId' => $this->game->id])
        ->call('openGameDetailsModal')
        ->assertSet('showGameDetailsModal', true)
        ->set('gameVenue', 'Cameron Indoor Stadium')
        ->set('gameScheduledDate', '2026-11-15')
        ->set('gameScheduledTime', '20:00')
        ->set('officialReferee', 'Ted Valentine')
        ->set('officialUmpire1', 'Tony Greene')
        ->set('officialScorer', 'Alice Walker')
        ->set('gameClockMinutes', 7)
        ->set('gameClockSeconds', 30)
        ->call('saveGameDetails')
        ->assertSet('showGameDetailsModal', false);

    $this->game->refresh();
    expect($this->game->venue)->toBe('Cameron Indoor Stadium');
    expect($this->game->clock_seconds_remaining)->toBe(450); // 7:30 = 450s
    expect($this->game->settings['officials']['referee'])->toBe('Ted Valentine');
    expect($this->game->settings['officials']['official_scorer'])->toBe('Alice Walker');

    // Verify it renders on official scorebook page
    $scorebookResponse = $this->get(route('public.scorebook', $this->game->access_code));
    $scorebookResponse->assertStatus(200);
    $scorebookResponse->assertSee('Cameron Indoor Stadium');
    $scorebookResponse->assertSee('Ted Valentine');
    $scorebookResponse->assertSee('Alice Walker');
});

test('scorebook page contains landscape print directive and 2-page print layout', function () {
    $response = $this->get(route('public.scorebook', $this->game->access_code));
    $response->assertStatus(200);
    $response->assertSee('size: letter landscape;', false);
    $response->assertSee('ncaa-page-sheet', false);
    $response->assertSee('break-after: page', false);
});

test('scorebook PDF export downloads in letter landscape orientation', function () {
    $response = $this->get(route('games.pdf', $this->game->access_code));
    $response->assertStatus(200);
    $response->assertHeader('content-disposition', 'attachment; filename=ProKeeper-Scorebook-'.$this->game->access_code.'.pdf');
});

test('officials can draw or type signature or initials in scorebook and persist certification', function () {
    $drawnData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    \Livewire\Livewire::test(\App\Livewire\NcaaScorebook::class, ['code' => $this->game->access_code])
        ->call('openSignatureModal', 'referee')
        ->assertSet('showSignatureModal', true)
        ->assertSet('activeSignRole', 'referee')
        ->call('saveSignature', 'referee', 'draw', $drawnData, 'Marcus Taylor', 'MT', null, '#000000')
        ->assertSet('showSignatureModal', false);

    $this->game->refresh();
    expect($this->game->settings['signatures']['referee'])->not->toBeNull();
    expect($this->game->settings['signatures']['referee']['type'])->toBe('draw');
    expect($this->game->settings['signatures']['referee']['signer_name'])->toBe('Marcus Taylor');
    expect($this->game->settings['signatures']['referee']['initials'])->toBe('MT');
    expect($this->game->settings['officials']['referee'])->toBe('Marcus Taylor');

    // Test typing a signature for official scorer
    $typedData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    \Livewire\Livewire::test(\App\Livewire\NcaaScorebook::class, ['code' => $this->game->access_code])
        ->call('saveSignature', 'official_scorer', 'type', $typedData, 'Sarah Jenkins', 'SJ', 'dancing_script', '#0f2942');

    $this->game->refresh();
    expect($this->game->settings['signatures']['official_scorer']['type'])->toBe('type');
    expect($this->game->settings['signatures']['official_scorer']['font_style'])->toBe('dancing_script');

    // Verify rendered on scorebook page
    $response = $this->get(route('public.scorebook', $this->game->access_code));
    $response->assertStatus(200);
    $response->assertSee('Marcus Taylor');
    $response->assertSee('Sarah Jenkins');
    $response->assertSee('SIGNED');
    $response->assertSee('Signatures (2/4)');

    // Test clearing a signature
    \Livewire\Livewire::test(\App\Livewire\NcaaScorebook::class, ['code' => $this->game->access_code])
        ->call('clearSignature', 'referee');

    $this->game->refresh();
    expect(isset($this->game->settings['signatures']['referee']))->toBeFalse();
});


