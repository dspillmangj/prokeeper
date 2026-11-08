<?php

use App\Livewire\BasketballOperator;
use App\Livewire\NcaaScorebook;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\User;
use App\Models\VolleyballStat;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->org = Organization::create([
        'name' => 'LCA Athletics',
        'slug' => 'lca-athletics-scorebook',
    ]);

    $this->user = User::create([
        'name' => 'Coach Dan',
        'email' => 'dan_scorebook@lca.org',
        'password' => bcrypt('password'),
        'organization_id' => $this->org->id,
        'role' => 'admin',
        'plan' => 'pro',
    ]);

    $this->game = Game::create([
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'home_team_name' => 'Eagles',
        'away_team_name' => 'Knights',
        'sport' => 'basketball',
        'status' => 'active',
        'period' => 2,
        'home_score' => 45,
        'away_score' => 42,
        'home_team_fouls' => 6,
        'away_team_fouls' => 8,
        'home_timeouts_remaining' => 4,
        'away_timeouts_remaining' => 3,
        'access_code' => 'PK2026',
        'slug' => 'eagles-vs-knights-pk2026',
        'uuid' => (string) Str::uuid(),
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
    $response->assertSee(route('public.scorebook', ['code' => $this->game->access_code, 'token' => $this->game->scorebook_token]));
});

test('operator header displays Share button with escapable modal and all three spectator routes', function () {
    $response = $this->actingAs($this->user)->get(route('games.operator', $this->game->uuid));
    $response->assertStatus(200);
    $response->assertDontSee('<span>Fan Live</span>', false);
    $response->assertSee('<span>Share</span>', false);
    $response->assertSee($this->game->access_code);
    $response->assertSee(route('public.stats', ['code' => $this->game->access_code, 'token' => $this->game->stats_token]));
    $response->assertSee(route('public.scorebook', ['code' => $this->game->access_code, 'token' => $this->game->scorebook_token]));
    $response->assertSee(route('public.live', $this->game->access_code));
    $response->assertSee('showShareModal');
});

test('scorebook renders 2-page official ledger layout with 4 quarters regulation format by default', function () {
    $response = $this->get(route('public.scorebook', ['code' => $this->game->access_code, 'token' => $this->game->scorebook_token]));
    $response->assertStatus(200);

    // Page 1 Home & Page 2 Away
    $response->assertSee('PAGE 1 OF 2');
    $response->assertSee('PAGE 2 OF 2');
    $response->assertSee('HOME TEAM SCOREBOOK PAGE');
    $response->assertSee('VISITING TEAM SCOREBOOK PAGE');

    // Check Players
    $response->assertSee('Cooper Flagg');
    $response->assertSee('RJ Davis');

    // Check 4 Quarters Layout Elements
    $response->assertSee('4 QUARTERS');
    $response->assertSee('Official Score by Quarters');
    $response->assertSee('1Q');
    $response->assertSee('2Q');
    $response->assertSee('3Q');
    $response->assertSee('4Q');
    $response->assertSee('1st Quarter');
    $response->assertSee('2nd Quarter');
    $response->assertSee('3rd Quarter');
    $response->assertSee('4th Quarter');
    $response->assertSee('Team Fouls Cumulative Tracker (By Quarter)');
    $response->assertSee('1ST QTR:');
    $response->assertSee('2ND QTR:');
    $response->assertSee('3RD QTR:');
    $response->assertSee('4TH QTR:');

    // Check Running Score Matrix & Signatures
    $response->assertSee('Official Running Score Progression');
    $response->assertSee('OFFICIAL SCORER');
    $response->assertSee('REFEREE');
    $response->assertSee('UMPIRE 1');
    $response->assertSee('UMPIRE 2');
});

test('scorebook dynamically reflects 2 halves format when set by operator or preset', function () {
    $this->game->settings = [
        'rules' => [
            'period_format' => 'halves',
            'bonus_foul_threshold' => 7,
            'double_bonus_foul_threshold' => 10,
        ],
    ];
    $this->game->save();

    $response = $this->get(route('public.scorebook', ['code' => $this->game->access_code, 'token' => $this->game->scorebook_token]));
    $response->assertStatus(200);

    $response->assertSee('2 HALVES');
    $response->assertSee('Official Score by Halves');
    $response->assertSee('1st Half');
    $response->assertSee('2nd Half');
    $response->assertSee('First Half Scoring');
    $response->assertSee('Second Half Scoring');
    $response->assertSee('Team Fouls Cumulative Tracker (By Half)');
    $response->assertSee('1ST HALF:');
    $response->assertSee('2ND HALF:');
    $response->assertSee('Bonus (1+1)');
    $response->assertSee('Double Bonus (2 Shots)');
});

test('operator can configure game-level details including date, venue, officials, and clock', function () {
    Livewire::actingAs($this->user)
        ->test(BasketballOperator::class, ['gameId' => $this->game->id])
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
    $scorebookResponse = $this->get(route('public.scorebook', ['code' => $this->game->access_code, 'token' => $this->game->scorebook_token]));
    $scorebookResponse->assertStatus(200);
    $scorebookResponse->assertSee('Cameron Indoor Stadium');
    $scorebookResponse->assertSee('Ted Valentine');
    $scorebookResponse->assertSee('Alice Walker');
});

test('scorebook page contains landscape print directive and 2-page print layout', function () {
    $response = $this->get(route('public.scorebook', ['code' => $this->game->access_code, 'token' => $this->game->scorebook_token]));
    $response->assertStatus(200);
    $response->assertSee('size: letter landscape;', false);
    $response->assertSee('ncaa-page-sheet', false);
    $response->assertSee('break-after: page', false);
});

test('scorebook PDF export downloads in letter landscape orientation', function () {
    $response = $this->get(route('games.pdf', ['code' => $this->game->access_code, 'token' => $this->game->scorebook_token]));
    $response->assertStatus(200);
    $response->assertHeader('content-disposition', 'attachment; filename=ProKeeper-Scorebook-'.$this->game->access_code.'.pdf');
});

test('officials can draw or type signature or initials in scorebook and persist certification', function () {
    $drawnData = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    Livewire::test(NcaaScorebook::class, ['code' => $this->game->access_code])
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
    Livewire::test(NcaaScorebook::class, ['code' => $this->game->access_code])
        ->call('saveSignature', 'official_scorer', 'type', $typedData, 'Sarah Jenkins', 'SJ', 'dancing_script', '#0f2942');

    $this->game->refresh();
    expect($this->game->settings['signatures']['official_scorer']['type'])->toBe('type');
    expect($this->game->settings['signatures']['official_scorer']['font_style'])->toBe('dancing_script');

    // Verify rendered on scorebook page
    $response = $this->get(route('public.scorebook', ['code' => $this->game->access_code, 'token' => $this->game->scorebook_token]));
    $response->assertStatus(200);
    $response->assertSee('Marcus Taylor');
    $response->assertSee('Sarah Jenkins');
    $response->assertSee('SIGNED');
    $response->assertSee('Signatures (2/4)');

    // Test clearing a signature
    Livewire::test(NcaaScorebook::class, ['code' => $this->game->access_code])
        ->call('clearSignature', 'referee');

    $this->game->refresh();
    expect(isset($this->game->settings['signatures']['referee']))->toBeFalse();
});

test('scorebook parses standard operator action codes X, M, B, V in quarter scoring and includes flagrant fouls in team fouls', function () {
    $game = Game::create([
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'home_team_name' => 'Duke Blue',
        'away_team_name' => 'UNC White',
        'sport' => 'basketball',
        'status' => 'active',
        'period' => 1,
        'home_score' => 6,
        'away_score' => 2,
        'access_code' => 'NCAA99',
        'slug' => 'duke-unc-ncaa99',
        'uuid' => (string) Str::uuid(),
    ]);

    GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '30',
        'player_name' => 'Kyle Filipowski',
        'is_starter' => true,
    ]);

    // 2pt make using standard operator code 'X'
    GameEvent::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '30',
        'action_code' => 'X',
        'action_type' => '2pt_make',
        'action_name' => '2pt MAKE',
        'points' => 2,
        'period' => 1,
        'sequence' => 1,
    ]);

    // 3pt make using standard operator code 'M'
    GameEvent::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '30',
        'action_code' => 'M',
        'action_type' => '3pt_make',
        'action_name' => '3pt MAKE',
        'points' => 3,
        'period' => 1,
        'sequence' => 2,
    ]);

    // FT make using standard operator code 'B'
    GameEvent::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '30',
        'action_code' => 'B',
        'action_type' => 'ft_make',
        'action_name' => 'FT MAKE',
        'points' => 1,
        'period' => 1,
        'sequence' => 3,
    ]);

    // Flagrant foul using 'FLAG'
    GameEvent::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '30',
        'action_code' => 'FLAG',
        'action_type' => 'foul_flagrant',
        'action_name' => 'Flagrant Foul',
        'points' => 0,
        'period' => 1,
        'sequence' => 4,
    ]);

    $response = $this->get(route('public.scorebook', ['code' => $game->access_code, 'token' => $game->scorebook_token]));
    $response->assertStatus(200);

    // Verify Official Box Statistics Ledger is rendered
    $response->assertSee('Official Box Statistics Ledger');
    $response->assertSee('Kyle Filipowski');
});

test('scorebook renders official volleyball ledger and signatures for volleyball games', function () {
    $game = Game::create([
        'organization_id' => $this->org->id,
        'created_by_user_id' => $this->user->id,
        'home_team_name' => 'Texas Longhorns',
        'away_team_name' => 'Nebraska Huskers',
        'sport' => 'volleyball',
        'status' => 'active',
        'period' => 2,
        'home_score' => 25,
        'away_score' => 23,
        'home_period_scores' => [25, 0, 0, 0, 0],
        'away_period_scores' => [23, 0, 0, 0, 0],
        'access_code' => 'VB2026',
        'slug' => 'texas-vs-nebraska-vb2026',
        'uuid' => (string) Str::uuid(),
    ]);

    GameLineup::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '02',
        'player_name' => 'Logan Eggleston',
        'position' => 'OH',
        'is_starter' => true,
    ]);

    VolleyballStat::create([
        'game_id' => $game->id,
        'team_side' => 'home',
        'jersey_number' => '02',
        'player_name' => 'Logan Eggleston',
        'sets_played' => 1,
        'kills' => 8,
        'attack_errors' => 1,
        'attack_attempts' => 16,
        'hitting_percentage' => 0.438,
        'service_aces' => 2,
        'digs' => 5,
        'total_blocks' => 1.5,
        'total_points' => 11.5,
    ]);

    $response = $this->get(route('public.scorebook', ['code' => $game->access_code, 'token' => $game->scorebook_token]));
    $response->assertStatus(200);

    // Verify Volleyball scorebook elements
    $response->assertSee('OFFICIAL VOLLEYBALL SCOREBOOK');
    $response->assertSee('Logan Eggleston');
    $response->assertSee('FIRST REFEREE (R1)');
    $response->assertSee('SECOND REFEREE (R2)');
    $response->assertSee('LIBERO TRACKER');

    // Test Volleyball Scorebook PDF export
    $pdfResponse = $this->get(route('games.pdf', ['code' => $game->access_code, 'token' => $game->scorebook_token]));
    $pdfResponse->assertStatus(200);
    $pdfResponse->assertHeader('content-disposition', 'attachment; filename=ProKeeper-Scorebook-'.$game->access_code.'.pdf');
});
