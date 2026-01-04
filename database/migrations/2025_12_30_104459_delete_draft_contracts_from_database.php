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
        // Delete all contracts with status 'draft'
        DB::statement("DELETE FROM contracts WHERE status = 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore deleted contracts, so this is a no-op
        // In a real scenario, you might want to backup the data before deletion
    }
};
