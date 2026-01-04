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
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('document_type');
            $table->string('document_number')->nullable()->after('full_name');
            $table->date('date_of_birth')->nullable()->after('document_number');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->string('nationality')->nullable()->after('place_of_birth');
            $table->date('issue_date')->nullable()->after('nationality');
            $table->date('expiry_date')->nullable()->after('issue_date');
            $table->text('address')->nullable()->after('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('identity_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'document_number',
                'date_of_birth',
                'place_of_birth',
                'nationality',
                'issue_date',
                'expiry_date',
                'address',
            ]);
        });
    }
};
