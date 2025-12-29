<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\FreelancerApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    protected FreelancerApiService $freelancerApi;

    public function __construct(FreelancerApiService $freelancerApi)
    {
        $this->freelancerApi = $freelancerApi;
    }

    /**
     * Display a listing of projects.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        $activeTab = $request->get('tab', 'live'); // 'live' or 'proposals'
        
        $liveProjects = [];
        $sentProposals = [];

        // Fetch live projects from Freelancer API
        if ($activeTab === 'live' && $user->freelancer_access_token) {
            try {
                $this->freelancerApi->setAccessToken($user->freelancer_access_token);
                
                // Get user's bidding settings to filter projects
                $biddingSettings = $user->biddingSettings;
                $criteria = [];
                
                if ($biddingSettings) {
                    if ($biddingSettings->countries) {
                        $criteria['countries'] = $biddingSettings->countries;
                    }
                    if ($biddingSettings->technologies) {
                        $criteria['jobs'] = $biddingSettings->technologies;
                    }
                    if ($biddingSettings->categories) {
                        $criteria['categories'] = $biddingSettings->categories;
                    }
                    if ($biddingSettings->min_budget) {
                        $criteria['min_budget'] = $biddingSettings->min_budget;
                    }
                    if ($biddingSettings->max_budget) {
                        $criteria['max_budget'] = $biddingSettings->max_budget;
                    }
                }
                
                $criteria['limit'] = 50;
                $criteria['offset'] = $request->get('page', 1) - 1;
                
                $response = $this->freelancerApi->searchProjects($criteria);
                
                if (isset($response['status']) && $response['status'] === 'success' && isset($response['result'])) {
                    $liveProjects = array_map(function ($project) use ($user) {
                        // Check if user already bid on this project
                        $hasBid = $user->biddingJobs()
                            ->where('freelancer_project_id', (string)$project['id'])
                            ->exists();
                            
                        return [
                            'id' => $project['id'],
                            'title' => $project['title'] ?? 'Untitled Project',
                            'description' => $project['description'] ?? null,
                            'budget' => $project['budget']['minimum'] ?? $project['budget']['maximum'] ?? null,
                            'budget_max' => $project['budget']['maximum'] ?? null,
                            'url' => $project['url'] ?? "https://www.freelancer.com/projects/{$project['id']}",
                            'posted_at' => isset($project['time_submitted']) 
                                ? date('Y-m-d H:i:s', $project['time_submitted']) 
                                : null,
                            'status' => $project['status'] ?? 'active',
                            'has_bid' => $hasBid,
                            'jobs' => $project['jobs'] ?? [],
                            'currency' => $project['currency']['code'] ?? 'USD',
                        ];
                    }, $response['result']);
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching live projects', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fetch sent proposals (bidding jobs)
        $query = $user->biddingJobs()->orderBy('submitted_at', 'desc');

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $sentProposals = $query->paginate(20)->through(function ($job) {
            return [
                'id' => $job->id,
                'freelancer_project_id' => $job->freelancer_project_id,
                'project_title' => $job->project_title,
                'project_description' => $job->project_description,
                'project_budget' => $job->project_budget,
                'project_url' => $job->project_url,
                'project_posted_at' => $job->project_posted_at?->format('Y-m-d H:i:s'),
                'status' => $job->status,
                'bid_amount' => $job->bid_amount,
                'cover_letter' => $job->cover_letter,
                'submitted_at' => $job->submitted_at?->format('Y-m-d H:i:s'),
                'created_at' => $job->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return Inertia::render('User/Projects/Index', [
            'liveProjects' => $liveProjects,
            'sentProposals' => $sentProposals,
            'activeTab' => $activeTab,
            'filters' => [
                'status' => $request->status ?? 'all',
            ],
        ]);
    }
}
