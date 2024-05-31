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
        Schema::create('user_answers', function (Blueprint $table) {
            $table->string('user_answer_id')->primary();
            $table->string('user_id');
            $table->string('story_id');
            $table->string('question_id');
            $table->string('selected_ans');
            $table->boolean('is_correct');
            $table->integer('earned_points')->default(0);

            $table->foreign('user_id')->references('user_id')->on('users')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('story_id')->references('story_id')->on('stories')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('question_id')->references('question_id')->on('question_answer_sets')
            ->onDelete('cascade')
            ->onUpdate('cascade');

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
        Schema::dropIfExists('user_answers');
    }
};
