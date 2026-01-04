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
        Schema::table('contracts', function (Blueprint $table) {
            $table->enum('status', ['draft', 'pending', 'active', 'signed', 'expired', 'cancelled'])->default('draft')->change();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null')->after('rental_request_id');
            $table->text('owner_signature')->nullable()->after('terms');
            $table->text('renter_signature')->nullable()->after('owner_signature');
            $table->timestamp('owner_signed_at')->nullable()->after('renter_signature');
            $table->timestamp('renter_signed_at')->nullable()->after('owner_signed_at');
            $table->boolean('payment_confirmed_by_owner')->default(false)->after('renter_signed_at');
            $table->timestamp('payment_confirmed_at')->nullable()->after('payment_confirmed_by_owner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['payment_id']);
            $table->dropColumn([
                'payment_id',
                'owner_signature',
                'renter_signature',
                'owner_signed_at',
                'renter_signed_at',
                'payment_confirmed_by_owner',
                'payment_confirmed_at'
            ]);
            DB::statement("ALTER TABLE contracts MODIFY COLUMN status ENUM('active', 'expired', 'cancelled') DEFAULT 'active'");
        });
    }
};
