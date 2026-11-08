<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'access_code',
        'scorebook_token',
        'stats_token',
        'slug',
        'organization_id',
        'created_by_user_id',
        'sport',
        'status',
        'home_team_id',
        'home_team_name',
        'home_team_score_color',
        'away_team_id',
        'away_team_name',
        'away_team_score_color',
        'current_period',
        'clock_seconds_remaining',
        'clock_running',
        'clock_last_started_at',
        'home_score',
        'away_score',
        'home_period_scores',
        'away_period_scores',
        'home_timeouts_remaining',
        'away_timeouts_remaining',
        'home_fouls_current_period',
        'away_fouls_current_period',
        'possession_arrow',
        'current_server',
        'home_rotation',
        'away_rotation',
        'venue',
        'scheduled_at',
        'settings',
    ];

    protected $casts = [
        'clock_running' => 'boolean',
        'clock_last_started_at' => 'datetime',
        'home_period_scores' => 'array',
        'away_period_scores' => 'array',
        'scheduled_at' => 'datetime',
        'settings' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Game $game) {
            if (empty($game->uuid)) {
                $game->uuid = (string) Str::uuid();
            }
            if (empty($game->access_code)) {
                $game->access_code = strtoupper(Str::random(6));
            }
            if (empty($game->scorebook_token)) {
                $game->scorebook_token = Str::random(32);
            }
            if (empty($game->stats_token)) {
                $game->stats_token = Str::random(32);
            }
            if (empty($game->slug)) {
                $home = Str::slug($game->home_team_name ?: 'home');
                $away = Str::slug($game->away_team_name ?: 'away');
                $game->slug = "{$home}-vs-{$away}-".strtolower(Str::random(4));
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function gameShares(): HasMany
    {
        return $this->hasMany(GameShare::class);
    }

    public function isHostPro(): bool
    {
        if ($this->createdBy && $this->createdBy->isPro()) {
            return true;
        }

        if ($this->organization && $this->organization->isEnterprise()) {
            return true;
        }

        return false;
    }

    public function canBeOperatedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $this->created_by_user_id === $user->id) {
            return true;
        }

        // Same organization members
        if ($this->organization_id && $user->organization_id === $this->organization_id) {
            return true;
        }

        // Shared with edit permission
        return $this->gameShares()
            ->where(function ($q) use ($user) {
                $q->where('shared_with_user_id', $user->id)
                    ->orWhere('shared_with_email', $user->email);
            })
            ->where('permission', 'edit')
            ->exists();
    }

    public function canBeViewedBy(?User $user, ?string $token = null, string $viewType = 'live'): bool
    {
        // 1. If game host is on Free plan: ONLY the authenticated creator or org owner can view
        if (! $this->isHostPro()) {
            if (! $user) {
                return false;
            }

            return $user->id === $this->created_by_user_id ||
                ($this->organization_id && $user->organization_id === $this->organization_id);
        }

        // 2. Pro / Enterprise host:
        // Live View is public to anyone with the game code
        if ($viewType === 'live') {
            return true;
        }

        // Scorebook view: requires matching scorebook_token, or authenticated access
        if ($viewType === 'scorebook') {
            if ($token && hash_equals((string) $this->scorebook_token, (string) $token)) {
                return true;
            }

            if ($user && ($user->id === $this->created_by_user_id || $user->organization_id === $this->organization_id)) {
                return true;
            }

            if ($user && $this->gameShares()->where(function ($q) use ($user) {
                $q->where('shared_with_user_id', $user->id)->orWhere('shared_with_email', $user->email);
            })->exists()) {
                return true;
            }

            return false;
        }

        // Stats view: requires matching stats_token, or authenticated access
        if ($viewType === 'stats') {
            if ($token && hash_equals((string) $this->stats_token, (string) $token)) {
                return true;
            }

            if ($user && ($user->id === $this->created_by_user_id || $user->organization_id === $this->organization_id)) {
                return true;
            }

            if ($user && $this->gameShares()->where(function ($q) use ($user) {
                $q->where('shared_with_user_id', $user->id)->orWhere('shared_with_email', $user->email);
            })->exists()) {
                return true;
            }

            return false;
        }

        return false;
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function lineups(): HasMany
    {
        return $this->hasMany(GameLineup::class);
    }

    public function homeLineup(): HasMany
    {
        return $this->hasMany(GameLineup::class)->where('team_side', 'home');
    }

    public function awayLineup(): HasMany
    {
        return $this->hasMany(GameLineup::class)->where('team_side', 'away');
    }

    public function events(): HasMany
    {
        return $this->hasMany(GameEvent::class)->orderBy('sequence', 'asc');
    }

    public function basketballStats(): HasMany
    {
        return $this->hasMany(BasketballStat::class);
    }

    public function volleyballStats(): HasMany
    {
        return $this->hasMany(VolleyballStat::class);
    }

    public function getHomeDisplayNameAttribute(): string
    {
        return $this->homeTeam?->name ?? $this->home_team_name ?? 'Home Team';
    }

    public function getAwayDisplayNameAttribute(): string
    {
        return $this->awayTeam?->name ?? $this->away_team_name ?? 'Away Team';
    }

    public function getRulesAttribute(): array
    {
        return $this->settings['rules'] ?? [];
    }

    public function getFullTimeoutsAllowedAttribute(): int
    {
        return (int) ($this->rules['timeouts_full'] ?? 3);
    }

    public function getThirtySecondTimeoutsAllowedAttribute(): int
    {
        return (int) ($this->rules['timeouts_30s'] ?? 2);
    }

    public function getOtTimeoutsAllowedAttribute(): int
    {
        return (int) ($this->rules['timeouts_ot'] ?? 1);
    }

    public function getTotalTimeoutsAllowedAttribute(): int
    {
        if ($this->sport === 'volleyball') {
            return (int) ($this->rules['timeouts_per_set'] ?? 2);
        }

        $base = $this->full_timeouts_allowed + $this->thirty_second_timeouts_allowed;
        $regPeriods = ($this->period_format === 'halves') ? 2 : 4;
        $otPeriods = max(0, (int) $this->current_period - $regPeriods);

        return $base + ($otPeriods * $this->ot_timeouts_allowed);
    }

    public function getPeriodFormatAttribute(): string
    {
        if ($this->sport === 'volleyball') {
            return 'sets';
        }

        return $this->rules['period_format'] ?? 'quarters';
    }

    public function getPeriodMinutesAttribute(): int
    {
        if ($this->sport === 'volleyball') {
            return 0;
        }

        return (int) ($this->rules['period_minutes'] ?? 8);
    }

    public function getOtMinutesAttribute(): int
    {
        return (int) ($this->rules['ot_minutes'] ?? 4);
    }

    public function getShotClockSecondsAttribute(): ?int
    {
        if (! empty($this->rules['shot_clock_seconds'])) {
            return (int) $this->rules['shot_clock_seconds'];
        }

        return null;
    }

    public function getBonusThresholdAttribute(): int
    {
        return (int) ($this->rules['bonus_foul_threshold'] ?? ($this->period_format === 'halves' ? 7 : 5));
    }

    public function getDoubleBonusThresholdAttribute(): int
    {
        return (int) ($this->rules['double_bonus_foul_threshold'] ?? ($this->period_format === 'halves' ? 10 : 5));
    }

    public function getPlayerFoulLimitAttribute(): int
    {
        return (int) ($this->rules['player_foul_limit'] ?? 5);
    }

    public function getHomeTimeoutsBreakdownAttribute(): array
    {
        return $this->calculateTimeoutsBreakdown('home');
    }

    public function getAwayTimeoutsBreakdownAttribute(): array
    {
        return $this->calculateTimeoutsBreakdown('away');
    }

    public function getHomeTimeoutsUsedAttribute(): int
    {
        return (int) ($this->home_timeouts_breakdown['used_total'] ?? 0);
    }

    public function getAwayTimeoutsUsedAttribute(): int
    {
        return (int) ($this->away_timeouts_breakdown['used_total'] ?? 0);
    }

    public function getHomeTimeoutsTakenAttribute(): int
    {
        return $this->home_timeouts_used;
    }

    public function getAwayTimeoutsTakenAttribute(): int
    {
        return $this->away_timeouts_used;
    }

    public function calculateTimeoutsBreakdown(string $teamSide): array
    {
        if ($this->sport === 'volleyball') {
            $allowedTotal = (int) ($this->rules['timeouts_per_set'] ?? 2);
            $timeoutEvents = $this->events()
                ->where('team_side', $teamSide)
                ->where('action_code', 'TIMEOUT')
                ->where('period', $this->current_period)
                ->where('is_undone', false)
                ->get();

            $usedTotal = $timeoutEvents->count();
            $remTotal = ($teamSide === 'home') ? (int) $this->home_timeouts_remaining : (int) $this->away_timeouts_remaining;

            return [
                'allowed_full' => $allowedTotal,
                'allowed_30s' => 0,
                'allowed_ot' => 0,
                'allowed_total' => $allowedTotal,
                'ot_periods' => 0,
                'used_full' => $usedTotal,
                'used_30s' => 0,
                'used_total' => $usedTotal,
                'rem_full' => $remTotal,
                'rem_30s' => 0,
                'rem_total' => $remTotal,
                'events' => $timeoutEvents,
            ];
        }

        $allowedFull = $this->full_timeouts_allowed;
        $regPeriods = ($this->period_format === 'halves') ? 2 : 4;
        $otPeriods = max(0, (int) $this->current_period - $regPeriods);
        $otTimeouts = $otPeriods * $this->ot_timeouts_allowed;
        $allowed30s = $this->thirty_second_timeouts_allowed + $otTimeouts;
        $allowedTotal = $allowedFull + $allowed30s;

        $timeoutEvents = $this->events()
            ->where('team_side', $teamSide)
            ->where('action_code', 'TIMEOUT')
            ->where('is_undone', false)
            ->get();

        $usedFull = 0;
        $used30s = 0;

        foreach ($timeoutEvents as $ev) {
            $type = $ev->metadata['timeout_type'] ?? 'full';
            if ($type === '30s') {
                $used30s++;
            } else {
                $usedFull++;
            }
        }

        // If extra timeouts were used beyond allowed due to legacy data
        $remFull = max(0, $allowedFull - $usedFull);
        $rem30s = max(0, $allowed30s - $used30s);
        $remTotal = ($teamSide === 'home') ? (int) $this->home_timeouts_remaining : (int) $this->away_timeouts_remaining;

        return [
            'allowed_full' => $allowedFull,
            'allowed_30s' => $allowed30s,
            'allowed_ot' => $this->ot_timeouts_allowed,
            'allowed_total' => $allowedTotal,
            'ot_periods' => $otPeriods,
            'used_full' => $usedFull,
            'used_30s' => $used30s,
            'used_total' => $usedFull + $used30s,
            'rem_full' => $remFull,
            'rem_30s' => $rem30s,
            'rem_total' => $remTotal,
            'events' => $timeoutEvents,
        ];
    }

    public function isBonus(string $teamSide): bool
    {
        $fouls = ($teamSide === 'home') ? $this->home_fouls_current_period : $this->away_fouls_current_period;

        return $fouls >= $this->bonus_threshold;
    }

    public function isDoubleBonus(string $teamSide): bool
    {
        $fouls = ($teamSide === 'home') ? $this->home_fouls_current_period : $this->away_fouls_current_period;

        return $fouls >= $this->double_bonus_threshold;
    }

    public function getPeriodNameAttribute(): string
    {
        if ($this->sport === 'volleyball') {
            return "Set {$this->current_period}";
        }

        if ($this->period_format === 'halves') {
            return match ($this->current_period) {
                1 => '1st Half',
                2 => '2nd Half',
                3 => 'Overtime',
                4 => '2nd Overtime',
                5 => '3rd Overtime',
                default => 'OT '.($this->current_period - 2),
            };
        }

        return match ($this->current_period) {
            1 => '1st Quarter',
            2 => '2nd Quarter',
            3 => '3rd Quarter',
            4 => '4th Quarter',
            5 => 'Overtime',
            6 => '2nd Overtime',
            default => 'Quarter '.$this->current_period,
        };
    }

    public function getFormattedClockAttribute(): string
    {
        $minutes = floor($this->clock_seconds_remaining / 60);
        $seconds = $this->clock_seconds_remaining % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
