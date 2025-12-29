<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiddingJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'freelancer_project_id',
        'project_title',
        'project_description',
        'project_budget',
        'project_url',
        'project_posted_at',
        'status',
        'bid_amount',
        'cover_letter',
        'error_message',
        'submitted_at',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'project_budget' => 'decimal:2',
        'submitted_at' => 'datetime',
        'project_posted_at' => 'datetime',
    ];

    /**
     * Get the user that owns the bidding job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}


