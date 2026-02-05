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
        Schema::create('user_follows', function (Blueprint $table) {
            $table->string('user_follows_id')->primary();
            $table->string('follower_id'); // User who follows
            $table->string('following_id'); // User being followed
            $table->timestamps();

            // Foreign Keys
            $table->foreign('follower_id')->references('user_id')->on('users')
            ->onUpdate('cascade')
            ->onDelete('cascade');
            
            $table->foreign('following_id')->references('user_id')->on('users')
            ->onUpdate('cascade')
            ->onDelete('cascade');;

            // Ensure a user can't follow the same person twice
            $table->unique(['follower_id', 'following_id']);
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_follows');
    }
};
