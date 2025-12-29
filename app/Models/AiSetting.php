<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = [
        'ai_model',
        'api_key',
        'prompt_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the active AI setting.
     */
    public static function getActive(): ?self
    {
        return self::where('is_active', true)->first();
    }
}
