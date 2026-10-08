<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\RosterPlayer;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teams = Team::where('organization_id', $user->organization_id)
            ->withCount('rosterPlayers')
            ->get();

        return view('teams.index', compact('teams'));
    }

    public function create()
    {
        return view('teams.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:10'],
            'sport' => ['required', 'in:basketball,volleyball'],
            'gender' => ['required', 'in:boys,girls,coed'],
            'level' => ['required', 'in:varsity,jv,freshman,middle_school,club'],
            'season' => ['required', 'string', 'max:50'],
        ]);

        $team = Team::create([
            'organization_id' => $user->organization_id,
            'name' => $validated['name'],
            'short_name' => $validated['short_name'],
            'sport' => $validated['sport'],
            'gender' => $validated['gender'],
            'level' => $validated['level'],
            'season' => $validated['season'],
        ]);

        return redirect()->route('teams.show', $team->id);
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $team = Team::where('organization_id', $user->organization_id)
            ->with(['rosterPlayers.player'])
            ->findOrFail($id);

        return view('teams.show', compact('team'));
    }

    public function addPlayer(Request $request, int $id)
    {
        $user = Auth::user();
        $team = Team::where('organization_id', $user->organization_id)->findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'jersey_number' => ['required', 'string', 'max:5'],
            'position' => ['nullable', 'string', 'max:10'],
            'is_starter' => ['nullable', 'boolean'],
            'is_libero' => ['nullable', 'boolean'],
        ]);

        $player = Player::create([
            'organization_id' => $user->organization_id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'default_jersey_number' => $validated['jersey_number'],
            'position' => $validated['position'],
        ]);

        RosterPlayer::create([
            'team_id' => $team->id,
            'player_id' => $player->id,
            'jersey_number' => $validated['jersey_number'],
            'position' => $validated['position'],
            'is_starter' => $request->boolean('is_starter'),
            'is_libero' => $request->boolean('is_libero'),
        ]);

        return back()->with('success', "Player #{$validated['jersey_number']} {$player->full_name} added to roster.");
    }

    public function batchUpdateRoster(Request $request, int $id)
    {
        $user = Auth::user();
        $team = Team::where('organization_id', $user->organization_id)->findOrFail($id);

        $validated = $request->validate([
            'players' => ['present', 'array'],
            'players.*.id' => ['nullable'],
            'players.*.player_id' => ['nullable'],
            'players.*.name' => ['nullable', 'string', 'max:255'],
            'players.*.first_name' => ['nullable', 'string', 'max:255'],
            'players.*.last_name' => ['nullable', 'string', 'max:255'],
            'players.*.jersey_number' => ['required', 'string', 'max:5'],
            'players.*.position' => ['nullable', 'string', 'max:10'],
            'players.*.is_starter' => ['nullable'],
            'players.*.is_libero' => ['nullable'],
        ]);

        DB::transaction(function () use ($user, $team, $validated) {
            $submittedRosterPlayerIds = [];

            foreach ($validated['players'] as $row) {
                $jerseyNumber = trim((string)($row['jersey_number'] ?? ''));
                $fullName = trim($row['name'] ?? '');

                if ($fullName === '') {
                    $fn = trim($row['first_name'] ?? '');
                    $ln = trim($row['last_name'] ?? '');
                    $fullName = trim("{$fn} {$ln}");
                }

                if ($jerseyNumber === '' || $fullName === '') {
                    continue;
                }

                $nameParts = preg_split('/\s+/', $fullName, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
                $position = !empty($row['position']) ? strtoupper(trim($row['position'])) : null;
                $isStarter = filter_var($row['is_starter'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $isLibero = filter_var($row['is_libero'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $rosterPlayerId = !empty($row['id']) ? (int)$row['id'] : null;

                if ($rosterPlayerId) {
                    $rosterPlayer = RosterPlayer::where('team_id', $team->id)->where('id', $rosterPlayerId)->first();
                    if ($rosterPlayer) {
                        if ($rosterPlayer->player) {
                            $rosterPlayer->player->update([
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                                'default_jersey_number' => $jerseyNumber,
                                'position' => $position,
                            ]);
                        }
                        $rosterPlayer->update([
                            'jersey_number' => $jerseyNumber,
                            'position' => $position,
                            'is_starter' => $isStarter,
                            'is_libero' => $isLibero,
                        ]);
                        $submittedRosterPlayerIds[] = $rosterPlayer->id;
                        continue;
                    }
                }

                // Check if a player with this jersey exists on the team already
                $existingRp = RosterPlayer::where('team_id', $team->id)->where('jersey_number', $jerseyNumber)->first();
                if ($existingRp) {
                    if ($existingRp->player) {
                        $existingRp->player->update([
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'default_jersey_number' => $jerseyNumber,
                            'position' => $position,
                        ]);
                    }
                    $existingRp->update([
                        'jersey_number' => $jerseyNumber,
                        'position' => $position,
                        'is_starter' => $isStarter,
                        'is_libero' => $isLibero,
                    ]);
                    $submittedRosterPlayerIds[] = $existingRp->id;
                    continue;
                }

                $player = Player::create([
                    'organization_id' => $user->organization_id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'default_jersey_number' => $jerseyNumber,
                    'position' => $position,
                ]);

                $rosterPlayer = RosterPlayer::create([
                    'team_id' => $team->id,
                    'player_id' => $player->id,
                    'jersey_number' => $jerseyNumber,
                    'position' => $position,
                    'is_starter' => $isStarter,
                    'is_libero' => $isLibero,
                ]);

                $submittedRosterPlayerIds[] = $rosterPlayer->id;
            }

            // Remove roster players not present in submitted list
            RosterPlayer::where('team_id', $team->id)
                ->whereNotIn('id', $submittedRosterPlayerIds)
                ->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Roster updated successfully.',
            ]);
        }

        return redirect()->route('teams.show', $team->id)->with('success', 'Roster updated successfully.');
    }

    public function removePlayer(int $teamId, int $rosterPlayerId)
    {
        $user = Auth::user();
        $team = Team::where('organization_id', $user->organization_id)->findOrFail($teamId);
        $rp = RosterPlayer::where('team_id', $team->id)->findOrFail($rosterPlayerId);
        $rp->delete();

        return back()->with('success', 'Player removed from roster.');
    }
}
