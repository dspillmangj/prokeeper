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

    public function getPeriodNameAttribute(): string
    {
        if ($this->sport === 'volleyball') {
            return "Set {$this->current_period}";
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
