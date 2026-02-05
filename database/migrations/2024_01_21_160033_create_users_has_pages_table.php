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
        Schema::create('users_has_pages', function (Blueprint $table) {
            $table->string('user_id');
            $table->string('page_id');
            $table->timestamps();
            // Make user_id and group_id a composite primary key
            $table->primary(['user_id', 'page_id']);

            $table->foreign('user_id')->references('user_id')->on('users')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('page_id')->references('page_id')->on('pages')
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
        Schema::dropIfExists('users_has_pages');
    }
};
