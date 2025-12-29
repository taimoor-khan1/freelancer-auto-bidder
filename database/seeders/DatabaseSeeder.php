<?php

namespace Database\Seeders;

use App\Models\BiddingSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'email' => 'admin@freelancer.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Regular User
        $user = User::create([
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'user@freelancer.com',
            'password' => Hash::make('user123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        // Create Subscriptions
        $basicSubscription = Subscription::create([
            'name' => 'Basic Plan',
            'description' => 'Perfect for getting started with auto-bidding',
            'price' => 9.99,
            'bid_limit' => 50,
            'is_active' => true,
        ]);

        $proSubscription = Subscription::create([
            'name' => 'Pro Plan',
            'description' => 'For serious freelancers who want more bids',
            'price' => 29.99,
            'bid_limit' => 200,
            'is_active' => true,
        ]);

        $premiumSubscription = Subscription::create([
            'name' => 'Premium Plan',
            'description' => 'Unlimited bids for power users',
            'price' => 49.99,
            'bid_limit' => 500,
            'is_active' => true,
        ]);

        // Assign Pro subscription to the user
        $user->subscriptions()->attach($proSubscription->id, [
            'bids_used' => 5,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        // Create bidding settings for the user
        BiddingSetting::create([
            'user_id' => $user->id,
            'countries' => ['US', 'UK', 'CA'],
            'technologies' => ['PHP', 'JavaScript', 'Laravel', 'Vue'],
            'categories' => ['Web Development', 'Mobile Development'],
            'min_budget' => 100,
            'max_budget' => 5000,
            'budget_type' => 'both',
            'bidding_times' => [
                ['start' => '09:00', 'end' => '17:00'],
            ],
            'max_bid_amount' => 500,
            'cover_letter_template' => 'Hello! I am interested in this project and have the required skills to complete it successfully.',
            'auto_bid_enabled' => false,
        ]);

        $this->command->info('✅ Seeder completed successfully!');
        $this->command->newLine();
        $this->command->info('📋 Login Credentials:');
        $this->command->newLine();
        $this->command->info('👤 Admin Account:');
        $this->command->line('   Email: admin@freelancer.com');
        $this->command->line('   Password: admin123');
        $this->command->newLine();
        $this->command->info('👤 User Account:');
        $this->command->line('   Email: user@freelancer.com');
        $this->command->line('   Password: user123');
        $this->command->newLine();
        $this->command->info('💳 Subscriptions Created:');
        $this->command->line('   - Basic Plan: $9.99 (50 bids)');
        $this->command->line('   - Pro Plan: $29.99 (200 bids)');
        $this->command->line('   - Premium Plan: $49.99 (500 bids)');
        $this->command->newLine();
        $this->command->info('✨ User has been assigned Pro Plan with 5 bids used (195 remaining)');
    }
}
