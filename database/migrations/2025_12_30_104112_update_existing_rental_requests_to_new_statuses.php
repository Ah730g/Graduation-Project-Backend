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
        // First, ensure the ENUM includes all new statuses (in case previous migration didn't run)
        DB::statement("ALTER TABLE rental_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'contract_signing', 'cancelled', 'awaiting_payment', 'payment_received', 'payment_confirmed', 'contract_signed') DEFAULT 'pending'");

        // Update rental requests based on their contract status
        // If contract status is 'pending' -> 'payment_received'
        DB::statement("
            UPDATE rental_requests rr
            INNER JOIN contracts c ON c.rental_request_id = rr.id
            SET rr.status = 'payment_received'
            WHERE c.status = 'pending' AND rr.status IN ('approved', 'contract_signing')
        ");

        // If contract status is 'pending_signing' -> 'payment_confirmed'
        DB::statement("
            UPDATE rental_requests rr
            INNER JOIN contracts c ON c.rental_request_id = rr.id
            SET rr.status = 'payment_confirmed'
            WHERE c.status = 'pending_signing' AND rr.status IN ('approved', 'contract_signing')
        ");

        // If contract status is 'signed' or 'active' -> 'contract_signed'
        DB::statement("
            UPDATE rental_requests rr
            INNER JOIN contracts c ON c.rental_request_id = rr.id
            SET rr.status = 'contract_signed'
            WHERE c.status IN ('signed', 'active') AND rr.status IN ('approved', 'contract_signing', 'payment_received', 'payment_confirmed')
        ");

        // If rental request is 'approved' but has no contract yet, keep it as 'approved' (awaiting payment)
        // This is already the case, so no update needed
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous statuses
        // Convert new statuses back to 'approved' or 'contract_signing'
        DB::statement("
            UPDATE rental_requests
            SET status = CASE
                WHEN status = 'payment_received' THEN 'approved'
                WHEN status = 'payment_confirmed' THEN 'contract_signing'
                WHEN status = 'contract_signed' THEN 'contract_signing'
                ELSE status
            END
            WHERE status IN ('payment_received', 'payment_confirmed', 'contract_signed')
        ");
    }
};
