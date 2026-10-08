<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolleyballStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'player_id',
        'team_side',
        'jersey_number',
        'player_name',
        'sets_played',
        'kills',
        'attack_errors',
        'attack_attempts',
        'hitting_percentage',
        'assists',
        'service_aces',
        'service_errors',
        'service_attempts',
        'digs',
        'block_solos',
        'block_assists',
        'total_blocks',
        'ball_handling_errors',
        'reception_errors',
        'total_points',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function calculateHittingPercentage(): void
    {
        $attempts = (int) ($this->attack_attempts ?? 0);
        if ($attempts <= 0) {
            $this->hitting_percentage = 0.000;
        } else {
            $kills = (int) ($this->kills ?? 0);
            $errors = (int) ($this->attack_errors ?? 0);
            $this->hitting_percentage = round(($kills - $errors) / $attempts, 3);
        }
    }
}
