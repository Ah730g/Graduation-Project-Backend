<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Drop old foreign keys and columns
            $table->dropForeign(['user_id']);
            $table->dropForeign(['post_id']);
            
            // Add new columns for two-sided rating system
            $table->foreignId('contract_id')->nullable()->after('id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('rater_user_id')->after('contract_id')->constrained('users')->onDelete('cascade')->comment('User who is giving the rating');
            $table->foreignId('rated_user_id')->after('rater_user_id')->constrained('users')->onDelete('cascade')->comment('User who is being rated');
            
            // Update status enum to include hidden/revealed states
            DB::statement("ALTER TABLE reviews MODIFY COLUMN status ENUM('hidden', 'revealed', 'removed') DEFAULT 'hidden'");
            
            // Add revealed_at timestamp
            $table->timestamp('revealed_at')->nullable()->after('status');
            
            // Keep post_id for backward compatibility but make it nullable
            $table->foreignId('post_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Drop new columns
            $table->dropForeign(['contract_id']);
            $table->dropForeign(['rater_user_id']);
            $table->dropForeign(['rated_user_id']);
            $table->dropColumn(['contract_id', 'rater_user_id', 'rated_user_id', 'revealed_at']);
            
            // Restore old enum
            DB::statement("ALTER TABLE reviews MODIFY COLUMN status ENUM('active', 'removed') DEFAULT 'active'");
            
            // Restore foreign keys
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreignId('post_id')->nullable(false)->change();
        });
    }
};

