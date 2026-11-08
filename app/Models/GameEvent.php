<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'uuid',
        'sequence',
        'period',
        'clock_seconds_remaining',
        'wall_clock_time',
        'team_side',
        'player_id',
        'jersey_number',
        'player_name',
        'assist_player_id',
        'assist_jersey_number',
        'sport',
        'action_code',
        'action_type',
        'action_name',
        'raw_input',
        'points',
        'home_score_after',
        'away_score_after',
        'possession_arrow_after',
        'home_fouls_after',
        'away_fouls_after',
        'home_timeouts_after',
        'away_timeouts_after',
        'is_undone',
        'description',
        'metadata',
        'actor_id',
    ];

    protected $attributes = [
        'player_name' => '',
    ];

    public function setPlayerNameAttribute($value): void
    {
        $this->attributes['player_name'] = $value ?? '';
    }

    protected $casts = [
        'is_undone' => 'boolean',
        'metadata' => 'array',
        'wall_clock_time' => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function assistPlayer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'assist_player_id');
    }

    public function getFormattedClockAttribute(): string
    {
        $minutes = floor($this->clock_seconds_remaining / 60);
        $seconds = $this->clock_seconds_remaining % 60;

        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
