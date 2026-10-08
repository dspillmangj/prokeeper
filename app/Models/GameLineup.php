<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameLineup extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'team_side',
        'player_id',
        'jersey_number',
        'player_name',
        'position',
        'is_starter',
        'is_on_court',
        'court_position',
        'is_libero',
        'seconds_played',
        'subbed_in_at_clock',
    ];

    protected $casts = [
        'is_starter' => 'boolean',
        'is_on_court' => 'boolean',
        'is_libero' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function getFormattedPlayingTimeAttribute(): string
    {
        $minutes = floor($this->seconds_played / 60);
        $seconds = $this->seconds_played % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
