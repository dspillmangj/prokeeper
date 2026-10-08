<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'player_id',
        'jersey_number',
        'position',
        'is_starter',
        'is_libero',
        'is_captain',
    ];

    protected $casts = [
        'is_starter' => 'boolean',
        'is_libero' => 'boolean',
        'is_captain' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
