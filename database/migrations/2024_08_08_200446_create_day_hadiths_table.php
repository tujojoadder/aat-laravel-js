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
        Schema::create('day_hadiths', function (Blueprint $table) {
            $table->string('day_hadith_id')->primary();
            $table->string('hadith_id');
            $table->string('user_id');

            $table->foreign('hadith_id')->references('hadith_id')->on('hadith')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')
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
        Schema::dropIfExists('day_hadiths');
    }
};
