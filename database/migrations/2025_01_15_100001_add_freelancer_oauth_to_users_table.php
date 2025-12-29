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
        Schema::table('users', function (Blueprint $table) {
            $table->string('freelancer_user_id')->nullable()->after('role');
            $table->text('freelancer_access_token')->nullable()->after('freelancer_user_id');
            $table->text('freelancer_refresh_token')->nullable()->after('freelancer_access_token');
            $table->timestamp('freelancer_token_expires_at')->nullable()->after('freelancer_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'freelancer_user_id',
                'freelancer_access_token',
                'freelancer_refresh_token',
                'freelancer_token_expires_at',
            ]);
        });
    }
};


