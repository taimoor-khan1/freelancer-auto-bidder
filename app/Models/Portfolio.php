<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portfolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'url',
        'image_url',
        'technologies',
        'order',
        'is_active',
    ];

    protected $casts = [
        'technologies' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the user that owns the portfolio.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
