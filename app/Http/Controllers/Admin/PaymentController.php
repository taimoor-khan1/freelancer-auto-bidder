<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(): Response
    {
        // Get all user subscriptions as payments
        $payments = DB::table('user_subscriptions')
            ->join('users', 'user_subscriptions.user_id', '=', 'users.id')
            ->join('subscriptions', 'user_subscriptions.subscription_id', '=', 'subscriptions.id')
            ->select(
                'user_subscriptions.id',
                'users.name as user_name',
                'users.email as user_email',
                'subscriptions.name as subscription_name',
                'subscriptions.price',
                'user_subscriptions.starts_at',
                'user_subscriptions.ends_at',
                'user_subscriptions.is_active',
                'user_subscriptions.created_at',
                'user_subscriptions.bids_used'
            )
            ->orderBy('user_subscriptions.created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'user_name' => $payment->user_name,
                    'user_email' => $payment->user_email,
                    'subscription_name' => $payment->subscription_name,
                    'amount' => $payment->price,
                    'status' => $payment->is_active ? 'Active' : 'Inactive',
                    'starts_at' => $payment->starts_at ? date('Y-m-d', strtotime($payment->starts_at)) : null,
                    'ends_at' => $payment->ends_at ? date('Y-m-d', strtotime($payment->ends_at)) : null,
                    'bids_used' => $payment->bids_used,
                    'created_at' => date('Y-m-d H:i:s', strtotime($payment->created_at)),
                ];
            });

        // Calculate totals
        $totalRevenue = DB::table('user_subscriptions')
            ->join('subscriptions', 'user_subscriptions.subscription_id', '=', 'subscriptions.id')
            ->where('user_subscriptions.is_active', true)
            ->sum('subscriptions.price');

        $totalPayments = $payments->count();
        $activePayments = $payments->where('status', 'Active')->count();

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'stats' => [
                'total_revenue' => $totalRevenue,
                'total_payments' => $totalPayments,
                'active_payments' => $activePayments,
            ],
        ]);
    }
}

