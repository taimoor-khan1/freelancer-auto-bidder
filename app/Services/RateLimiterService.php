<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class RateLimiterService
{
    /**
     * Check if we can make an API request based on rate limits.
     * 
     * Freelancer API limits:
     * - 60 requests per minute per access token
     * - 1000 requests per hour per access token
     */
    public function canMakeRequest(string $accessToken, int $perMinute = 60, int $perHour = 1000): bool
    {
        $minuteKey = "rate_limit:minute:{$accessToken}:" . now()->format('Y-m-d-H-i');
        $hourKey = "rate_limit:hour:{$accessToken}:" . now()->format('Y-m-d-H');
        
        $minuteCount = Cache::get($minuteKey, 0);
        $hourCount = Cache::get($hourKey, 0);
        
        return $minuteCount < $perMinute && $hourCount < $perHour;
    }

    /**
     * Record an API request.
     */
    public function recordRequest(string $accessToken): void
    {
        $minuteKey = "rate_limit:minute:{$accessToken}:" . now()->format('Y-m-d-H-i');
        $hourKey = "rate_limit:hour:{$accessToken}:" . now()->format('Y-m-d-H');
        
        Cache::increment($minuteKey, 1);
        Cache::increment($hourKey, 1);
        
        // Set expiration
        Cache::put($minuteKey, Cache::get($minuteKey), now()->addMinute());
        Cache::put($hourKey, Cache::get($hourKey), now()->addHour());
    }

    /**
     * Get remaining requests for the current minute.
     */
    public function getRemainingRequests(string $accessToken, int $limit = 60): int
    {
        $minuteKey = "rate_limit:minute:{$accessToken}:" . now()->format('Y-m-d-H-i');
        $currentCount = Cache::get($minuteKey, 0);
        
        return max(0, $limit - $currentCount);
    }

    /**
     * Wait until we can make a request (with exponential backoff).
     */
    public function waitForRateLimit(string $accessToken, int $maxWaitSeconds = 60): bool
    {
        $waitTime = 1;
        $maxAttempts = 10;
        $attempt = 0;
        
        while (!$this->canMakeRequest($accessToken) && $attempt < $maxAttempts) {
            sleep($waitTime);
            $waitTime = min($waitTime * 2, $maxWaitSeconds);
            $attempt++;
        }
        
        return $this->canMakeRequest($accessToken);
    }
}

