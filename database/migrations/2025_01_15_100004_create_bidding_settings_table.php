<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bidding_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('countries')->nullable(); // Array of country codes
            $table->json('technologies')->nullable(); // Array of technology names
            $table->json('categories')->nullable(); // Array of category IDs
            $table->decimal('min_budget', 10, 2)->nullable();
            $table->decimal('max_budget', 10, 2)->nullable();
            $table->string('budget_type')->nullable(); // fixed, hourly, both
            $table->json('bidding_times')->nullable(); // Array of time ranges
            $table->integer('max_bid_amount')->nullable();
            $table->text('cover_letter_template')->nullable();
            $table->boolean('auto_bid_enabled')->default(false);
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bidding_settings');
    }
};


