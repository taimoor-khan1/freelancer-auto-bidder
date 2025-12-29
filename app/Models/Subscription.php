<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'bid_limit',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'bid_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the users that have this subscription.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_subscriptions')
            ->withPivot('bids_used', 'starts_at', 'ends_at', 'is_active')
            ->withTimestamps();
    }
}


