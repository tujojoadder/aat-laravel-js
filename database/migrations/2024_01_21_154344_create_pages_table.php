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
            $table->string('page_name');
            $table->text('page_details');
            $table->string('page_creator');
            $table->longText('page_admins');
            $table->integer('reported_count')->default(0);
            $table->string('identifier')->unique();   
            $table->string('page_picture');
            $table->string('page_cover');
            $table->timestamps();

            $table->foreign('page_creator')->references('user_id')->on('users')
            ->onDelete('cascade')
            ->onUpdate('cascade');

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
