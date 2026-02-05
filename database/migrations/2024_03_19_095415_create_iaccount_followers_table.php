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
        Schema::create('iaccount_followers', function (Blueprint $table) {
            $table->string('iaccount_followers_id')->primary();
            $table->string('iaccount_id');
            $table->string('follower_id')->nullable();
    
            $table->timestamps();

            $table->foreign('iaccount_id')->references('iaccount_id')->on('iaccounts')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('follower_id')->references('user_id')->on('users')
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
        Schema::dropIfExists('iaccount_followers');
    }
};
