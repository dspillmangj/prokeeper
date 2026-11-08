<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'plan', // free, enterprise
        'seats',
        'billing_user_id',
        'city',
        'state',
        'primary_color',
        'secondary_color',
        'logo_url',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function billingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billing_user_id');
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function gamePresets(): HasMany
    {
        return $this->hasMany(GamePreset::class);
    }

    public function isEnterprise(): bool
    {
        if ($this->plan === 'enterprise') {
            return true;
        }

        if ($this->billingUser && $this->billingUser->subscribed('enterprise')) {
            return true;
        }

        return false;
    }

    public function syncEnterpriseSeats(): void
    {
        $activeUserCount = max(1, $this->users()->count());
        $this->update(['seats' => $activeUserCount]);

        if ($this->billingUser && $this->billingUser->subscribed('enterprise')) {
            $subscription = $this->billingUser->subscription('enterprise');
            if ($subscription && $subscription->quantity !== $activeUserCount) {
                try {
                    $subscription->updateQuantity($activeUserCount);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }
    }
}
