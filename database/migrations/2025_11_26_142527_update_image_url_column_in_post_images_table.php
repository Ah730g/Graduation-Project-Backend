<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('post_images', function (Blueprint $table) {
        $table->text('Image_URL')->change();
    });
}

public function down()
{
    Schema::table('post_images', function (Blueprint $table) {
        $table->string('Image_URL', 255)->change();
    });
}

};
