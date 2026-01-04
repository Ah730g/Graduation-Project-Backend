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
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->enum('duration_type', ['day', 'week', 'month', 'test_10s', 'test_30s'])->nullable()->after('message');
            $table->integer('duration_multiplier')->nullable()->after('duration_type');
            $table->date('requested_start_date')->nullable()->after('duration_multiplier');
            $table->date('requested_end_date')->nullable()->after('requested_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            $table->dropColumn(['duration_type', 'duration_multiplier', 'requested_start_date', 'requested_end_date']);
        });
    }
};

