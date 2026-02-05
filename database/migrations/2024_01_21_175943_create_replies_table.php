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
    Schema::create('replies', function (Blueprint $table) {
        $table->string('reply_id')->primary();
        $table->string('comment_id');
        $table->string('parent_reply_id')->nullable();
        $table->string('replied_by_id');
        $table->text('reply_text');
        $table->integer('reported_count')->default(0);
        $table->timestamps();
    });

    Schema::table('replies', function (Blueprint $table) {
        $table->foreign('comment_id')->references('comment_id')->on('comments')
            ->onDelete('cascade')
            ->onUpdate('cascade');

        $table->foreign('parent_reply_id')->references('reply_id')->on('replies')
            ->onDelete('cascade')
            ->onUpdate('cascade');

        $table->foreign('replied_by_id')->references('user_id')->on('users')
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
        Schema::dropIfExists('replies');
    }
};
