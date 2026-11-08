<?php

namespace App\Http\Controllers;

use App\Mail\RosterSharedMail;
use App\Models\Game;
use App\Models\GameLineup;
use App\Models\Player;
use App\Models\RosterPlayer;
use App\Models\RosterShare;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TeamController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Organization teams
        $teams = Team::where('organization_id', $user->organization_id)
            ->withCount('rosterPlayers')
            ->with('rosterShares.sharedWithUser')
            ->get();

        // 2. Shared teams from outside the user's organization
        $sharedRosters = RosterShare::where(function ($q) use ($user) {
            $q->where('shared_with_user_id', $user->id)
                ->orWhere('shared_with_email', $user->email);
        })
            ->with(['team.organization', 'team' => function ($q) {
                $q->withCount('rosterPlayers');
            }])
            ->get();

        return view('teams.index', compact('teams', 'sharedRosters'));
    }

    public function create()
    {
        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('error', 'Please verify your email address before creating a team roster.');
        }

        if (! $user->canCreateRoster()) {
            return redirect()->route('billing.index')->with('error', 'Free plan accounts can save up to 2 team rosters. Upgrade to Pro for unlimited rosters!');
        }

        return view('teams.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice')->with('error', 'Please verify your email address before creating a team roster.');
        }

        if (! $user->canCreateRoster()) {
            return redirect()->route('billing.index')->with('error', 'Free plan accounts can save up to 2 team rosters. Upgrade to Pro for unlimited rosters!');
        }

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
            'short_name' => $validated['short_name'] ?? null,
            'sport' => $validated['sport'],
            'gender' => $validated['gender'],
            'level' => $validated['level'],
            'season' => $validated['season'],
        ]);

        return redirect()->route('teams.show', $team->id)->with('success', 'Team roster created successfully.');
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $team = Team::with(['rosterPlayers.player', 'rosterShares.sharedWithUser'])->findOrFail($id);

        if (! $team->canBeViewedBy($user)) {
            abort(403, 'You do not have permission to view this roster.');
        }

        $canEdit = $team->canBeEditedBy($user);

        return view('teams.show', compact('team', 'canEdit'));
    }

    public function shareRoster(Request $request, int $id)
    {
        $user = Auth::user();
        $team = Team::findOrFail($id);

        if (! $team->canBeEditedBy($user)) {
            abort(403, 'Only team administrators or editors can share rosters.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'permission' => ['required', 'in:view,edit'],
            'notify' => ['nullable', 'boolean'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $targetUser = User::where('email', $validated['email'])->first();

        RosterShare::updateOrCreate(
            [
                'team_id' => $team->id,
                'shared_with_email' => $validated['email'],
            ],
            [
                'shared_with_user_id' => $targetUser?->id,
                'permission' => $validated['permission'],
                'invited_by_user_id' => $user->id,
            ]
        );

        // Notify user via email (like Google Drive, default is true)
        if ($request->boolean('notify', true)) {
            try {
                Mail::to($validated['email'])->send(new RosterSharedMail(
                    $user,
                    $team,
                    $validated['permission'],
                    $validated['message'] ?? null
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', "Roster successfully shared with {$validated['email']}.");
    }

    public function removeRosterShare(int $id, int $shareId)
    {
        $user = Auth::user();
        $team = Team::findOrFail($id);

        if (! $team->canBeEditedBy($user)) {
            abort(403);
        }

        $share = RosterShare::where('team_id', $team->id)->findOrFail($shareId);
        $share->delete();

        return back()->with('success', 'Roster sharing access revoked.');
    }

    public function addPlayer(Request $request, int $id)
    {
        $user = Auth::user();
        $team = Team::findOrFail($id);

        if (! $team->canBeEditedBy($user)) {
            abort(403);
        }

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'jersey_number' => ['required', 'string', 'max:5'],
            'position' => ['nullable', 'string', 'max:10'],
            'is_starter' => ['nullable', 'boolean'],
            'is_libero' => ['nullable', 'boolean'],
        ]);

        $player = Player::create([
            'organization_id' => $team->organization_id,
            'first_name' => $validated['first_name'] ?? '',
            'last_name' => $validated['last_name'] ?? '',
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

        $fullName = trim($player->full_name);
        $displayName = $fullName ? " {$fullName}" : '';

        return back()->with('success', "Player #{$validated['jersey_number']}{$displayName} added to roster.");
    }

    public function batchUpdateRoster(Request $request, int $id)
    {
        $user = Auth::user();
        $team = Team::findOrFail($id);

        if (! $team->canBeEditedBy($user)) {
            abort(403);
        }

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

        DB::transaction(function () use ($team, $validated) {
            $submittedRosterPlayerIds = [];

            foreach ($validated['players'] as $row) {
                $jerseyNumber = trim((string) ($row['jersey_number'] ?? ''));
                $fullName = trim($row['name'] ?? '');

                if ($fullName === '') {
                    $fn = trim($row['first_name'] ?? '');
                    $ln = trim($row['last_name'] ?? '');
                    $fullName = trim("{$fn} {$ln}");
                }

                if ($jerseyNumber === '') {
                    continue;
                }

                if ($fullName !== '') {
                    $nameParts = preg_split('/\s+/', $fullName, 2);
                    $firstName = $nameParts[0] ?? '';
                    $lastName = $nameParts[1] ?? '';
                } else {
                    $firstName = trim((string) ($row['first_name'] ?? ''));
                    $lastName = trim((string) ($row['last_name'] ?? ''));
                }

                $position = trim((string) ($row['position'] ?? ''));
                $isStarter = filter_var($row['is_starter'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $isLibero = filter_var($row['is_libero'] ?? false, FILTER_VALIDATE_BOOLEAN);

                $rosterPlayerId = ! empty($row['id']) ? (int) $row['id'] : null;

                if ($rosterPlayerId) {
                    $rosterPlayer = RosterPlayer::where('team_id', $team->id)->find($rosterPlayerId);

                    if ($rosterPlayer) {
                        $rosterPlayer->update([
                            'jersey_number' => $jerseyNumber,
                            'position' => $position !== '' ? $position : null,
                            'is_starter' => $isStarter,
                            'is_libero' => $isLibero,
                        ]);

                        if ($rosterPlayer->player) {
                            $rosterPlayer->player->update([
                                'first_name' => $firstName,
                                'last_name' => $lastName,
                                'default_jersey_number' => $jerseyNumber,
                                'position' => $position !== '' ? $position : null,
                            ]);
                        }

                        $submittedRosterPlayerIds[] = $rosterPlayer->id;
                        continue;
                    }
                }

                $player = Player::create([
                    'organization_id' => $team->organization_id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'default_jersey_number' => $jerseyNumber,
                    'position' => $position !== '' ? $position : null,
                ]);

                $newRosterPlayer = RosterPlayer::create([
                    'team_id' => $team->id,
                    'player_id' => $player->id,
                    'jersey_number' => $jerseyNumber,
                    'position' => $position !== '' ? $position : null,
                    'is_starter' => $isStarter,
                    'is_libero' => $isLibero,
                ]);

                $submittedRosterPlayerIds[] = $newRosterPlayer->id;
            }

            RosterPlayer::where('team_id', $team->id)
                ->whereNotIn('id', $submittedRosterPlayerIds)
                ->delete();

            // Sync updated roster into active games
            $activeGames = Game::where(function ($q) use ($team) {
                $q->where('home_team_id', $team->id)->orWhere('away_team_id', $team->id);
            })->whereIn('status', ['scheduled', 'warmup', 'in_progress', 'halftime', 'paused'])->get();

            $currentRoster = RosterPlayer::where('team_id', $team->id)->with('player')->get();

            foreach ($activeGames as $g) {
                $side = ($g->home_team_id === $team->id) ? 'home' : 'away';

                foreach ($currentRoster as $rp) {
                    $playerName = $rp->player?->full_name ?? "Player #{$rp->jersey_number}";
                    $existingLineup = GameLineup::where('game_id', $g->id)
                        ->where('team_side', $side)
                        ->where('player_id', $rp->player_id)
                        ->first();

                    if ($existingLineup) {
                        $existingLineup->update([
                            'jersey_number' => (string) $rp->jersey_number,
                            'player_name' => $playerName,
                            'position' => $rp->position,
                            'is_starter' => (bool) $rp->is_starter,
                            'is_libero' => (bool) $rp->is_libero,
                        ]);
                    } else {
                        GameLineup::create([
                            'game_id' => $g->id,
                            'team_side' => $side,
                            'player_id' => $rp->player_id,
                            'jersey_number' => (string) $rp->jersey_number,
                            'player_name' => $playerName,
                            'position' => $rp->position,
                            'is_starter' => (bool) $rp->is_starter,
                            'is_libero' => (bool) $rp->is_libero,
                            'is_on_court' => false,
                        ]);
                    }
                }
            }
        });

        if ($request->wantsJson()) {
            $team->refresh();
            $team->load(['rosterPlayers.player']);

            return response()->json([
                'success' => true,
                'message' => 'Roster updated successfully.',
                'players' => $team->rosterPlayers->map(function ($rp) {
                    return [
                        'id' => $rp->id,
                        'player_id' => $rp->player_id,
                        'jersey_number' => (string) $rp->jersey_number,
                        'name' => trim(($rp->player->first_name ?? '').' '.($rp->player->last_name ?? '')),
                        'position' => (string) ($rp->position ?? ''),
                        'is_starter' => (bool) $rp->is_starter,
                        'is_libero' => (bool) $rp->is_libero,
                    ];
                })->values(),
            ]);
        }

        return redirect()->route('teams.show', $team->id)->with('success', 'Roster updated successfully.');
    }

    public function clearRoster(int $id)
    {
        $user = Auth::user();
        $team = Team::findOrFail($id);

        if (! $team->canBeEditedBy($user)) {
            abort(403);
        }

        $count = $team->rosterPlayers()->count();
        $team->rosterPlayers()->delete();

        return redirect()->route('teams.show', $team->id)->with('success', "Cleared {$count} player(s) from {$team->name} roster.");
    }

    public function removePlayer(int $teamId, int $rosterPlayerId)
    {
        $user = Auth::user();
        $team = Team::findOrFail($teamId);

        if (! $team->canBeEditedBy($user)) {
            abort(403);
        }

        $rp = RosterPlayer::where('team_id', $team->id)->findOrFail($rosterPlayerId);
        $rp->delete();

        return back()->with('success', 'Player removed from roster.');
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $team = Team::where('organization_id', $user->organization_id)->findOrFail($id);
        $teamName = $team->name;

        if (! $team->canBeEditedBy($user)) {
            abort(403);
        }

        $activeGamesCount = Game::where('home_team_id', $team->id)
            ->orWhere('away_team_id', $team->id)
            ->whereIn('status', ['in_progress', 'halftime', 'warmup'])
            ->count();

        if ($activeGamesCount > 0) {
            return back()->with('error', 'Cannot delete team while active games are in progress.');
        }

        DB::transaction(function () use ($team) {
            $team->rosterPlayers()->delete();
            $team->delete();
        });

        return redirect()->route('teams.index')->with('success', "Team '{$teamName}' deleted successfully.");
    }
}
