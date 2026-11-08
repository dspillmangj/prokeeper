<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameShare;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $orgId = $user->organization_id;

        $activeGames = Game::where('organization_id', $orgId)
            ->whereIn('status', ['in_progress', 'paused'])
            ->with(['homeTeam', 'awayTeam'])
            ->latest()
            ->get();

        $recentGames = Game::where('organization_id', $orgId)
            ->where('status', 'final')
            ->with(['homeTeam', 'awayTeam'])
            ->latest()
            ->take(6)
            ->get();

        $scheduledGames = Game::where('organization_id', $orgId)
            ->where('status', 'scheduled')
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at', 'asc')
            ->take(6)
            ->get();

        $sharedGames = GameShare::where(function ($q) use ($user) {
            $q->where('shared_with_user_id', $user->id)
                ->orWhere('shared_with_email', $user->email);
        })
            ->with(['game.homeTeam', 'game.awayTeam', 'game.organization'])
            ->get();

        $teams = Team::where('organization_id', $orgId)
            ->withCount(['players', 'homeGames'])
            ->get();

        return view('dashboard.index', compact('activeGames', 'recentGames', 'scheduledGames', 'sharedGames', 'teams'));
    }
}
