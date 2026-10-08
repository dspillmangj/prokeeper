<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameLineup;
use App\Models\Organization;
use App\Models\Player;
use App\Models\RosterPlayer;
use App\Models\Team;
use App\Models\User;
use App\Services\BasketballStatService;
use App\Services\VolleyballStatService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Organization
        $org = Organization::create([
            'name' => 'Lighthouse Christian Academy',
            'slug' => 'lca-athletics',
            'city' => 'Grand Junction',
            'state' => 'CO',
            'primary_color' => '#1e3a8a',
            'secondary_color' => '#f59e0b',
        ]);

        // 2. Create Users
        $admin = User::create([
            'name' => 'Athletic Director',
            'email' => 'admin@prokeeper.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'admin',
        ]);

        $scorekeeper = User::create([
            'name' => 'Lead Scorekeeper',
            'email' => 'scorekeeper@prokeeper.com',
            'password' => Hash::make('password'),
            'organization_id' => $org->id,
            'role' => 'scorekeeper',
        ]);

        // 3. Create Basketball Teams
        $homeBball = Team::create([
            'organization_id' => $org->id,
            'name' => 'LCA Eagles Varsity',
            'short_name' => 'EAGLES',
            'sport' => 'basketball',
            'gender' => 'boys',
            'level' => 'varsity',
            'season' => '2026-2027',
            'home_jersey_color' => '#ffffff',
            'away_jersey_color' => '#1e3a8a',
        ]);

        $awayBball = Team::create([
            'organization_id' => $org->id,
            'name' => 'Heritage Warriors',
            'short_name' => 'WARRIORS',
            'sport' => 'basketball',
            'gender' => 'boys',
            'level' => 'varsity',
            'season' => '2026-2027',
            'home_jersey_color' => '#ffffff',
            'away_jersey_color' => '#991b1b',
        ]);

        // 4. Create Basketball Players for LCA
        $lcaPlayers = [
            ['first' => 'Caleb', 'last' => 'Johnson', 'jersey' => '23', 'pos' => 'SG', 'starter' => true],
            ['first' => 'Marcus', 'last' => 'Rivers', 'jersey' => '11', 'pos' => 'PG', 'starter' => true],
            ['first' => 'Ethan', 'last' => 'Wright', 'jersey' => '33', 'pos' => 'C', 'starter' => true],
            ['first' => 'David', 'last' => 'Spillman', 'jersey' => '05', 'pos' => 'SF', 'starter' => true],
            ['first' => 'Jordan', 'last' => 'Miller', 'jersey' => '14', 'pos' => 'PF', 'starter' => true],
            ['first' => 'Noah', 'last' => 'Bennett', 'jersey' => '02', 'pos' => 'PG', 'starter' => false],
            ['first' => 'Lucas', 'last' => 'Adams', 'jersey' => '21', 'pos' => 'SG', 'starter' => false],
            ['first' => 'Samuel', 'last' => 'Cooper', 'jersey' => '42', 'pos' => 'C', 'starter' => false],
        ];

        foreach ($lcaPlayers as $p) {
            $player = Player::create([
                'organization_id' => $org->id,
                'first_name' => $p['first'],
                'last_name' => $p['last'],
                'default_jersey_number' => $p['jersey'],
                'position' => $p['pos'],
            ]);

            RosterPlayer::create([
                'team_id' => $homeBball->id,
                'player_id' => $player->id,
                'jersey_number' => $p['jersey'],
                'position' => $p['pos'],
                'is_starter' => $p['starter'],
            ]);
        }

        // 5. Create Basketball Players for Heritage Warriors
        $warriorPlayers = [
            ['first' => 'Trevor', 'last' => 'Scott', 'jersey' => '10', 'pos' => 'PG', 'starter' => true],
            ['first' => 'Aiden', 'last' => 'Reed', 'jersey' => '24', 'pos' => 'SG', 'starter' => true],
            ['first' => 'Brandon', 'last' => 'Cole', 'jersey' => '15', 'pos' => 'SF', 'starter' => true],
            ['first' => 'Zachary', 'last' => 'Hayes', 'jersey' => '34', 'pos' => 'PF', 'starter' => true],
            ['first' => 'Tyson', 'last' => 'Ward', 'jersey' => '50', 'pos' => 'C', 'starter' => true],
            ['first' => 'Eli', 'last' => 'Brooks', 'jersey' => '03', 'pos' => 'SG', 'starter' => false],
            ['first' => 'Derek', 'last' => 'Simmons', 'jersey' => '12', 'pos' => 'PG', 'starter' => false],
        ];

        foreach ($warriorPlayers as $p) {
            $player = Player::create([
                'organization_id' => $org->id,
                'first_name' => $p['first'],
                'last_name' => $p['last'],
                'default_jersey_number' => $p['jersey'],
                'position' => $p['pos'],
            ]);

            RosterPlayer::create([
                'team_id' => $awayBball->id,
                'player_id' => $player->id,
                'jersey_number' => $p['jersey'],
                'position' => $p['pos'],
                'is_starter' => $p['starter'],
            ]);
        }

        // 6. Create Live Basketball Game
        $bballGame = Game::create([
            'access_code' => 'EAG101',
            'slug' => 'lca-eagles-vs-heritage-warriors',
            'organization_id' => $org->id,
            'created_by_user_id' => $scorekeeper->id,
            'sport' => 'basketball',
            'status' => 'in_progress',
            'home_team_id' => $homeBball->id,
            'home_team_name' => 'LCA Eagles',
            'home_team_score_color' => '#1e3a8a',
            'away_team_id' => $awayBball->id,
            'away_team_name' => 'Heritage Warriors',
            'away_team_score_color' => '#991b1b',
            'current_period' => 2,
            'clock_seconds_remaining' => 382,
            'clock_running' => false,
            'home_score' => 0,
            'away_score' => 0,
            'home_period_scores' => [18, 0, 0, 0],
            'away_period_scores' => [15, 0, 0, 0],
            'home_timeouts_remaining' => 4,
            'away_timeouts_remaining' => 4,
            'home_fouls_current_period' => 2,
            'away_fouls_current_period' => 3,
            'possession_arrow' => 'home',
            'venue' => 'LCA Main Gymnasium',
            'scheduled_at' => now(),
        ]);

        // Populate Basketball Game Lineups
        foreach ($homeBball->rosterPlayers as $rp) {
            GameLineup::create([
                'game_id' => $bballGame->id,
                'team_side' => 'home',
                'player_id' => $rp->player_id,
                'jersey_number' => $rp->jersey_number,
                'player_name' => $rp->player->full_name,
                'position' => $rp->position,
                'is_starter' => $rp->is_starter,
                'is_on_court' => $rp->is_starter,
                'subbed_in_at_clock' => $rp->is_starter ? 480 : null,
            ]);
        }

        foreach ($awayBball->rosterPlayers as $rp) {
            GameLineup::create([
                'game_id' => $bballGame->id,
                'team_side' => 'away',
                'player_id' => $rp->player_id,
                'jersey_number' => $rp->jersey_number,
                'player_name' => $rp->player->full_name,
                'position' => $rp->position,
                'is_starter' => $rp->is_starter,
                'is_on_court' => $rp->is_starter,
                'subbed_in_at_clock' => $rp->is_starter ? 480 : null,
            ]);
        }

        // Seed some initial plays using BasketballStatService
        $bballService = new BasketballStatService;
        $initialPlays = [
            '23-X', // Home 23 made 2pt
            '10=M', // Away 10 made 3pt
            '33-D', // Home 33 def reb
            '11-A', // Home 11 assist
            '05-M', // Home 05 made 3pt
            '24=Z', // Away 24 missed 2pt
            '14-O', // Home 14 off reb
            '14-B', // Home 14 FT make
            '14-B', // Home 14 FT make
            '50=X', // Away 50 made 2pt
            '23-M', // Home 23 made 3pt
            '15=X', // Away 15 made 2pt
            '11-S', // Home 11 steal
            '11-X', // Home 11 made 2pt
            '34=M', // Away 34 made 3pt
            '05-F', // Home 05 foul
            '10=B', // Away 10 FT make
            '10=B', // Away 10 FT make
            '33-K', // Home 33 block
            '23-X', // Home 23 made 2pt
            '24=M', // Away 24 made 3pt
        ];

        foreach ($initialPlays as $play) {
            $bballService->processKeyboardInput($bballGame, $play, 382);
        }

        // 7. Create Volleyball Teams & Game
        $homeVball = Team::create([
            'organization_id' => $org->id,
            'name' => 'LCA Lady Eagles',
            'short_name' => 'EAGLES',
            'sport' => 'volleyball',
            'gender' => 'girls',
            'level' => 'varsity',
            'season' => '2026-2027',
        ]);

        $awayVball = Team::create([
            'organization_id' => $org->id,
            'name' => 'Grace Knights',
            'short_name' => 'KNIGHTS',
            'sport' => 'volleyball',
            'gender' => 'girls',
            'level' => 'varsity',
            'season' => '2026-2027',
        ]);

        $vbPlayers = [
            ['first' => 'Hannah', 'last' => 'Taylor', 'jersey' => '04', 'pos' => 'OH', 'starter' => true],
            ['first' => 'Grace', 'last' => 'Davis', 'jersey' => '08', 'pos' => 'MB', 'starter' => true],
            ['first' => 'Chloe', 'last' => 'Wilson', 'jersey' => '12', 'pos' => 'S', 'starter' => true],
            ['first' => 'Abigail', 'last' => 'Clark', 'jersey' => '15', 'pos' => 'OPP', 'starter' => true],
            ['first' => 'Emma', 'last' => 'Hall', 'jersey' => '03', 'pos' => 'OH', 'starter' => true],
            ['first' => 'Mia', 'last' => 'Young', 'jersey' => '06', 'pos' => 'L', 'starter' => true],
        ];

        foreach ($vbPlayers as $idx => $p) {
            $pl = Player::create([
                'organization_id' => $org->id,
                'first_name' => $p['first'],
                'last_name' => $p['last'],
                'default_jersey_number' => $p['jersey'],
                'position' => $p['pos'],
            ]);
            RosterPlayer::create([
                'team_id' => $homeVball->id,
                'player_id' => $pl->id,
                'jersey_number' => $p['jersey'],
                'position' => $p['pos'],
                'is_starter' => true,
                'is_libero' => ($p['pos'] === 'L'),
            ]);
        }

        $awayVbPlayers = [
            ['first' => 'Sophia', 'last' => 'Baker', 'jersey' => '07', 'pos' => 'OH', 'starter' => true],
            ['first' => 'Olivia', 'last' => 'Nelson', 'jersey' => '10', 'pos' => 'MB', 'starter' => true],
            ['first' => 'Ava', 'last' => 'Carter', 'jersey' => '14', 'pos' => 'S', 'starter' => true],
            ['first' => 'Isabella', 'last' => 'Mitchell', 'jersey' => '18', 'pos' => 'OPP', 'starter' => true],
            ['first' => 'Harper', 'last' => 'Perez', 'jersey' => '02', 'pos' => 'OH', 'starter' => true],
            ['first' => 'Ella', 'last' => 'Roberts', 'jersey' => '09', 'pos' => 'L', 'starter' => true],
        ];

        foreach ($awayVbPlayers as $idx => $p) {
            $pl = Player::create([
                'organization_id' => $org->id,
                'first_name' => $p['first'],
                'last_name' => $p['last'],
                'default_jersey_number' => $p['jersey'],
                'position' => $p['pos'],
            ]);
            RosterPlayer::create([
                'team_id' => $awayVball->id,
                'player_id' => $pl->id,
                'jersey_number' => $p['jersey'],
                'position' => $p['pos'],
                'is_starter' => true,
                'is_libero' => ($p['pos'] === 'L'),
            ]);
        }

        $vballGame = Game::create([
            'access_code' => 'VB202',
            'slug' => 'lca-lady-eagles-vs-grace-knights',
            'organization_id' => $org->id,
            'created_by_user_id' => $scorekeeper->id,
            'sport' => 'volleyball',
            'status' => 'in_progress',
            'home_team_id' => $homeVball->id,
            'home_team_name' => 'LCA Lady Eagles',
            'away_team_id' => $awayVball->id,
            'away_team_name' => 'Grace Knights',
            'current_period' => 1,
            'clock_seconds_remaining' => 0,
            'home_score' => 0,
            'away_score' => 0,
            'current_server' => 'home',
            'home_rotation' => 1,
            'away_rotation' => 1,
            'venue' => 'LCA Fieldhouse Court 1',
            'scheduled_at' => now(),
        ]);

        foreach ($homeVball->rosterPlayers as $idx => $rp) {
            GameLineup::create([
                'game_id' => $vballGame->id,
                'team_side' => 'home',
                'player_id' => $rp->player_id,
                'jersey_number' => $rp->jersey_number,
                'player_name' => $rp->player->full_name,
                'position' => $rp->position,
                'is_starter' => true,
                'is_on_court' => true,
                'court_position' => $idx + 1,
                'is_libero' => $rp->is_libero,
            ]);
        }

        foreach ($awayVball->rosterPlayers as $idx => $rp) {
            GameLineup::create([
                'game_id' => $vballGame->id,
                'team_side' => 'away',
                'player_id' => $rp->player_id,
                'jersey_number' => $rp->jersey_number,
                'player_name' => $rp->player->full_name,
                'position' => $rp->position,
                'is_starter' => true,
                'is_on_court' => true,
                'court_position' => $idx + 1,
                'is_libero' => $rp->is_libero,
            ]);
        }

        $vbService = new VolleyballStatService;
        $vbPlays = ['04-K', '08-B', '12-A', '07=K', '10=A', '04-K', '08-K'];
        foreach ($vbPlays as $p) {
            $vbService->processKeyboardInput($vballGame, $p);
        }
    }
}
