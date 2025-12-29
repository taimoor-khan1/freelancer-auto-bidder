<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::where('role', 'user')
            ->with(['subscriptions', 'biddingSettings']);

        // Filter by subscription status
        if ($request->has('subscription_status')) {
            if ($request->subscription_status === 'active') {
                $query->whereHas('subscriptions', function ($q) {
                    $q->wherePivot('is_active', true)
                      ->where(function ($query) {
                          $query->wherePivot('ends_at', '>', now())
                                ->orWhereNull('user_subscriptions.ends_at');
                      });
                });
            } elseif ($request->subscription_status === 'inactive') {
                $query->whereDoesntHave('subscriptions', function ($q) {
                    $q->wherePivot('is_active', true)
                      ->where(function ($query) {
                          $query->wherePivot('ends_at', '>', now())
                                ->orWhereNull('user_subscriptions.ends_at');
                      });
                });
            }
        }

        $users = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                $activeSub = $user->activeSubscription();
                $remainingBids = $user->getRemainingBids();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'has_active_subscription' => $activeSub !== null,
                    'active_subscription' => $activeSub ? $activeSub->name : null,
                    'subscription_bid_limit' => $activeSub ? $activeSub->bid_limit : 0,
                    'bids_used' => $activeSub ? ($activeSub->bid_limit - $remainingBids) : 0,
                    'remaining_bids' => $remainingBids,
                    'total_bids' => $user->biddingJobs()->count(),
                ];
            });

        // Calculate stats
        $stats = [
            'total_users' => User::where('role', 'user')->count(),
            'users_with_active_subscription' => User::where('role', 'user')
                ->whereHas('subscriptions', function ($q) {
                    $q->wherePivot('is_active', true)
                      ->where(function ($query) {
                          $query->wherePivot('ends_at', '>', now())
                                ->orWhereNull('user_subscriptions.ends_at');
                      });
                })
                ->count(),
            'total_remaining_bids' => $users->sum('remaining_bids'),
        ];

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => [
                'subscription_status' => $request->subscription_status ?? 'all',
            ],
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $user->load(['subscriptions', 'biddingSettings']);
        
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'subscriptions' => \App\Models\Subscription::where('is_active', true)->get(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'username' => 'nullable|string|unique:users,username,' . $user->id,
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        if ($user->role === 'admin') {
            return back()->withErrors(['error' => 'Cannot delete admin user.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}

