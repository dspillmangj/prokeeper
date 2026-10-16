<?php

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\Team;
use App\Services\BasketballStatService;

beforeEach(function () {
    $this->org = Organization::create([
        'name' => 'Test Org',
        'slug' => 'test-org',
    ]);

    $this->homeTeam = Team::create([
        'organization_id' => $this->org->id,
        'name' => 'Eagles',
        'sport' => 'basketball',
    ]);

    $this->awayTeam = Team::create([
        'organization_id' => $this->org->id,
        'name' => 'Warriors',
        'sport' => 'basketball',
    ]);

    $this->game = Game::create([
        'access_code' => 'TST001',
        'slug' => 'eagles-vs-warriors-tst1',
        'organization_id' => $this->org->id,
        'sport' => 'basketball',
        'home_team_id' => $this->homeTeam->id,
        'home_team_name' => 'Eagles',
        'away_team_id' => $this->awayTeam->id,
        'away_team_name' => 'Warriors',
        'clock_seconds_remaining' => 480,
    ]);

    GameLineup::create([
        'game_id' => $this->game->id,
        'team_side' => 'home',
        'jersey_number' => '23',
        'player_name' => 'Michael Jordan',
        'is_on_court' => true,
    ]);

    GameLineup::create([
        'game_id' => $this->game->id,
        'team_side' => 'away',
        'jersey_number' => '11',
        'player_name' => 'Stephen Curry',
        'is_on_court' => true,
    ]);

    $this->service = new BasketballStatService;
});

test('processes rapid keyboard 2pt and 3pt makes', function () {
    $res1 = $this->service->processKeyboardInput($this->game, '23-X');
    expect($res1['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->home_score)->toBe(2);

    $res2 = $this->service->processKeyboardInput($this->game, '11=M');
    expect($res2['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->away_score)->toBe(3);

    $stat23 = BasketballStat::where('game_id', $this->game->id)->where('jersey_number', '23')->first();
    expect($stat23->points)->toBe(2);
    expect($stat23->fgm)->toBe(1);
    expect($stat23->fga)->toBe(1);

    $stat11 = BasketballStat::where('game_id', $this->game->id)->where('jersey_number', '11')->first();
    expect($stat11->points)->toBe(3);
    expect($stat11->fg3m)->toBe(1);
    expect($stat11->fg3a)->toBe(1);
});

test('handles rebounds and fouls correctly', function () {
    $this->service->processKeyboardInput($this->game, '23-D'); // Def reb
    $this->service->processKeyboardInput($this->game, '23-F'); // Personal foul

    $this->game->refresh();
    expect($this->game->home_fouls_current_period)->toBe(1);

    $stat23 = BasketballStat::where('game_id', $this->game->id)->where('jersey_number', '23')->first();
    expect($stat23->dreb)->toBe(1);
    expect($stat23->reb)->toBe(1);
    expect($stat23->total_fouls)->toBe(1);
});

test('undoes plays with mathematical consistency', function () {
    $this->service->processKeyboardInput($this->game, '23-X'); // +2 pts
    $this->service->processKeyboardInput($this->game, '11=M'); // +3 pts

    $this->game->refresh();
    expect($this->game->home_score)->toBe(2);
    expect($this->game->away_score)->toBe(3);

    $undone = $this->service->undoLastEvent($this->game);
    expect($undone)->not->toBeNull();

    $this->game->refresh();
    expect($this->game->away_score)->toBe(0);
    expect($this->game->home_score)->toBe(2);
});

test('records audit event for timeouts and decreases remaining timeouts', function () {
    $res = $this->service->callTimeout($this->game, 'home', 'full', 450);
    expect($res)->toBeInstanceOf(GameEvent::class);

    $this->game->refresh();
    expect($this->game->home_timeouts_remaining)->toBe(4); // default 5 - 1

    $event = GameEvent::where('game_id', $this->game->id)->where('action_code', 'TIMEOUT')->first();
    expect($event)->not->toBeNull();
    expect($event->team_side)->toBe('home');
    expect($event->action_name)->toContain('Timeout');
});

test('records audit event for score adjustments and updates game score', function () {
    $res = $this->service->adjustScore($this->game, 'away', 3, 400, 'Score correction by referee table');
    expect($res)->toBeInstanceOf(GameEvent::class);

    $this->game->refresh();
    expect($this->game->away_score)->toBe(3);

    $event = GameEvent::where('game_id', $this->game->id)->where('action_code', 'SCORE_ADJ')->first();
    expect($event)->not->toBeNull();
    expect($event->points)->toBe(3);
    expect($event->description)->toContain('Score correction');
});

test('creates manual event and edits existing event with full mathematical rebuild', function () {
    $created = $this->service->createManualEvent($this->game, [
        'team_side' => 'home',
        'jersey_number' => '23',
        'action_code' => 'X',
        'period' => 1,
        'points' => 2,
        'description' => 'Manual 2pt Make',
    ]);
    expect($created)->not->toBeNull();

    $this->game->refresh();
    expect($this->game->home_score)->toBe(2);

    // Now edit the event to be a 3pt make (M, points: 3)
    $updated = $this->service->updateEvent($this->game, $created->id, [
        'team_side' => 'home',
        'jersey_number' => '23',
        'action_code' => 'M',
        'points' => 3,
        'period' => 1,
        'description' => 'Upgraded to 3pt Make after review',
    ]);
    expect($updated)->toBeInstanceOf(GameEvent::class);

    $this->game->refresh();
    expect($this->game->home_score)->toBe(3);

    $stat23 = BasketballStat::where('game_id', $this->game->id)->where('jersey_number', '23')->first();
    expect($stat23->points)->toBe(3);
    expect($stat23->fg3m)->toBe(1);
    expect($stat23->fgm)->toBe(1);
    expect($stat23->fga)->toBe(1);
});

test('records 2pt and 3pt shots with assists and undoes them accurately', function () {
    GameLineup::create([
        'game_id' => $this->game->id,
        'team_side' => 'home',
        'jersey_number' => '10',
        'player_name' => 'Steve Kerr',
        'is_on_court' => true,
    ]);

    // Record 2pt make by #23 with assist by #10
    $res = $this->service->recordStat($this->game, 'home', '23', 'X', null, 400, '10');
    expect($res['success'])->toBeTrue();
    expect($res['message'])->toContain('Ast: #10');

    $this->game->refresh();
    expect($this->game->home_score)->toBe(2);

    $event = GameEvent::where('game_id', $this->game->id)->where('action_code', 'X')->first();
    expect($event->assist_jersey_number)->toBe('10');
    expect($event->description)->toContain('Ast: #10 Steve Kerr');

    $stat23 = BasketballStat::where('game_id', $this->game->id)->where('jersey_number', '23')->first();
    expect($stat23->points)->toBe(2);
    expect($stat23->fgm)->toBe(1);

    $stat10 = BasketballStat::where('game_id', $this->game->id)->where('jersey_number', '10')->first();
    expect($stat10->ast)->toBe(1);

    // Keyboard entry with assist: 23-M:10
    $res2 = $this->service->processKeyboardInput($this->game, '23-M:10');
    expect($res2['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->home_score)->toBe(5);

    $stat10->refresh();
    expect($stat10->ast)->toBe(2);

    // Undo the 3pt make with assist
    $undone = $this->service->undoLastEvent($this->game);
    expect($undone)->not->toBeNull();

    $this->game->refresh();
    expect($this->game->home_score)->toBe(2);

    $stat10->refresh();
    expect($stat10->ast)->toBe(1);
});

test('audits fouls system: personal, offensive, and flagrant count as team fouls while technical does not', function () {
    // 1. Personal foul on jersey 23 ('F')
    $res1 = $this->service->processKeyboardInput($this->game, '23-F');
    expect($res1['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->home_fouls_current_period)->toBe(1);

    $stat23 = BasketballStat::where('game_id', $this->game->id)->where('jersey_number', '23')->first();
    expect($stat23->fouls_pers)->toBe(1);
    expect($stat23->total_fouls)->toBe(1);

    // 2. Offensive foul on jersey 23 ('R')
    $res2 = $this->service->processKeyboardInput($this->game, '23-R');
    expect($res2['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->home_fouls_current_period)->toBe(2);

    $stat23->refresh();
    expect($stat23->fouls_off)->toBe(1);
    expect($stat23->total_fouls)->toBe(2);

    // 3. Technical foul on jersey 23 ('T') -> counts towards player, NOT towards team fouls
    $res3 = $this->service->processKeyboardInput($this->game, '23-T');
    expect($res3['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->home_fouls_current_period)->toBe(2); // Still 2!

    $stat23->refresh();
    expect($stat23->fouls_tech)->toBe(1);
    expect($stat23->total_fouls)->toBe(3);

    // 4. Flagrant foul on jersey 23 ('G') -> counts towards player AND team fouls
    $res4 = $this->service->processKeyboardInput($this->game, '23-G');
    expect($res4['success'])->toBeTrue();

    $this->game->refresh();
    expect($this->game->home_fouls_current_period)->toBe(3); // Increments to 3!

    $stat23->refresh();
    expect($stat23->fouls_flagrant)->toBe(1);
    expect($stat23->total_fouls)->toBe(4);

    // Rebuild game from events should maintain identical counts
    $this->service->rebuildGameFromEvents($this->game);

    $this->game->refresh();
    expect($this->game->home_fouls_current_period)->toBe(3);

    $stat23->refresh();
    expect($stat23->fouls_pers)->toBe(1);
    expect($stat23->fouls_off)->toBe(1);
    expect($stat23->fouls_tech)->toBe(1);
    expect($stat23->fouls_flagrant)->toBe(1);
    expect($stat23->total_fouls)->toBe(4);
});

