<?php

namespace App\Jobs;

use App\Models\BiddingJob;
use App\Models\BiddingSetting;
use App\Models\Portfolio;
use App\Models\User;
use App\Services\FreelancerApiService;
use App\Services\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessAutoBidding implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // Wait 60 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = User::find($this->userId);
        
        if (!$user) {
            Log::error('ProcessAutoBidding: User not found', ['user_id' => $this->userId]);
            return;
        }

        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            Log::info('ProcessAutoBidding: User has no active subscription', ['user_id' => $this->userId]);
            return;
        }

        // Check if user has remaining bids
        $remainingBids = $user->getRemainingBids();
        if ($remainingBids <= 0) {
            Log::info('ProcessAutoBidding: User has no remaining bids', ['user_id' => $this->userId]);
            return;
        }

        // Get bidding settings
        $biddingSettings = $user->biddingSettings;
        if (!$biddingSettings || !$biddingSettings->auto_bid_enabled) {
            Log::info('ProcessAutoBidding: Auto-bidding not enabled', ['user_id' => $this->userId]);
            return;
        }

        // Check if user has Freelancer access token
        if (!$user->freelancer_access_token) {
            Log::error('ProcessAutoBidding: User has no Freelancer access token', ['user_id' => $this->userId]);
            return;
        }

        // Check if token is expired
        if ($user->freelancer_token_expires_at && $user->freelancer_token_expires_at->isPast()) {
            Log::warning('ProcessAutoBidding: Access token expired', ['user_id' => $this->userId]);
            // TODO: Implement token refresh logic
            return;
        }

        // Check if it's within bidding times
        if (!$this->isWithinBiddingTimes($biddingSettings)) {
            Log::info('ProcessAutoBidding: Outside bidding times', ['user_id' => $this->userId]);
            return;
        }

        // Initialize Freelancer API service
        $apiService = new FreelancerApiService($user->freelancer_access_token);

        // Search for projects matching user's criteria
        $projects = $this->searchProjects($apiService, $biddingSettings, $user);
        
        if (empty($projects)) {
            Log::info('ProcessAutoBidding: No matching projects found', ['user_id' => $this->userId]);
            return;
        }

        // Process up to the remaining bids or available projects
        $maxBids = min($remainingBids, count($projects), 10); // Limit to 10 bids per job run
        
        $bidsSubmitted = 0;
        $bidsFailed = 0;

        foreach (array_slice($projects, 0, $maxBids) as $project) {
            // Check if we already bid on this project
            $existingBid = BiddingJob::where('user_id', $user->id)
                ->where('freelancer_project_id', $project['id'])
                ->first();

            if ($existingBid) {
                Log::info('ProcessAutoBidding: Already bid on project', [
                    'user_id' => $user->id,
                    'project_id' => $project['id'],
                ]);
                continue;
            }

            // Calculate bid amount
            $bidAmount = $this->calculateBidAmount($project, $biddingSettings);
            
            if (!$bidAmount || ($biddingSettings->max_bid_amount && $bidAmount > $biddingSettings->max_bid_amount)) {
                Log::info('ProcessAutoBidding: Bid amount exceeds limit', [
                    'user_id' => $user->id,
                    'project_id' => $project['id'],
                    'bid_amount' => $bidAmount,
                ]);
                continue;
            }

            // Submit bid
            $result = $this->submitBid($apiService, $project, $bidAmount, $biddingSettings, $user);

            if ($result['success']) {
                $bidsSubmitted++;
                
                // Update subscription bids used
                $this->incrementBidsUsed($user);
                
                Log::info('ProcessAutoBidding: Bid submitted successfully', [
                    'user_id' => $user->id,
                    'project_id' => $project['id'],
                    'bid_amount' => $bidAmount,
                ]);
            } else {
                $bidsFailed++;
                
                Log::error('ProcessAutoBidding: Bid submission failed', [
                    'user_id' => $user->id,
                    'project_id' => $project['id'],
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            // Small delay between bids to respect rate limits
            usleep(500000); // 0.5 seconds
        }

        Log::info('ProcessAutoBidding: Job completed', [
            'user_id' => $user->id,
            'bids_submitted' => $bidsSubmitted,
            'bids_failed' => $bidsFailed,
        ]);
    }

    /**
     * Search for projects matching user's criteria.
     */
    private function searchProjects(FreelancerApiService $apiService, BiddingSetting $settings, User $user): array
    {
        $criteria = [];

        // Add countries filter
        if ($settings->countries && !empty($settings->countries)) {
            $criteria['countries'] = $settings->countries;
        }

        // Add technologies/skills filter
        if ($settings->technologies && !empty($settings->technologies)) {
            // Map technology names to IDs (simplified - in real implementation, you'd need to map these)
            $criteria['jobs'] = $settings->technologies;
        }

        // Add categories filter
        if ($settings->categories && !empty($settings->categories)) {
            $criteria['categories'] = $settings->categories;
        }

        // Add budget filters
        if ($settings->min_budget) {
            $criteria['min_budget'] = $settings->min_budget;
        }
        if ($settings->max_budget) {
            $criteria['max_budget'] = $settings->max_budget;
        }

        // Search for projects
        $response = $apiService->searchProjects($criteria);

        if (isset($response['status']) && $response['status'] === 'success' && isset($response['result'])) {
            $projects = $response['result'];
            
            // Filter projects based on additional criteria
            return array_filter($projects, function ($project) use ($settings) {
                // Filter by budget type
                if ($settings->budget_type) {
                    $projectBudgetType = $project['budget']['minimum'] ?? null;
                    if ($settings->budget_type === 'fixed' && isset($project['hourly'])) {
                        return false;
                    }
                    if ($settings->budget_type === 'hourly' && !isset($project['hourly'])) {
                        return false;
                    }
                }

                // Only include open projects
                if (isset($project['status']) && $project['status'] !== 'active') {
                    return false;
                }

                return true;
            });
        }

        return [];
    }

    /**
     * Calculate bid amount for a project.
     */
    private function calculateBidAmount(array $project, BiddingSetting $settings): ?float
    {
        // Get project budget
        $projectBudget = null;
        
        if (isset($project['budget']['minimum']) && isset($project['budget']['maximum'])) {
            $projectBudget = ($project['budget']['minimum'] + $project['budget']['maximum']) / 2;
        } elseif (isset($project['budget']['minimum'])) {
            $projectBudget = $project['budget']['minimum'];
        } elseif (isset($project['hourly']['min_hourly_rate'])) {
            $projectBudget = $project['hourly']['min_hourly_rate'] * 40; // Estimate for hourly projects
        }

        if (!$projectBudget) {
            return null;
        }

        // Calculate bid as a percentage of project budget (e.g., 80-90%)
        $bidPercentage = 0.85; // Default to 85% of project budget
        $bidAmount = $projectBudget * $bidPercentage;

        // Ensure bid is within user's max bid amount
        if ($settings->max_bid_amount && $bidAmount > $settings->max_bid_amount) {
            $bidAmount = $settings->max_bid_amount;
        }

        return round($bidAmount, 2);
    }

    /**
     * Submit a bid on a project.
     */
    private function submitBid(
        FreelancerApiService $apiService,
        array $project,
        float $bidAmount,
        BiddingSetting $settings,
        User $user
    ): array {
        // Get relevant portfolios
        $portfolios = $this->getRelevantPortfolios($user, $project);
        
        // Generate proposal using AI service
        try {
            $aiService = new AiService();
            $coverLetter = $aiService->generateProposal($project, $portfolios, $user->name);
        } catch (\Exception $e) {
            Log::warning('ProcessAutoBidding: AI proposal generation failed, using fallback', [
                'user_id' => $user->id,
                'project_id' => $project['id'],
                'error' => $e->getMessage(),
            ]);
            
            // Fallback to template-based proposal
            $coverLetter = $settings->cover_letter_template ?? 'I am interested in this project and would like to help you achieve your goals.';
            $coverLetter = str_replace('{project_title}', $project['title'] ?? 'this project', $coverLetter);
            $coverLetter = str_replace('{bid_amount}', number_format($bidAmount, 2), $coverLetter);
            
            // Add relevant portfolios to cover letter
            if (!empty($portfolios)) {
                $portfolioSection = "\n\nRelevant Portfolio:\n";
                foreach ($portfolios as $portfolio) {
                    $portfolioSection .= "- {$portfolio->title}";
                    if ($portfolio->url) {
                        $portfolioSection .= " ({$portfolio->url})";
                    }
                    if ($portfolio->description) {
                        $portfolioSection .= ": {$portfolio->description}";
                    }
                    $portfolioSection .= "\n";
                }
                $coverLetter .= $portfolioSection;
            }
        }

        try {
            $response = $apiService->submitBid(
                $project['id'],
                $bidAmount,
                $coverLetter
            );

            if (isset($response['status']) && $response['status'] === 'success') {
            // Record successful bid with project details
            BiddingJob::create([
                'user_id' => $user->id,
                'freelancer_project_id' => (string)$project['id'],
                'project_title' => $project['title'] ?? null,
                'project_description' => $project['description'] ?? null,
                'project_budget' => $project['budget']['minimum'] ?? $project['budget']['maximum'] ?? null,
                'project_url' => $project['url'] ?? "https://www.freelancer.com/projects/{$project['id']}",
                'project_posted_at' => isset($project['time_submitted']) ? \Carbon\Carbon::createFromTimestamp($project['time_submitted']) : null,
                'status' => 'pending',
                'bid_amount' => $bidAmount,
                'cover_letter' => $coverLetter,
                'submitted_at' => now(),
            ]);

                return ['success' => true];
            } else {
            // Record failed bid with project details
            BiddingJob::create([
                'user_id' => $user->id,
                'freelancer_project_id' => (string)$project['id'],
                'project_title' => $project['title'] ?? null,
                'project_description' => $project['description'] ?? null,
                'project_budget' => $project['budget']['minimum'] ?? $project['budget']['maximum'] ?? null,
                'project_url' => $project['url'] ?? "https://www.freelancer.com/projects/{$project['id']}",
                'project_posted_at' => isset($project['time_submitted']) ? \Carbon\Carbon::createFromTimestamp($project['time_submitted']) : null,
                'status' => 'failed',
                'bid_amount' => $bidAmount,
                'cover_letter' => $coverLetter,
                'error_message' => $response['message'] ?? 'Unknown error',
                'submitted_at' => now(),
            ]);

                return ['success' => false, 'error' => $response['message'] ?? 'Unknown error'];
            }
        } catch (\Exception $e) {
            Log::error('ProcessAutoBidding: Exception submitting bid', [
                'user_id' => $user->id,
                'project_id' => $project['id'],
                'error' => $e->getMessage(),
            ]);

            // Record failed bid with project details
            BiddingJob::create([
                'user_id' => $user->id,
                'freelancer_project_id' => (string)$project['id'],
                'project_title' => $project['title'] ?? null,
                'project_description' => $project['description'] ?? null,
                'project_budget' => $project['budget']['minimum'] ?? $project['budget']['maximum'] ?? null,
                'project_url' => $project['url'] ?? "https://www.freelancer.com/projects/{$project['id']}",
                'project_posted_at' => isset($project['time_submitted']) ? \Carbon\Carbon::createFromTimestamp($project['time_submitted']) : null,
                'status' => 'failed',
                'bid_amount' => $bidAmount,
                'cover_letter' => $coverLetter,
                'error_message' => $e->getMessage(),
                'submitted_at' => now(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if current time is within bidding times.
     */
    private function isWithinBiddingTimes(BiddingSetting $settings): bool
    {
        if (!$settings->bidding_times || empty($settings->bidding_times)) {
            return true; // No time restrictions
        }

        $now = Carbon::now();
        $currentDay = strtolower($now->format('l')); // Monday, Tuesday, etc.
        $currentTime = $now->format('H:i');

        foreach ($settings->bidding_times as $timeSlot) {
            $day = strtolower($timeSlot['day'] ?? '');
            $start = $timeSlot['start'] ?? '00:00';
            $end = $timeSlot['end'] ?? '23:59';

            if ($day === $currentDay && $currentTime >= $start && $currentTime <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * Increment bids used for user's active subscription.
     */
    private function incrementBidsUsed(User $user): void
    {
        $activeSubscription = $user->activeSubscription();
        
        if ($activeSubscription) {
            $pivot = $user->subscriptions()
                ->wherePivot('is_active', true)
                ->where('subscriptions.id', $activeSubscription->id)
                ->first()
                ->pivot;

            if ($pivot) {
                $user->subscriptions()->updateExistingPivot($activeSubscription->id, [
                    'bids_used' => ($pivot->bids_used ?? 0) + 1,
                ]);
            }
        }
    }

    /**
     * Get relevant portfolios for a project based on technologies.
     */
    private function getRelevantPortfolios(User $user, array $project): array
    {
        // Get project technologies/jobs
        $projectTechnologies = [];
        if (isset($project['jobs']) && is_array($project['jobs'])) {
            foreach ($project['jobs'] as $job) {
                if (isset($job['name'])) {
                    $projectTechnologies[] = strtolower($job['name']);
                }
            }
        }

        // Get user's active portfolios
        $portfolios = $user->portfolios()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        // Filter portfolios that match project technologies
        $relevantPortfolios = $portfolios->filter(function ($portfolio) use ($projectTechnologies) {
            if (empty($portfolio->technologies)) {
                return true; // Include portfolios without specific technologies
            }

            $portfolioTechs = array_map('strtolower', $portfolio->technologies);
            
            // Check if any portfolio technology matches project technologies
            foreach ($projectTechnologies as $projectTech) {
                foreach ($portfolioTechs as $portfolioTech) {
                    if (str_contains($portfolioTech, $projectTech) || str_contains($projectTech, $portfolioTech)) {
                        return true;
                    }
                }
            }

            return false;
        });

        // If no matches, return first 2 active portfolios
        if ($relevantPortfolios->isEmpty()) {
            return $portfolios->take(2)->all();
        }

        return $relevantPortfolios->take(3)->all(); // Return up to 3 relevant portfolios
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAutoBidding: Job failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
