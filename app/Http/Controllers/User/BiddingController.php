<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\BiddingJob;
use App\Models\BiddingSetting;
use App\Services\FreelancerApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BiddingController extends Controller
{
    protected FreelancerApiService $freelancerApi;

    public function __construct(FreelancerApiService $freelancerApi)
    {
        $this->freelancerApi = $freelancerApi;
    }

    /**
     * Display the user's bidding dashboard.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $biddingSettings = $user->biddingSettings;
        $activeSubscription = $user->activeSubscription();
        $remainingBids = $user->getRemainingBids();
        $biddingJobs = $user->biddingJobs()->orderBy('created_at', 'desc')->limit(50)->get();

        // Fetch data from Freelancer API
        $countries = $this->freelancerApi->getCountries();
        $technologies = $this->freelancerApi->getSkills();
        $categories = $this->freelancerApi->getCategories();

        return Inertia::render('User/Bidding/Index', [
            'biddingSettings' => $biddingSettings,
            'activeSubscription' => $activeSubscription,
            'remainingBids' => $remainingBids,
            'biddingJobs' => $biddingJobs,
            'countries' => $countries,
            'technologies' => $technologies,
            'categories' => $categories,
        ]);
    }

    /**
     * Update bidding settings.
     */
    public function updateSettings(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'countries' => 'nullable|array',
            'technologies' => 'nullable|array',
            'categories' => 'nullable|array',
            'min_budget' => 'nullable|numeric|min:0',
            'max_budget' => 'nullable|numeric|min:0',
            'budget_type' => 'nullable|string|in:fixed,hourly,both',
            'bidding_times' => 'nullable|array',
            'max_bid_amount' => 'nullable|integer|min:0',
            'cover_letter_template' => 'nullable|string',
            'auto_bid_enabled' => 'boolean',
        ]);

        $biddingSettings = $user->biddingSettings;
        
        if ($biddingSettings) {
            $biddingSettings->update($validated);
        } else {
            $validated['user_id'] = $user->id;
            BiddingSetting::create($validated);
        }

        return redirect()->route('user.bidding.index')
            ->with('success', 'Bidding settings updated successfully.');
    }

    /**
     * Start auto-bidding.
     */
    public function start(Request $request)
    {
        $user = auth()->user();

        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            return back()->withErrors(['error' => 'You need an active subscription to start auto-bidding.']);
        }

        // Check if user has remaining bids
        if ($user->getRemainingBids() <= 0) {
            return back()->withErrors(['error' => 'You have no remaining bids.']);
        }

        // Check if bidding settings exist
        $biddingSettings = $user->biddingSettings;
        if (!$biddingSettings) {
            return back()->withErrors(['error' => 'Please configure your bidding settings first.']);
        }

        // Check if user has Freelancer access token
        if (!$user->freelancer_access_token) {
            return back()->withErrors(['error' => 'Please connect your Freelancer account first.']);
        }

        // Enable auto-bidding
        $biddingSettings->update(['auto_bid_enabled' => true]);

        // Dispatch the auto-bidding job immediately
        \App\Jobs\ProcessAutoBidding::dispatch($user->id)
            ->onQueue('bidding');

        return redirect()->route('user.bidding.index')
            ->with('success', 'Auto-bidding started successfully. The system will now bid on projects matching your criteria.');
    }

    /**
     * Stop auto-bidding.
     */
    public function stop()
    {
        $user = auth()->user();
        $biddingSettings = $user->biddingSettings;

        if ($biddingSettings) {
            $biddingSettings->update(['auto_bid_enabled' => false]);
        }

        return redirect()->route('user.bidding.index')
            ->with('success', 'Auto-bidding stopped successfully.');
    }

    /**
     * Refresh Freelancer API data (countries, technologies, categories).
     */
    public function refreshData(): Response
    {
        // Clear cache to force refresh
        \Illuminate\Support\Facades\Cache::forget('freelancer_countries');
        \Illuminate\Support\Facades\Cache::forget('freelancer_skills');
        \Illuminate\Support\Facades\Cache::forget('freelancer_categories');

        return redirect()->route('user.bidding.index')
            ->with('success', 'Freelancer data refreshed successfully.');
    }
}


