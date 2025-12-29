<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'freelancer_user_id',
        'freelancer_access_token',
        'freelancer_refresh_token',
        'freelancer_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
        'freelancer_access_token',
        'freelancer_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'freelancer_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is regular user.
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Get the subscriptions for the user.
     */
    public function subscriptions()
    {
        return $this->belongsToMany(Subscription::class, 'user_subscriptions')
            ->withPivot('bids_used', 'starts_at', 'ends_at', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get active subscription for the user.
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->wherePivot('is_active', true)
            ->where(function ($query) {
                $query->wherePivot('ends_at', '>', now())
                    ->orWhereNull('user_subscriptions.ends_at');
            })
            ->first();
    }

    /**
     * Get the bidding settings for the user.
     */
    public function biddingSettings()
    {
        return $this->hasOne(BiddingSetting::class);
    }

    /**
     * Get the bidding jobs for the user.
     */
    public function biddingJobs()
    {
        return $this->hasMany(BiddingJob::class);
    }

    /**
     * Get the portfolios for the user.
     */
    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    /**
     * Check if user has active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Get remaining bids for user.
     */
    public function getRemainingBids(): int
    {
        $subscription = $this->activeSubscription();
        if (!$subscription) {
            return 0;
        }

        $bidLimit = $subscription->bid_limit;
        $bidsUsed = $subscription->pivot->bids_used ?? 0;

        return max(0, $bidLimit - $bidsUsed);
    }
}
