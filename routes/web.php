<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\TeamController;
use App\Livewire\NcaaScorebook;
use App\Livewire\PublicScoreboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Live Spectator & Scorebook Routes (Accessible via Code / Slug)
|--------------------------------------------------------------------------
*/
Route::get('/live/{code}', PublicScoreboard::class)->name('public.live');
Route::get('/scorebook/{code}', NcaaScorebook::class)->name('public.scorebook');
Route::get('/export/ncaa/{code}.pdf', [GameController::class, 'exportNcaaPdf'])->name('games.pdf');

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
