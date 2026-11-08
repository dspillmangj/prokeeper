<?php

namespace App\Livewire;

use App\Models\BasketballStat;
use App\Models\Game;
use App\Models\GameEvent;
use App\Models\GameLineup;
use App\Models\VolleyballStat;
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

    public ?string $token = null;

    public function mount(?string $code = null)
    {
        $this->code = $code ?: request()->route('code');
        $this->token = request()->query('token');
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

        if (! $game) {
            $this->errorMessage = "No game found with code '{$clean}'.";

            return;
        }

        return redirect()->route('public.scorebook', $game->access_code);
    }

    public function getGameProperty(): ?Game
    {
        if (! $this->code) {
            return null;
        }

        return Game::where('access_code', $this->code)
            ->orWhere('uuid', $this->code)
            ->orWhere('slug', $this->code)
            ->with(['homeTeam', 'awayTeam'])
            ->first();
    }

    public function openSignatureModal(?string $role = null)
    {
        $game = $this->game;
        $defaultRole = ($game && $game->sport === 'volleyball') ? 'official_scorer' : 'official_scorer';
        $this->activeSignRole = $role ?: $defaultRole;

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
        if (! empty($cleanName)) {
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
            if (! empty($w)) {
                $initials .= strtoupper(mb_substr($w, 0, 1));
            }
        }

        return mb_substr($initials, 0, 4);
    }

    public function render()
    {
        $game = $this->game;

        if (! $game) {
            return view('livewire.ncaa-scorebook', [
                'game' => null,
                'isRestricted' => false,
            ])->layout('layouts.public');
        }

        // Check tokenized / authenticated access
        $isAllowed = $game->canBeViewedBy(auth()->user(), $this->token, 'scorebook');
        if (! $isAllowed) {
            return view('livewire.ncaa-scorebook', [
                'game' => $game,
                'isRestricted' => true,
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

        // All non-undone game events sorted chronologically
        $events = GameEvent::where('game_id', $game->id)
            ->where('is_undone', false)
            ->orderBy('sequence', 'asc')
            ->get();

        $isVolleyball = ($game->sport === 'volleyball');

        if ($isVolleyball) {
            $homeStats = VolleyballStat::where('game_id', $game->id)
                ->where('team_side', 'home')
                ->get()
                ->keyBy('jersey_number');

            $awayStats = VolleyballStat::where('game_id', $game->id)
                ->where('team_side', 'away')
                ->get()
                ->keyBy('jersey_number');

            $homeData = $this->compileTeamVolleyballData('home', $homeLineup, $homeStats, $events, $game);
            $awayData = $this->compileTeamVolleyballData('away', $awayLineup, $awayStats, $events, $game);
        } else {
            $homeStats = BasketballStat::where('game_id', $game->id)
                ->where('team_side', 'home')
                ->get()
                ->keyBy('jersey_number');

            $awayStats = BasketballStat::where('game_id', $game->id)
                ->where('team_side', 'away')
                ->get()
                ->keyBy('jersey_number');

            // 1. Build Detailed Quarters/Halves and Running Score breakdown for Home
            $homeData = $this->compileTeamNcaaData('home', $homeLineup, $homeStats, $events, $game);

            // 2. Build Detailed Quarters/Halves and Running Score breakdown for Away
            $awayData = $this->compileTeamNcaaData('away', $awayLineup, $awayStats, $events, $game);
        }

        return view('livewire.ncaa-scorebook', [
            'game' => $game,
            'home' => $homeData,
            'away' => $awayData,
            'isVolleyball' => $isVolleyball,
            'isQuarters' => ($game->period_format === 'quarters'),
        ])->layout('layouts.public');
    }

    protected function compileTeamNcaaData(string $teamSide, $lineup, $stats, $events, Game $game): array
    {
        $teamEvents = $events->where('team_side', $teamSide);
        $oppSide = ($teamSide === 'home') ? 'away' : 'home';
        $isQuarters = ($game->period_format === 'quarters');

        $is2ptMake = fn ($e) => in_array($e->action_code, ['X', '2P', '2P_FAST', '2P_SECOND']) || $e->action_type === '2pt_make';
        $is3ptMake = fn ($e) => in_array($e->action_code, ['M', '3P']) || $e->action_type === '3pt_make';
        $isFtEvent = fn ($e) => in_array($e->action_code, ['B', 'V', 'FT_MADE', 'FT_MISSED']) || in_array($e->action_type, ['ft_make', 'ft_miss']);
        $isFtMake = fn ($e) => in_array($e->action_code, ['B', 'FT_MADE']) || $e->action_type === 'ft_make';
        $isFoulEvent = fn ($e) => in_array($e->action_code, ['F', 'R', 'T', 'G', 'FL', 'FLAG']) || str_starts_with($e->action_type ?? '', 'foul_');

        $playerBreakdown = [];

        foreach ($lineup as $lp) {
            $j = (string) $lp->jersey_number;
            $pEvents = $teamEvents->where('jersey_number', $j);
            $st = $stats[$j] ?? null;

            // Quarter 1 (Period 1)
            $q1_2pt = $pEvents->where('period', 1)->filter($is2ptMake)->count();
            $q1_3pt = $pEvents->where('period', 1)->filter($is3ptMake)->count();
            $q1_ft_events = $pEvents->where('period', 1)->filter($isFtEvent);
            $q1_ft_str = $q1_ft_events->map(fn ($e) => $isFtMake($e) ? 'O' : 'X')->implode(' ');
            $q1_pts = $pEvents->where('period', 1)->sum('points');

            // Quarter 2 (Period 2)
            $q2_2pt = $pEvents->where('period', 2)->filter($is2ptMake)->count();
            $q2_3pt = $pEvents->where('period', 2)->filter($is3ptMake)->count();
            $q2_ft_events = $pEvents->where('period', 2)->filter($isFtEvent);
            $q2_ft_str = $q2_ft_events->map(fn ($e) => $isFtMake($e) ? 'O' : 'X')->implode(' ');
            $q2_pts = $pEvents->where('period', 2)->sum('points');

            // Quarter 3 (Period 3)
            $q3_2pt = $pEvents->where('period', 3)->filter($is2ptMake)->count();
            $q3_3pt = $pEvents->where('period', 3)->filter($is3ptMake)->count();
            $q3_ft_events = $pEvents->where('period', 3)->filter($isFtEvent);
            $q3_ft_str = $q3_ft_events->map(fn ($e) => $isFtMake($e) ? 'O' : 'X')->implode(' ');
            $q3_pts = $pEvents->where('period', 3)->sum('points');

            // Quarter 4 (Period 4)
            $q4_2pt = $pEvents->where('period', 4)->filter($is2ptMake)->count();
            $q4_3pt = $pEvents->where('period', 4)->filter($is3ptMake)->count();
            $q4_ft_events = $pEvents->where('period', 4)->filter($isFtEvent);
            $q4_ft_str = $q4_ft_events->map(fn ($e) => $isFtMake($e) ? 'O' : 'X')->implode(' ');
            $q4_pts = $pEvents->where('period', 4)->sum('points');

            if (! $isQuarters) {
                // Halves mode: Period 1 = 1st Half, Period 2 = 2nd Half, Period >= 3 = Overtime
                $h1_2pt = $q1_2pt;
                $h1_3pt = $q1_3pt;
                $h1_ft_str = $q1_ft_str;
                $h1_pts = $q1_pts;

                $h2_2pt = $q2_2pt;
                $h2_3pt = $q2_3pt;
                $h2_ft_str = $q2_ft_str;
                $h2_pts = $q2_pts;

                $ot_pts = $pEvents->where('period', '>=', 3)->sum('points');
            } else {
                // Quarters mode: OT = Period >= 5
                $h1_2pt = $q1_2pt + $q2_2pt;
                $h1_3pt = $q1_3pt + $q2_3pt;
                $h1_ft_str = trim($q1_ft_str.' '.$q2_ft_str);
                $h1_pts = $q1_pts + $q2_pts;

                $h2_2pt = $q3_2pt + $q4_2pt;
                $h2_3pt = $q3_3pt + $q4_3pt;
                $h2_ft_str = trim($q3_ft_str.' '.$q4_ft_str);
                $h2_pts = $q3_pts + $q4_pts;

                $ot_pts = $pEvents->where('period', '>=', 5)->sum('points');
            }

            $fouls = $pEvents->filter($isFoulEvent)->values();
            $pfCount = $fouls->filter(fn ($e) => $e->action_code !== 'T' && $e->action_type !== 'foul_tech')->count();
            $tfCount = $fouls->filter(fn ($e) => $e->action_code === 'T' || $e->action_type === 'foul_tech')->count();

            // Extended stats
            $fgm = $st?->fgm ?? $pEvents->filter(fn ($e) => $is2ptMake($e) || $is3ptMake($e))->count();
            $fga = $st?->fga ?? ($fgm + $pEvents->filter(fn ($e) => in_array($e->action_code, ['Z', 'N']) || in_array($e->action_type, ['2pt_miss', '3pt_miss']))->count());
            $fg3m = $st?->fg3m ?? $pEvents->filter($is3ptMake)->count();
            $fg3a = $st?->fg3a ?? ($fg3m + $pEvents->filter(fn ($e) => $e->action_code === 'N' || $e->action_type === '3pt_miss')->count());
            $ftm = $st?->ftm ?? $pEvents->filter($isFtMake)->count();
            $fta = $st?->fta ?? ($ftm + $pEvents->filter(fn ($e) => in_array($e->action_code, ['V', 'FT_MISSED']) || $e->action_type === 'ft_miss')->count());
            $reb = $st?->reb ?? $pEvents->filter(fn ($e) => in_array($e->action_code, ['O', 'D']) || in_array($e->action_type, ['off_reb', 'def_reb']))->count();
            $ast = $st?->ast ?? $pEvents->filter(fn ($e) => $e->action_code === 'A' || $e->action_type === 'assist')->count();
            $stl = $st?->stl ?? $pEvents->filter(fn ($e) => $e->action_code === 'S' || $e->action_type === 'steal')->count();
            $blk = $st?->blk ?? $pEvents->filter(fn ($e) => $e->action_code === 'K' || $e->action_type === 'block')->count();
            $to = $st?->turnovers ?? $pEvents->filter(fn ($e) => in_array($e->action_code, ['P', 'U', 'I', 'TO']) || str_starts_with($e->action_type ?? '', 'turnover'))->count();

            $playerBreakdown[] = [
                'lineup_id' => $lp->id,
                'jersey' => $j,
                'name' => $lp->player_name,
                'position' => $lp->position ?: '—',
                'is_starter' => $lp->is_starter,
                // Quarters specific
                'q1_2pt' => $q1_2pt,
                'q1_3pt' => $q1_3pt,
                'q1_ft_str' => $q1_ft_str ?: '—',
                'q1_pts' => $q1_pts,
                'q2_2pt' => $q2_2pt,
                'q2_3pt' => $q2_3pt,
                'q2_ft_str' => $q2_ft_str ?: '—',
                'q2_pts' => $q2_pts,
                'q3_2pt' => $q3_2pt,
                'q3_3pt' => $q3_3pt,
                'q3_ft_str' => $q3_ft_str ?: '—',
                'q3_pts' => $q3_pts,
                'q4_2pt' => $q4_2pt,
                'q4_3pt' => $q4_3pt,
                'q4_ft_str' => $q4_ft_str ?: '—',
                'q4_pts' => $q4_pts,
                // Halves specific
                'h1_2pt' => $h1_2pt,
                'h1_3pt' => $h1_3pt,
                'h1_ft_str' => $h1_ft_str ?: '—',
                'h1_pts' => $h1_pts,
                'h2_2pt' => $h2_2pt,
                'h2_3pt' => $h2_3pt,
                'h2_ft_str' => $h2_ft_str ?: '—',
                'h2_pts' => $h2_pts,
                'ot_pts' => $ot_pts,
                'total_pts' => $st?->points ?? ($isQuarters ? ($q1_pts + $q2_pts + $q3_pts + $q4_pts + $ot_pts) : ($h1_pts + $h2_pts + $ot_pts)),
                'pf_count' => $pfCount,
                'tf_count' => $tfCount,
                'fouls_list' => $fouls,
                'fgm' => $fgm,
                'fga' => $fga,
                'fg_str' => "{$fgm}-{$fga}",
                'fg3m' => $fg3m,
                'fg3a' => $fg3a,
                'fg3_str' => "{$fg3m}-{$fg3a}",
                'ftm' => $ftm,
                'fta' => $fta,
                'ft_str' => "{$ftm}-{$fta}",
                'reb' => $reb,
                'ast' => $ast,
                'stl' => $stl,
                'blk' => $blk,
                'to' => $to,
            ];
        }

        // Running score progression for this team: index 1..160
        $runningScore = [];
        $currentPts = 0;
        $scoringEvents = $teamEvents->where('points', '>', 0);

        foreach ($scoringEvents as $sev) {
            $pts = $sev->points;
            $periodLabel = '';
            if ($isQuarters) {
                $periodLabel = match ((int) $sev->period) {
                    1 => 'Q1',
                    2 => 'Q2',
                    3 => 'Q3',
                    4 => 'Q4',
                    5 => 'OT',
                    default => 'OT'.($sev->period - 4),
                };
            } else {
                $periodLabel = match ((int) $sev->period) {
                    1 => '1H',
                    2 => '2H',
                    3 => 'OT',
                    default => 'OT'.($sev->period - 2),
                };
            }

            for ($p = 1; $p <= $pts; $p++) {
                $currentPts++;
                $runningScore[$currentPts] = [
                    'jersey' => $sev->jersey_number ?? 'TM',
                    'period' => $sev->period,
                    'period_label' => $periodLabel,
                    'is_scoring_point' => ($p === $pts),
                    'action' => $sev->action_code,
                ];
            }
        }

        if ($isQuarters) {
            $q1_team_fouls = $teamEvents->where('period', 1)->filter($isFoulEvent)->count();
            $q2_team_fouls = $teamEvents->where('period', 2)->filter($isFoulEvent)->count();
            $q3_team_fouls = $teamEvents->where('period', 3)->filter($isFoulEvent)->count();
            $q4_team_fouls = $teamEvents->where('period', 4)->filter($isFoulEvent)->count();
            $ot_team_fouls = $teamEvents->where('period', '>=', 5)->filter($isFoulEvent)->count();

            $h1_team_fouls = $q1_team_fouls + $q2_team_fouls;
            $h2_team_fouls = $q3_team_fouls + $q4_team_fouls;

            $q1_total_pts = collect($playerBreakdown)->sum('q1_pts');
            $q2_total_pts = collect($playerBreakdown)->sum('q2_pts');
            $q3_total_pts = collect($playerBreakdown)->sum('q3_pts');
            $q4_total_pts = collect($playerBreakdown)->sum('q4_pts');
            $h1_total_pts = $q1_total_pts + $q2_total_pts;
            $h2_total_pts = $q3_total_pts + $q4_total_pts;
            $ot_total_pts = collect($playerBreakdown)->sum('ot_pts');
        } else {
            $h1_team_fouls = $teamEvents->where('period', 1)->filter($isFoulEvent)->count();
            $h2_team_fouls = $teamEvents->where('period', 2)->filter($isFoulEvent)->count();
            $ot_team_fouls = $teamEvents->where('period', '>=', 3)->filter($isFoulEvent)->count();

            $q1_team_fouls = $h1_team_fouls;
            $q2_team_fouls = $h2_team_fouls;
            $q3_team_fouls = 0;
            $q4_team_fouls = 0;

            $h1_total_pts = collect($playerBreakdown)->sum('h1_pts');
            $h2_total_pts = collect($playerBreakdown)->sum('h2_pts');
            $ot_total_pts = collect($playerBreakdown)->sum('ot_pts');
            $q1_total_pts = $h1_total_pts;
            $q2_total_pts = $h2_total_pts;
            $q3_total_pts = 0;
            $q4_total_pts = 0;
        }

        // Team Timeouts
        $timeouts_taken = $teamEvents->whereIn('action_code', ['TIMEOUT', 'TO'])->values();
        $timeouts_breakdown = $game->calculateTimeoutsBreakdown($teamSide);

        return [
            'side' => $teamSide,
            'name' => ($teamSide === 'home') ? $game->home_display_name : $game->away_display_name,
            'score' => ($teamSide === 'home') ? $game->home_score : $game->away_score,
            'players' => $playerBreakdown,
            'running_score' => $runningScore,
            'period_format' => $game->period_format,
            'is_quarters' => $isQuarters,
            'q1_team_fouls' => $q1_team_fouls,
            'q2_team_fouls' => $q2_team_fouls,
            'q3_team_fouls' => $q3_team_fouls,
            'q4_team_fouls' => $q4_team_fouls,
            'h1_team_fouls' => $h1_team_fouls,
            'h2_team_fouls' => $h2_team_fouls,
            'ot_team_fouls' => $ot_team_fouls,
            'q1_total_pts' => $q1_total_pts,
            'q2_total_pts' => $q2_total_pts,
            'q3_total_pts' => $q3_total_pts,
            'q4_total_pts' => $q4_total_pts,
            'h1_total_pts' => $h1_total_pts,
            'h2_total_pts' => $h2_total_pts,
            'ot_total_pts' => $ot_total_pts,
            'timeouts_taken' => $timeouts_taken,
            'timeouts_breakdown' => $timeouts_breakdown,
            'timeouts_remaining' => ($teamSide === 'home') ? $game->home_timeouts_remaining : $game->away_timeouts_remaining,
        ];
    }

    protected function compileTeamVolleyballData(string $teamSide, $lineup, $stats, $events, Game $game): array
    {
        $teamEvents = $events->where('team_side', $teamSide);
        $oppSide = ($teamSide === 'home') ? 'away' : 'home';
        $maxSet = max(3, (int) $game->current_period, count($game->home_period_scores ?? []));

        $playerBreakdown = [];

        foreach ($lineup as $lp) {
            $j = (string) $lp->jersey_number;
            $pEvents = $teamEvents->where('jersey_number', $j);
            $st = $stats[$j] ?? null;

            $kills = (int) ($st?->kills ?? $pEvents->filter(fn ($e) => $e->action_code === 'K' || $e->action_type === 'kill')->count());
            $attackErrors = (int) ($st?->attack_errors ?? $pEvents->filter(fn ($e) => $e->action_code === 'E' || $e->action_type === 'attack_error')->count());
            $attackAttempts = (int) ($st?->attack_attempts ?? $pEvents->filter(fn ($e) => in_array($e->action_code, ['K', 'E', 'T']) || in_array($e->action_type, ['kill', 'attack_error', 'attack_attempt']))->count());
            $hittingPct = (float) ($st?->hitting_percentage ?? ($attackAttempts > 0 ? round(($kills - $attackErrors) / $attackAttempts, 3) : 0.000));
            $assists = (int) ($st?->assists ?? $pEvents->filter(fn ($e) => $e->action_code === 'Z' || $e->action_type === 'set_assist')->count());
            $serviceAces = (int) ($st?->service_aces ?? $pEvents->filter(fn ($e) => $e->action_code === 'A' || $e->action_type === 'service_ace')->count());
            $serviceErrors = (int) ($st?->service_errors ?? $pEvents->filter(fn ($e) => $e->action_code === 'S' || $e->action_type === 'service_error')->count());
            $digs = (int) ($st?->digs ?? $pEvents->filter(fn ($e) => $e->action_code === 'D' || $e->action_type === 'dig')->count());
            $blockSolos = (int) ($st?->block_solos ?? $pEvents->filter(fn ($e) => $e->action_code === 'B' || $e->action_type === 'block_solo')->count());
            $blockAssists = (int) ($st?->block_assists ?? $pEvents->filter(fn ($e) => $e->action_code === 'C' || $e->action_type === 'block_assist')->count());
            $totalBlocks = (float) ($st?->total_blocks ?? ($blockSolos + ($blockAssists * 0.5)));
            $bhe = (int) ($st?->ball_handling_errors ?? $pEvents->filter(fn ($e) => $e->action_code === 'H' || $e->action_type === 'ball_handling_error')->count());
            $re = (int) ($st?->reception_errors ?? $pEvents->filter(fn ($e) => $e->action_code === 'R' || $e->action_type === 'reception_error')->count());
            $totalPoints = (float) ($st?->total_points ?? ($kills + $serviceAces + $blockSolos + ($blockAssists * 0.5)));
            $sp = (int) ($st?->sets_played ?? max(1, $pEvents->pluck('period')->unique()->count()));

            // Set-by-set kills and points
            $setStats = [];
            for ($s = 1; $s <= 5; $s++) {
                $sEvents = $pEvents->where('period', $s);
                $setStats[$s] = [
                    'kills' => $sEvents->filter(fn ($e) => $e->action_code === 'K' || $e->action_type === 'kill')->count(),
                    'aces' => $sEvents->filter(fn ($e) => $e->action_code === 'A' || $e->action_type === 'service_ace')->count(),
                    'blocks' => $sEvents->filter(fn ($e) => $e->action_code === 'B')->count() + ($sEvents->filter(fn ($e) => $e->action_code === 'C')->count() * 0.5),
                    'points' => $sEvents->sum('points'),
                ];
            }

            $playerBreakdown[] = [
                'lineup_id' => $lp->id,
                'jersey' => $j,
                'name' => $lp->player_name,
                'position' => $lp->position ?: '—',
                'is_starter' => $lp->is_starter,
                'sp' => $sp,
                'kills' => $kills,
                'attack_errors' => $attackErrors,
                'attack_attempts' => $attackAttempts,
                'hitting_pct' => $hittingPct,
                'hitting_pct_str' => sprintf('%+.3f', $hittingPct),
                'assists' => $assists,
                'service_aces' => $serviceAces,
                'service_errors' => $serviceErrors,
                'digs' => $digs,
                'block_solos' => $blockSolos,
                'block_assists' => $blockAssists,
                'total_blocks' => $totalBlocks,
                'bhe' => $bhe,
                're' => $re,
                'total_points' => $totalPoints,
                'set_stats' => $setStats,
            ];
        }

        // Running scores per set (Set 1 to Set 5)
        $setRunningScores = [];
        for ($s = 1; $s <= $maxSet; $s++) {
            $sEvents = $teamEvents->where('period', $s)->where('points', '>', 0);
            $running = [];
            $curPt = 0;
            foreach ($sEvents as $ev) {
                $pts = $ev->points;
                for ($p = 1; $p <= $pts; $p++) {
                    $curPt++;
                    $running[$curPt] = [
                        'jersey' => $ev->jersey_number ?? 'TM',
                        'action' => $ev->action_code,
                    ];
                }
            }
            $setRunningScores[$s] = $running;
        }

        // Set scores
        $periodScores = ($teamSide === 'home') ? ($game->home_period_scores ?? []) : ($game->away_period_scores ?? []);
        $oppPeriodScores = ($teamSide === 'home') ? ($game->away_period_scores ?? []) : ($game->home_period_scores ?? []);
        $setsWon = 0;
        for ($s = 0; $s < $maxSet; $s++) {
            $myScore = (int) ($periodScores[$s] ?? 0);
            $oppScore = (int) ($oppPeriodScores[$s] ?? 0);
            $target = ($s === 4) ? 15 : 25;
            if ($myScore >= $target && ($myScore - $oppScore) >= 2) {
                $setsWon++;
            }
        }

        $substitutions = $teamEvents->where('action_code', 'SUB')->values();
        $timeouts = $teamEvents->where('action_code', 'TIMEOUT')->values();

        return [
            'side' => $teamSide,
            'name' => ($teamSide === 'home') ? $game->home_display_name : $game->away_display_name,
            'score' => ($teamSide === 'home') ? $game->home_score : $game->away_score,
            'sets_won' => $setsWon,
            'set_scores' => $periodScores,
            'players' => $playerBreakdown,
            'set_running_scores' => $setRunningScores,
            'substitutions' => $substitutions,
            'timeouts' => $timeouts,
            'max_set' => $maxSet,
        ];
    }
}
