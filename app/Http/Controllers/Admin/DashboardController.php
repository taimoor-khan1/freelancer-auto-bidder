<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        $stats = [
            'total_users' => User::where('role', 'user')->count(),
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('is_active', true)->count(),
            'total_bids' => \App\Models\BiddingJob::count(),
            'total_revenue' => DB::table('user_subscriptions')
                ->join('subscriptions', 'user_subscriptions.subscription_id', '=', 'subscriptions.id')
                ->where('user_subscriptions.is_active', true)
                ->sum('subscriptions.price'),
        ];

        // Get recent subscriptions
        $subscriptions = Subscription::orderBy('created_at', 'desc')->limit(5)->get();

        // Get recent users
        $recentUsers = User::where('role', 'user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at->format('Y-m-d'),
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'subscriptions' => $subscriptions,
            'recentUsers' => $recentUsers,
        ]);
    }
}


