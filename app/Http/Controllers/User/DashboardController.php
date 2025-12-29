<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $activeSubscription = $user->activeSubscription();
        $remainingBids = $user->getRemainingBids();
        $biddingSettings = $user->biddingSettings;

        return Inertia::render('User/Dashboard', [
            'user' => $user,
            'activeSubscription' => $activeSubscription,
            'remainingBids' => $remainingBids,
            'biddingSettings' => $biddingSettings,
        ]);
    }
}


