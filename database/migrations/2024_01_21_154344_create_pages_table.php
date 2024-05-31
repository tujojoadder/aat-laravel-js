<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->string('page_id')->primary();
            $table->string('identifier')->unique();
            $table->string('page_name');
            $table->text('page_details');
            $table->string('page_creator');
            $table->longText('page_admins');
            $table->string('page_picture');
            $table->string('page_cover');
            $table->integer('reported_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
};
