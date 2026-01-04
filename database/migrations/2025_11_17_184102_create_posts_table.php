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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained("users");
            $table->string("Title");
            $table->double("Price");
            $table->string("Address");
            $table->text("Description");
            $table->string("City");
            $table->integer("Bedrooms");
            $table->integer("Bathrooms");
            $table->string("Latitude");
            $table->string("Longitude");
            $table->enum("Type",["rent","buy"]);
            $table->foreignId("porperty_id")->constrained("porperties");
            $table->enum("Utilities_Policy",["owner","tenant","share"]);
            $table->boolean("Pet_Policy");
            $table->string("Income_Policy");
            $table->integer("Total_Size");
            $table->integer("Bus");
            $table->integer("Resturant");
            $table->integer("School");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
