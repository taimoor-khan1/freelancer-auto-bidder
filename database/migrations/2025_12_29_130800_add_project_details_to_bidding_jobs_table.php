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
        Schema::table('bidding_jobs', function (Blueprint $table) {
            $table->string('project_title')->nullable()->after('freelancer_project_id');
            $table->text('project_description')->nullable()->after('project_title');
            $table->decimal('project_budget', 10, 2)->nullable()->after('project_description');
            $table->string('project_url')->nullable()->after('project_budget');
            $table->timestamp('project_posted_at')->nullable()->after('project_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bidding_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'project_title',
                'project_description',
                'project_budget',
                'project_url',
                'project_posted_at',
            ]);
        });
    }
};
