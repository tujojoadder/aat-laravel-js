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
        Schema::create('current_story', function (Blueprint $table) {
            $table->string('current_story_id')->primary();
            $table->string('user_id');
            $table->string('story_id');
            $table->boolean('reading')->default(false); /* is user read the quiz story or not */
            
            $table->timestamps();
            /* we are not giving hadith id as a forien key becase we will can use anything else insted of hadith   */
            $table->foreign('user_id')->references('user_id')->on('users')
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
        Schema::dropIfExists('current_story');
    }
};
