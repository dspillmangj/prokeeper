<?php

namespace App\Http\Controllers;

use App\Models\Game;
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

        $teams = Team::where('organization_id', $orgId)
            ->withCount(['players', 'homeGames'])
            ->get();

        return view('dashboard.index', compact('activeGames', 'recentGames', 'scheduledGames', 'teams'));
    }
}
