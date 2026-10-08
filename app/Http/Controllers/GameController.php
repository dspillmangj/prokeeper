<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameLineup;
use App\Models\Team;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GameController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $teams = Team::where('organization_id', $user->organization_id)->get();

        return view('games.create', compact('teams'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'sport' => ['required', 'in:basketball,volleyball'],
            'home_team_id' => ['nullable', 'exists:teams,id'],
            'home_team_name' => ['nullable', 'string', 'max:255'],
            'away_team_id' => ['nullable', 'exists:teams,id'],
            'away_team_name' => ['nullable', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        $homeTeam = ! empty($validated['home_team_id']) ? Team::find($validated['home_team_id']) : null;
        $awayTeam = ! empty($validated['away_team_id']) ? Team::find($validated['away_team_id']) : null;

        $homeName = $homeTeam?->name ?? $validated['home_team_name'] ?? 'Home Team';
        $awayName = $awayTeam?->name ?? $validated['away_team_name'] ?? 'Away Team';

        $game = Game::create([
            'access_code' => strtoupper(Str::random(6)),
            'slug' => Str::slug($homeName).'-vs-'.Str::slug($awayName).'-'.strtolower(Str::random(4)),
            'organization_id' => $user->organization_id,
            'created_by_user_id' => $user->id,
            'sport' => $validated['sport'],
            'status' => 'in_progress',
            'home_team_id' => $homeTeam?->id,
            'home_team_name' => $homeName,
            'away_team_id' => $awayTeam?->id,
            'away_team_name' => $awayName,
            'current_period' => 1,
            'clock_seconds_remaining' => ($validated['sport'] === 'basketball' ? 480 : 0),
            'home_score' => 0,
            'away_score' => 0,
            'venue' => $validated['venue'],
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
        ]);

        // Copy roster into game lineups if team exists
        if ($homeTeam) {
            foreach ($homeTeam->rosterPlayers()->with('player')->get() as $idx => $rp) {
                GameLineup::create([
                    'game_id' => $game->id,
                    'team_side' => 'home',
                    'player_id' => $rp->player_id,
                    'jersey_number' => $rp->jersey_number,
                    'player_name' => $rp->player?->full_name ?? "Player #{$rp->jersey_number}",
                    'position' => $rp->position,
                    'is_starter' => $rp->is_starter,
                    'is_on_court' => ($validated['sport'] === 'basketball' ? ($idx < 5) : ($idx < 6)),
                    'court_position' => $idx + 1,
                    'is_libero' => $rp->is_libero,
                    'subbed_in_at_clock' => ($idx < 5 ? 480 : null),
                ]);
            }
        }

        if ($awayTeam) {
            foreach ($awayTeam->rosterPlayers()->with('player')->get() as $idx => $rp) {
                GameLineup::create([
                    'game_id' => $game->id,
                    'team_side' => 'away',
                    'player_id' => $rp->player_id,
                    'jersey_number' => $rp->jersey_number,
                    'player_name' => $rp->player?->full_name ?? "Player #{$rp->jersey_number}",
                    'position' => $rp->position,
                    'is_starter' => $rp->is_starter,
                    'is_on_court' => ($validated['sport'] === 'basketball' ? ($idx < 5) : ($idx < 6)),
                    'court_position' => $idx + 1,
                    'is_libero' => $rp->is_libero,
                    'subbed_in_at_clock' => ($idx < 5 ? 480 : null),
                ]);
            }
        }

        return redirect()->route('games.operator', $game->uuid);
    }

    public function operator(string $uuid)
    {
        $game = Game::where('uuid', $uuid)->firstOrFail();
        if ($game->sport === 'volleyball') {
            return view('games.operator-volleyball', compact('game'));
        }

        return view('games.operator-basketball', compact('game'));
    }

    public function exportNcaaPdf(string $code)
    {
        $game = Game::where('access_code', $code)->orWhere('uuid', $code)->firstOrFail();
        $homeStats = $game->basketballStats()->where('team_side', 'home')->get();
        $awayStats = $game->basketballStats()->where('team_side', 'away')->get();
        $events = $game->events()->where('is_undone', false)->get();

        $pdf = Pdf::loadView('pdf.ncaa-scorebook', compact('game', 'homeStats', 'awayStats', 'events'));

        return $pdf->download("NCAA-Scorebook-{$game->access_code}.pdf");
    }
}
