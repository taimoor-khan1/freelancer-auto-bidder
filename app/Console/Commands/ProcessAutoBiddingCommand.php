<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAutoBidding;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAutoBiddingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bidding:process 
                            {--user-id= : Process bidding for a specific user ID}
                            {--limit=10 : Maximum number of users to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process auto-bidding for users with active subscriptions and enabled auto-bidding';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting auto-bidding processing...');

        // Get users with active auto-bidding
        $query = User::where('role', 'user')
            ->whereHas('biddingSettings', function ($q) {
                $q->where('auto_bid_enabled', true);
            })
            ->whereHas('subscriptions', function ($q) {
                $q->wherePivot('is_active', true)
                  ->where(function ($query) {
                      $query->wherePivot('ends_at', '>', now())
                            ->orWhereNull('user_subscriptions.ends_at');
                  });
            })
            ->whereNotNull('freelancer_access_token');

        // Filter by specific user if provided
        if ($this->option('user-id')) {
            $query->where('id', $this->option('user-id'));
        }

        // Limit the number of users to process
        $limit = (int) $this->option('limit');
        $users = $query->limit($limit)->get();

        if ($users->isEmpty()) {
            $this->warn('No users found with active auto-bidding.');
            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} user(s) with active auto-bidding.");

        $dispatched = 0;
        $skipped = 0;

        foreach ($users as $user) {
            // Check if user has remaining bids
            $remainingBids = $user->getRemainingBids();
            
            if ($remainingBids <= 0) {
                $this->warn("Skipping user {$user->id} ({$user->email}): No remaining bids");
                $skipped++;
                continue;
            }

            // Check if token is expired
            if ($user->freelancer_token_expires_at && $user->freelancer_token_expires_at->isPast()) {
                $this->warn("Skipping user {$user->id} ({$user->email}): Access token expired");
                $skipped++;
                continue;
            }

            // Dispatch job for this user
            ProcessAutoBidding::dispatch($user->id)
                ->onQueue('bidding'); // Use a dedicated queue for bidding

            $this->info("Dispatched bidding job for user {$user->id} ({$user->email}) - {$remainingBids} bids remaining");
            $dispatched++;
        }

        $this->info("Processing complete: {$dispatched} job(s) dispatched, {$skipped} user(s) skipped.");

        Log::info('ProcessAutoBiddingCommand: Completed', [
            'dispatched' => $dispatched,
            'skipped' => $skipped,
        ]);

        return Command::SUCCESS;
    }
}
