<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiddingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'countries',
        'technologies',
        'categories',
        'min_budget',
        'max_budget',
        'budget_type',
        'bidding_times',
        'max_bid_amount',
        'cover_letter_template',
        'auto_bid_enabled',
    ];

    protected $casts = [
        'countries' => 'array',
        'technologies' => 'array',
        'categories' => 'array',
        'bidding_times' => 'array',
        'min_budget' => 'decimal:2',
        'max_budget' => 'decimal:2',
        'max_bid_amount' => 'integer',
        'auto_bid_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the bidding settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


