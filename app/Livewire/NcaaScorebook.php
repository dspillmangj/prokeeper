<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use Livewire\Component;

class NcaaScorebook extends Component
{
    public ?string $code = null;
    public string $inputCode = '';
    public string $errorMessage = '';
    public bool $showSignatureModal = false;
    public string $activeSignRole = 'official_scorer';
    public string $signerName = '';
    public string $signerInitials = '';
    public string $signatureMode = 'draw'; // 'draw' or 'type'
    public string $typedFont = 'dancing_script';
    public string $signatureColor = '#0f172a';

    public function mount(?string $code = null)
    {
        $this->code = $code ?: request()->route('code');
        if ($this->code) {
            $this->inputCode = strtoupper(trim($this->code));
        }
    }

    public function submitCode()
    {
        $clean = strtoupper(trim($this->inputCode));
        if (empty($clean)) {
            $this->errorMessage = 'Please enter a game access code.';
            return;
        }

        $game = Game::where('access_code', $clean)
            ->orWhere('uuid', $clean)
            ->orWhere('slug', $clean)
            ->first();

        if (!$game) {
            $this->errorMessage = "No game found with code '{$clean}'.";
            return;
        }

        return redirect()->route('public.scorebook', $game->access_code);
    }

    public function getGameProperty(): ?Game
    {
        if (!$this->code) {
            return null;
        }

        return Game::where('access_code', $this->code)
            ->orWhere('uuid', $this->code)
            ->orWhere('slug', $this->code)
            ->with(['homeTeam', 'awayTeam'])
            ->first();
    }

    public function openSignatureModal(?string $role = 'official_scorer')
    {
        $this->activeSignRole = $role ?: 'official_scorer';
        $game = $this->game;
        $officials = $game->settings['officials'] ?? [];
        $signatures = $game->settings['signatures'] ?? [];
        $currentSig = $signatures[$this->activeSignRole] ?? null;

        $this->signerName = $currentSig['signer_name'] ?? ($officials[$this->activeSignRole] ?? '');
        $this->signerInitials = $currentSig['initials'] ?? $this->deriveInitials($this->signerName);
        $this->signatureMode = $currentSig['type'] ?? 'draw';
        $this->typedFont = $currentSig['font_style'] ?? 'dancing_script';
        $this->showSignatureModal = true;
    }

    public function closeSignatureModal()
    {
        $this->showSignatureModal = false;
    }

    public function switchSignRole(string $role)
    {
        $this->activeSignRole = $role;
        $game = $this->game;
        $officials = $game->settings['officials'] ?? [];
        $signatures = $game->settings['signatures'] ?? [];
        $currentSig = $signatures[$role] ?? null;

        $this->signerName = $currentSig['signer_name'] ?? ($officials[$role] ?? '');
        $this->signerInitials = $currentSig['initials'] ?? $this->deriveInitials($this->signerName);
        if ($currentSig) {
            $this->signatureMode = $currentSig['type'] ?? 'draw';
            $this->typedFont = $currentSig['font_style'] ?? 'dancing_script';
        }
    }

    public function saveSignature(
        string $role,
        string $type,
        string $data,
        ?string $signerName = null,
        ?string $initials = null,
        ?string $fontStyle = null,
        ?string $color = null
    ) {
        $game = $this->game;
        $settings = $game->settings ?? [];
        $signatures = $settings['signatures'] ?? [];
        $officials = $settings['officials'] ?? [];

        $cleanName = trim($signerName ?: $this->signerName);
        $cleanInitials = trim($initials ?: ($this->signerInitials ?: $this->deriveInitials($cleanName)));

        $signatures[$role] = [
            'role' => $role,
            'type' => $type, // 'draw' or 'type'
            'data' => $data, // PNG base64 data URI or SVG string
            'signer_name' => $cleanName,
            'initials' => $cleanInitials,
            'font_style' => $fontStyle ?: $this->typedFont,
            'color' => $color ?: $this->signatureColor,
            'signed_at' => now()->format('m/d/Y g:i A'),
            'signed_timestamp' => now()->timestamp,
        ];

        // Keep official name in sync with game settings
        if (!empty($cleanName)) {
            $officials[$role] = $cleanName;
            $settings['officials'] = $officials;
        }

        $settings['signatures'] = $signatures;
        $game->settings = $settings;
        $game->save();

        $this->showSignatureModal = false;
        $this->dispatch('signature-saved', role: $role, name: $cleanName);
    }

    public function clearSignature(string $role)
    {
        $game = $this->game;
        $settings = $game->settings ?? [];
        $signatures = $settings['signatures'] ?? [];

        if (isset($signatures[$role])) {
            unset($signatures[$role]);
            $settings['signatures'] = $signatures;
            $game->settings = $settings;
            $game->save();
        }

        $this->dispatch('signature-cleared', role: $role);
    }

    protected function deriveInitials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= strtoupper(mb_substr($w, 0, 1));
            }
        }
        return mb_substr($initials, 0, 4);
    }

    public function render()
    {
        $game = $this->game;

        if (!$game) {
            return view('livewire.ncaa-scorebook', [
                'game' => null,
            ])->layout('layouts.public');
        }

        $homeLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->orderBy('is_starter', 'desc')
            ->orderBy('jersey_number', 'asc')
            ->get();

        $awayLineup = GameLineup::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->orderBy('is_starter', 'desc')
            ->orderBy('jersey_number', 'asc')
            ->get();

        $homeStats = BasketballStat::where('game_id', $game->id)
            ->where('team_side', 'home')
            ->get()
            ->keyBy('jersey_number');

        $awayStats = BasketballStat::where('game_id', $game->id)
            ->where('team_side', 'away')
            ->get()
            ->keyBy('jersey_number');

        // All non-undone game events sorted chronologically
        $events = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->orderBy('sequence', 'asc')
            ->get();

        // 1. Build Detailed Half-by-Half and Running Score breakdown for Home
        $homeData = $this->compileTeamNcaaData('home', $homeLineup, $homeStats, $events, $game);

        // 2. Build Detailed Half-by-Half and Running Score breakdown for Away
        $awayData = $this->compileTeamNcaaData('away', $awayLineup, $awayStats, $events, $game);

        return view('livewire.ncaa-scorebook', [
            'game' => $game,
            'home' => $homeData,
            'away' => $awayData,
        ])->layout('layouts.public');
    }

    protected function compileTeamNcaaData(string $teamSide, $lineup, $stats, $events, Game $game): array
    {
        $teamEvents = $events->where('team_side', $teamSide);
        $oppSide = ($teamSide === 'home') ? 'away' : 'home';

        // Half 1 = Period 1 & 2; Half 2 = Period 3 & 4; OT = Period 5+
        $playerBreakdown = [];

        foreach ($lineup as $lp) {
            $j = (string)$lp->jersey_number;
            $pEvents = $teamEvents->where('jersey_number', $j);
            $st = $stats[$j] ?? null;

            $h1_2pt = $pEvents->whereIn('period', [1, 2])->whereIn('action_code', ['2P', '2P_FAST', '2P_SECOND'])->count();
            $h1_3pt = $pEvents->whereIn('period', [1, 2])->where('action_code', '3P')->count();
            $h1_ft_events = $pEvents->whereIn('period', [1, 2])->whereIn('action_code', ['FT_MADE', 'FT_MISSED']);
            $h1_ft_str = $h1_ft_events->map(fn($e) => $e->action_code === 'FT_MADE' ? 'O' : 'X')->implode(' ');
            $h1_pts = $pEvents->whereIn('period', [1, 2])->sum('points');

            $h2_2pt = $pEvents->whereIn('period', [3, 4])->whereIn('action_code', ['2P', '2P_FAST', '2P_SECOND'])->count();
            $h2_3pt = $pEvents->whereIn('period', [3, 4])->where('action_code', '3P')->count();
            $h2_ft_events = $pEvents->whereIn('period', [3, 4])->whereIn('action_code', ['FT_MADE', 'FT_MISSED']);
            $h2_ft_str = $h2_ft_events->map(fn($e) => $e->action_code === 'FT_MADE' ? 'O' : 'X')->implode(' ');
            $h2_pts = $pEvents->whereIn('period', [3, 4])->sum('points');

            $ot_pts = $pEvents->where('period', '>=', 5)->sum('points');

            $fouls = $pEvents->whereIn('action_code', ['F', 'R', 'T'])->values();
            $pfCount = $fouls->where('action_code', '!=', 'T')->count();
            $tfCount = $fouls->where('action_code', 'T')->count();

            $playerBreakdown[] = [
                'lineup_id' => $lp->id,
                'jersey' => $j,
                'name' => $lp->player_name,
                'position' => $lp->position ?: '—',
                'is_starter' => $lp->is_starter,
                'h1_2pt' => $h1_2pt,
                'h1_3pt' => $h1_3pt,
                'h1_ft_str' => $h1_ft_str ?: '—',
                'h1_pts' => $h1_pts,
                'h2_2pt' => $h2_2pt,
                'h2_3pt' => $h2_3pt,
                'h2_ft_str' => $h2_ft_str ?: '—',
                'h2_pts' => $h2_pts,
                'ot_pts' => $ot_pts,
                'total_pts' => $st?->points ?? ($h1_pts + $h2_pts + $ot_pts),
                'pf_count' => $pfCount,
                'tf_count' => $tfCount,
                'fouls_list' => $fouls,
            ];
        }

        // Running score progression for this team: index 1..160
        $runningScore = [];
        $currentPts = 0;
        $scoringEvents = $teamEvents->where('points', '>', 0);

        foreach ($scoringEvents as $sev) {
            $pts = $sev->points;
            for ($p = 1; $p <= $pts; $p++) {
                $currentPts++;
                $runningScore[$currentPts] = [
                    'jersey' => $sev->jersey_number,
                    'period' => $sev->period,
                    'is_scoring_point' => ($p === $pts),
                    'action' => $sev->action_code,
                ];
            }
        }

        // Team Fouls per Half (1st Half: P1 & P2; 2nd Half: P3 & P4)
        $h1_team_fouls = $teamEvents->whereIn('period', [1, 2])->whereIn('action_code', ['F', 'R', 'T'])->count();
        $h2_team_fouls = $teamEvents->whereIn('period', [3, 4])->whereIn('action_code', ['F', 'R', 'T'])->count();
        $ot_team_fouls = $teamEvents->where('period', '>=', 5)->whereIn('action_code', ['F', 'R', 'T'])->count();

        // Team Timeouts
        $timeouts_taken = $teamEvents->whereIn('action_code', ['TIMEOUT', 'TO'])->values();
        $timeouts_breakdown = $game->calculateTimeoutsBreakdown($teamSide);

        // Half scoring sums
        $h1_total_pts = collect($playerBreakdown)->sum('h1_pts');
        $h2_total_pts = collect($playerBreakdown)->sum('h2_pts');
        $ot_total_pts = collect($playerBreakdown)->sum('ot_pts');

        return [
            'side' => $teamSide,
            'name' => ($teamSide === 'home') ? $game->home_display_name : $game->away_display_name,
            'score' => ($teamSide === 'home') ? $game->home_score : $game->away_score,
            'players' => $playerBreakdown,
            'running_score' => $runningScore,
            'h1_team_fouls' => $h1_team_fouls,
            'h2_team_fouls' => $h2_team_fouls,
            'ot_team_fouls' => $ot_team_fouls,
            'h1_total_pts' => $h1_total_pts,
            'h2_total_pts' => $h2_total_pts,
            'ot_total_pts' => $ot_total_pts,
            'timeouts_taken' => $timeouts_taken,
            'timeouts_breakdown' => $timeouts_breakdown,
            'timeouts_remaining' => ($teamSide === 'home') ? $game->home_timeouts_remaining : $game->away_timeouts_remaining,
        ];
    }
}
