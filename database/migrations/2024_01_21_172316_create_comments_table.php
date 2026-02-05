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
        Schema::create('comments', function (Blueprint $table) {
            $table->string('comment_id')->primary();
            $table->string('post_id');
            $table->string('commenter_id');
            $table->text('comment_text');
            $table->integer('reported_count')->default(0);
            $table->timestamps();

            $table->foreign('post_id')->references('post_id')->on('posts')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('commenter_id')->references('user_id')->on('users')
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
        Schema::dropIfExists('comments');
    }
};
