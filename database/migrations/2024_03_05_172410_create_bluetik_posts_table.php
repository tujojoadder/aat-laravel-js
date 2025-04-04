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
        Schema::create('bluetik_posts', function (Blueprint $table) {
            $table->string('bluetik_post_id')->primary();
            $table->string('author_id');
            $table->string('post_type'); /* user,group,page,iaccount */
            $table->string('request_for'); /* profile_picture,cover_picture */
            $table->foreign('author_id')->references('user_id')->on('users')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->timestamp('posted_at')->nullable(); // Add this line
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
        Schema::dropIfExists('bluetik_posts');
    }
};
