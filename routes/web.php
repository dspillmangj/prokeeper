<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\TeamController;
use App\Livewire\NcaaScorebook;
use App\Livewire\PureScoreboard;
use App\Livewire\WatchGame;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PWA App Installation & Manifest Routes
|--------------------------------------------------------------------------
*/
Route::get('/install', [InstallController::class, 'index'])->name('install');
Route::get('/manifest.json', [InstallController::class, 'manifest'])->name('manifest');


/*
|--------------------------------------------------------------------------
| Public Live Spectator & Scoreboard Routes (Accessible via Code / Prompt)
|--------------------------------------------------------------------------
*/
// 1. Pure Stadium Scoreboard (scores, team fouls, period, timeouts, possession arrow)
Route::get('/scoreboard', PureScoreboard::class)->name('public.scoreboard.prompt');
Route::get('/scoreboard/{code}', PureScoreboard::class)->name('public.scoreboard');

// 2. Watch Game Fan Experience (tabs: scoreboard, play-by-play, box score, match summary)
Route::get('/watch', WatchGame::class)->name('public.watch.prompt');
Route::get('/watch/{code}', WatchGame::class)->name('public.watch');

// Spectator live aliases
Route::get('/live', WatchGame::class)->name('public.live.prompt');
Route::get('/live/{code}', WatchGame::class)->name('public.live');

// 3. Official Digital Scorebook & PDF Export
Route::get('/scorebook', NcaaScorebook::class)->name('public.scorebook.prompt');
Route::get('/scorebook/{code}', NcaaScorebook::class)->name('public.scorebook');
Route::get('/export/scorebook/{code}.pdf', [GameController::class, 'exportNcaaPdf'])->name('games.pdf');
Route::get('/export/ncaa/{code}.pdf', [GameController::class, 'exportNcaaPdf'])->name('games.pdf.legacy');

// Operator route redirect (redirects to login)
Route::get('/operator', function () {
    return redirect()->route('login');
})->name('operator.redirect');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Dashboard & Game Operation
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Teams & Rosters
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{id}', [TeamController::class, 'show'])->name('teams.show');
    Route::post('/teams/{id}/players', [TeamController::class, 'addPlayer'])->name('teams.players.add');
    Route::post('/teams/{id}/roster/batch', [TeamController::class, 'batchUpdateRoster'])->name('teams.roster.batch');
    Route::delete('/teams/{id}/players/{playerId}', [TeamController::class, 'removePlayer'])->name('teams.players.remove');

    // Games & Operator
    Route::get('/games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('/games', [GameController::class, 'store'])->name('games.store');
    Route::get('/operator/{uuid}', [GameController::class, 'operator'])->name('games.operator');
});
