<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable, PasskeyAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'organization_id',
        'role',
        'two_factor_email_enabled',
        'two_factor_code',
        'two_factor_expires_at',
        'plan', // free, pro
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_expires_at' => 'datetime',
            'two_factor_email_enabled' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class, 'created_by_user_id');
    }

    public function gamePresets(): HasMany
    {
        return $this->hasMany(GamePreset::class);
    }

    public function rosterShares(): HasMany
    {
        return $this->hasMany(RosterShare::class, 'shared_with_user_id');
    }

    public function gameShares(): HasMany
    {
        return $this->hasMany(GameShare::class, 'shared_with_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isScorekeeper(): bool
    {
        return in_array($this->role, ['admin', 'scorekeeper', 'coach']);
    }

    public function isPro(): bool
    {
        if ($this->plan === 'pro') {
            return true;
        }

        if ($this->subscribed('default')) {
            return true;
        }

        if ($this->organization && $this->organization->isEnterprise()) {
            return true;
        }

        return false;
    }

    public function isEnterprise(): bool
    {
        return $this->organization && $this->organization->isEnterprise();
    }

    public function getEffectivePlan(): string
    {
        if ($this->isEnterprise()) {
            return 'enterprise';
        }
        if ($this->isPro()) {
            return 'pro';
        }
        return 'free';
    }

    public function canCreateRoster(): bool
    {
        if ($this->isPro()) {
            return true;
        }

        // Free plan limit: max 2 teams in the user's organization
        if (! $this->organization_id) {
            return true;
        }

        $count = Team::where('organization_id', $this->organization_id)->count();

        return $count < 2;
    }
}
