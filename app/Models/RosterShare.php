<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'shared_with_email',
        'shared_with_user_id',
        'permission', // 'view', 'edit'
        'invited_by_user_id',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    public function invitedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }
}

