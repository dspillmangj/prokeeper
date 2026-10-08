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
        'sequence',
        'period',
        'clock_seconds_remaining',
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
        'is_undone',
        'description',
        'metadata',
    ];

    protected $casts = [
        'is_undone' => 'boolean',
        'metadata' => 'array',
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
