<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GamePresetController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\SecuritySettingsController;
use App\Http\Controllers\TeamController;
use App\Livewire\CoachStats;
use App\Livewire\NcaaScorebook;
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
| The 4 Standardized Views: Operator, Scorebook, Statistics, Live View
|--------------------------------------------------------------------------
| Public entrance is strictly Live View (/live). Scorebook & Stats require
| tokenized links or QR codes generated from the Operator HUD.
| Standalone /scoreboard is eliminated and redirects to /live.
*/

// Legacy scoreboard and watch routes redirect to public live view
Route::get('/scoreboard', fn () => redirect()->route('public.live.prompt'))->name('public.scoreboard.prompt');
Route::get('/scoreboard/{code}', fn ($code) => redirect()->route('public.live', $code))->name('public.scoreboard');
Route::get('/watch', fn () => redirect()->route('public.live.prompt'))->name('public.watch.prompt');
Route::get('/watch/{code}', fn ($code) => redirect()->route('public.live', $code))->name('public.watch');

// 1. Live View (The ONLY publicly accessible entrance via game code)
Route::get('/live', WatchGame::class)->name('public.live.prompt');
Route::get('/live/{code}', WatchGame::class)->name('public.live');

// 2. Statistics View (Requires Operator QR / Tokenized Link)
Route::get('/stats', fn () => redirect()->route('public.live.prompt'))->name('public.stats.prompt');
Route::get('/stats/{code}', CoachStats::class)->name('public.stats');
Route::get('/export/stats/{code}.pdf', [GameController::class, 'exportCoachStatsPdf'])->name('games.stats.pdf');

// 3. Scorebook View (Requires Operator QR / Tokenized Link)
Route::get('/scorebook', fn () => redirect()->route('public.live.prompt'))->name('public.scorebook.prompt');
Route::get('/scorebook/{code}', NcaaScorebook::class)->name('public.scorebook');
Route::get('/export/scorebook/{code}.pdf', [GameController::class, 'exportNcaaPdf'])->name('games.pdf');
Route::get('/export/ncaa/{code}.pdf', [GameController::class, 'exportNcaaPdf'])->name('games.pdf.legacy');

// 4. Operator View redirect (redirects unauthenticated to login)
Route::get('/operator', fn () => redirect()->route('login'))->name('operator.redirect');

/*
|--------------------------------------------------------------------------
| Authentication Routes (2FA, Password Reset, Email Verification)
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 2FA Security Code Verification
Route::get('/auth/2fa', [AuthController::class, 'show2fa'])->name('auth.2fa.show');
Route::post('/auth/2fa', [AuthController::class, 'verify2fa'])->name('auth.2fa.verify');
Route::post('/auth/2fa/resend', [AuthController::class, 'resend2fa'])->name('auth.2fa.resend');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Email Verification Routes
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [AuthController::class, 'showVerifyNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->middleware('throttle:6,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Authenticated Dashboard, Teams, Games, Billing & Security
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Account Security & Passkey Management
    Route::get('/settings/security', [SecuritySettingsController::class, 'show'])->name('settings.security');
    Route::post('/settings/security/two-factor', [SecuritySettingsController::class, 'toggleTwoFactor'])->name('settings.security.two-factor');

    // Subscription & Billing (Stripe Cashier)
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/subscribe/pro', [BillingController::class, 'subscribePro'])->name('billing.subscribe.pro');
    Route::post('/billing/subscribe/enterprise', [BillingController::class, 'subscribeEnterprise'])->name('billing.subscribe.enterprise');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');

    // Teams & Rosters
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{id}', [TeamController::class, 'show'])->name('teams.show');
    Route::delete('/teams/{id}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{id}/players', [TeamController::class, 'addPlayer'])->name('teams.players.add');
    Route::post('/teams/{id}/roster/batch', [TeamController::class, 'batchUpdateRoster'])->name('teams.roster.batch');
    Route::delete('/teams/{id}/roster', [TeamController::class, 'clearRoster'])->name('teams.roster.clear');
    Route::delete('/teams/{id}/players/{playerId}', [TeamController::class, 'removePlayer'])->name('teams.players.remove');

    // Roster Email Sharing
    Route::post('/teams/{id}/share', [TeamController::class, 'shareRoster'])->name('teams.share');
    Route::delete('/teams/{id}/share/{shareId}', [TeamController::class, 'removeRosterShare'])->name('teams.share.remove');

    // Games & Operator
    Route::get('/games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('/games', [GameController::class, 'store'])->name('games.store');
    Route::get('/operator/{uuid}', [GameController::class, 'operator'])->name('games.operator');
    Route::delete('/games/{id}', [GameController::class, 'destroy'])->name('games.destroy');

    // Game Email Sharing
    Route::post('/games/{uuid}/share', [GameController::class, 'shareGame'])->name('games.share');
    Route::delete('/games/{uuid}/share/{shareId}', [GameController::class, 'removeGameShare'])->name('games.share.remove');

    // User Game Settings Presets
    Route::post('/game-presets', [GamePresetController::class, 'store'])->name('game-presets.store');
    Route::delete('/game-presets/{id}', [GamePresetController::class, 'destroy'])->name('game-presets.destroy');
});
