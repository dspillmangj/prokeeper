<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BasketballStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'team_side',
        'jersey_number',
        'player_name',
        'points',
        'fgm',
        'fga',
        'fg3m',
        'fg3a',
        'ftm',
        'fta',
        'oreb',
        'dreb',
        'reb',
        'ast',
        'stl',
        'blk',
        'swat',
        'to_pass',
        'to_fumble',
        'to_violation',
        'turnovers',
        'fouls_pers',
        'fouls_off',
        'fouls_tech',
        'fouls_forced',
        'total_fouls',
        'seconds_played',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function getFgPercentageAttribute(): string
    {
        if ($this->fga === 0) {
            return '.000';
        }

        return number_format($this->fgm / $this->fga, 3);
    }

    public function getFg3PercentageAttribute(): string
    {
        if ($this->fg3a === 0) {
            return '.000';
        }

        return number_format($this->fg3m / $this->fg3a, 3);
    }

    public function getFtPercentageAttribute(): string
    {
        if ($this->fta === 0) {
            return '.000';
        }

        return number_format($this->ftm / $this->fta, 3);
    }

    public function getFormattedPlayingTimeAttribute(): string
    {
        $minutes = floor($this->seconds_played / 60);
        $seconds = $this->seconds_played % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
