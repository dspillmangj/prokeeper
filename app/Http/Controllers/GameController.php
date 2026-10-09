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
            'home_team_score_color' => ['nullable', 'string', 'max:20'],
            'away_team_id' => ['nullable', 'exists:teams,id'],
            'away_team_name' => ['nullable', 'string', 'max:255'],
            'away_team_score_color' => ['nullable', 'string', 'max:20'],
            'venue' => ['nullable', 'string', 'max:255'],
            'event_name' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'preset' => ['nullable', 'string', 'max:50'],
            'timeouts_full' => ['nullable', 'integer', 'min:0', 'max:10'],
            'timeouts_30s' => ['nullable', 'integer', 'min:0', 'max:10'],
            'timeouts_ot' => ['nullable', 'integer', 'min:0', 'max:5'],
            'period_format' => ['nullable', 'string', 'in:quarters,halves,sets'],
            'period_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'ot_minutes' => ['nullable', 'integer', 'min:1', 'max:30'],
            'shot_clock_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'bonus_foul_threshold' => ['nullable', 'integer', 'min:1', 'max:20'],
            'double_bonus_foul_threshold' => ['nullable', 'integer', 'min:1', 'max:20'],
            'player_foul_limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'timeouts_per_set' => ['nullable', 'integer', 'min:0', 'max:10'],
            'set_target_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'deciding_set_target_score' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $homeTeam = ! empty($validated['home_team_id']) ? Team::find($validated['home_team_id']) : null;
        $awayTeam = ! empty($validated['away_team_id']) ? Team::find($validated['away_team_id']) : null;

        $homeName = $homeTeam?->name ?? $validated['home_team_name'] ?? 'Home Team';
        $awayName = $awayTeam?->name ?? $validated['away_team_name'] ?? 'Away Team';

        $homeScoreColor = !empty($validated['home_team_score_color']) 
            ? $validated['home_team_score_color'] 
            : ($homeTeam?->home_jersey_color && $homeTeam->home_jersey_color !== '#ffffff' ? $homeTeam->home_jersey_color : '#1e40af');
        
        $awayScoreColor = !empty($validated['away_team_score_color']) 
            ? $validated['away_team_score_color'] 
            : ($awayTeam?->away_jersey_color && $awayTeam->away_jersey_color !== '#ffffff' ? $awayTeam->away_jersey_color : '#b91c1c');

        $sport = $validated['sport'];
        $periodFormat = $validated['period_format'] ?? ($sport === 'basketball' ? 'quarters' : 'sets');
        $periodMinutes = (int) ($validated['period_minutes'] ?? ($sport === 'basketball' ? 8 : 0));
        $initialClock = ($sport === 'basketball') ? ($periodMinutes * 60) : 0;

        $timeoutsFull = isset($validated['timeouts_full']) ? (int) $validated['timeouts_full'] : 3;
        $timeouts30s = isset($validated['timeouts_30s']) ? (int) $validated['timeouts_30s'] : 2;
        $timeoutsOt = isset($validated['timeouts_ot']) ? (int) $validated['timeouts_ot'] : 1;
        $timeoutsPerSet = isset($validated['timeouts_per_set']) ? (int) $validated['timeouts_per_set'] : 2;

        $totalInitialTimeouts = ($sport === 'basketball') ? ($timeoutsFull + $timeouts30s) : $timeoutsPerSet;

        $rules = [
            'preset' => $validated['preset'] ?? 'default',
            'timeouts_full' => $timeoutsFull,
            'timeouts_30s' => $timeouts30s,
            'timeouts_ot' => $timeoutsOt,
            'period_format' => $periodFormat,
            'period_minutes' => $periodMinutes,
            'ot_minutes' => (int) ($validated['ot_minutes'] ?? 4),
            'shot_clock_seconds' => ! empty($validated['shot_clock_seconds']) ? (int) $validated['shot_clock_seconds'] : null,
            'bonus_foul_threshold' => (int) ($validated['bonus_foul_threshold'] ?? ($periodFormat === 'halves' ? 7 : 5)),
            'double_bonus_foul_threshold' => (int) ($validated['double_bonus_foul_threshold'] ?? ($periodFormat === 'halves' ? 10 : 5)),
            'player_foul_limit' => (int) ($validated['player_foul_limit'] ?? 5),
            'timeouts_per_set' => $timeoutsPerSet,
            'set_target_score' => (int) ($validated['set_target_score'] ?? 25),
            'deciding_set_target_score' => (int) ($validated['deciding_set_target_score'] ?? 15),
        ];

        $settings = [
            'event_name' => $validated['event_name'] ?? null,
            'rules' => $rules,
            'period_minutes' => $periodMinutes,
            'timeouts_per_game' => $totalInitialTimeouts,
            'timeouts_per_set' => $timeoutsPerSet,
        ];

        $game = Game::create([
            'access_code' => strtoupper(Str::random(6)),
            'slug' => Str::slug($homeName).'-vs-'.Str::slug($awayName).'-'.strtolower(Str::random(4)),
            'organization_id' => $user->organization_id,
            'created_by_user_id' => $user->id,
            'sport' => $sport,
            'status' => 'in_progress',
            'home_team_id' => $homeTeam?->id,
            'home_team_name' => $homeName,
            'home_team_score_color' => $homeScoreColor,
            'away_team_id' => $awayTeam?->id,
            'away_team_name' => $awayName,
            'away_team_score_color' => $awayScoreColor,
            'current_period' => 1,
            'clock_seconds_remaining' => $initialClock,
            'home_score' => 0,
            'away_score' => 0,
            'home_timeouts_remaining' => $totalInitialTimeouts,
            'away_timeouts_remaining' => $totalInitialTimeouts,
            'venue' => $validated['venue'] ?? null,
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
            'settings' => $settings,
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

        $pdf = Pdf::loadView('pdf.ncaa-scorebook', compact('game', 'homeStats', 'awayStats', 'events'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("ProKeeper-Scorebook-{$game->access_code}.pdf");
    }
}
