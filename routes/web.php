<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Auth\FreelancerOAuthController;
use App\Http\Controllers\User\BiddingController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Freelancer OAuth routes
Route::get('/auth/freelancer/redirect', [FreelancerOAuthController::class, 'redirect'])
    ->name('freelancer.redirect');
Route::get('/auth/freelancer/callback', [FreelancerOAuthController::class, 'callback'])
    ->name('freelancer.callback');

// Admin routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('subscriptions', SubscriptionController::class);
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/ai-settings', [\App\Http\Controllers\Admin\AiSettingController::class, 'index'])->name('ai-settings.index');
    Route::put('/ai-settings', [\App\Http\Controllers\Admin\AiSettingController::class, 'update'])->name('ai-settings.update');
});

// User routes
Route::middleware(['auth', 'verified'])->prefix('user')->name('user.')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/bidding', [BiddingController::class, 'index'])->name('bidding.index');
    Route::post('/bidding/settings', [BiddingController::class, 'updateSettings'])->name('bidding.settings');
    Route::post('/bidding/start', [BiddingController::class, 'start'])->name('bidding.start');
    Route::post('/bidding/stop', [BiddingController::class, 'stop'])->name('bidding.stop');
    Route::post('/bidding/refresh-data', [BiddingController::class, 'refreshData'])->name('bidding.refresh-data');
    
    // Projects routes
    Route::get('/projects', [\App\Http\Controllers\User\ProjectController::class, 'index'])->name('projects.index');
    
    // Portfolio routes
    Route::resource('portfolio', \App\Http\Controllers\User\PortfolioController::class)->except(['show', 'create', 'edit']);
});

// Legacy dashboard route - redirect based on role
Route::get('dashboard', function () {
    $user = auth()->user();
    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('user.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
