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
        // Set cancelled_by_admin = true for all contracts that were cancelled before this migration
        // This applies to contracts that were cancelled by admin (status = 'cancelled')
        DB::statement("UPDATE contracts SET cancelled_by_admin = true WHERE status = 'cancelled' AND cancelled_by_admin = false");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset cancelled_by_admin to false for contracts that were updated
        // Note: This is a best-effort reversal, as we can't distinguish which contracts
        // were actually cancelled by admin vs by users
        DB::statement("UPDATE contracts SET cancelled_by_admin = false WHERE status = 'cancelled'");
    }
};
