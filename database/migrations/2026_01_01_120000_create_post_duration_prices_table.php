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
        Schema::create('post_duration_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->enum('duration_type', ['day', 'week', 'month', 'year'])->comment('Duration type offered by owner');
            $table->decimal('price', 10, 2)->comment('Price for this duration type');
            $table->timestamps();
            
            // Ensure one price per duration type per post
            $table->unique(['post_id', 'duration_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_duration_prices');
    }
};

