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
        Schema::create('question_answer_sets', function (Blueprint $table) {
            $table->string('question_id')->primary();
            $table->text('question');
            $table->string('wrong_ans');
            $table->string('correct_ans');
            $table->string('story_id');
            $table->timestamps();


            $table->foreign('story_id')->references('story_id')->on('stories')->onDelete('cascade')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('question_answer_sets');
    }
};
