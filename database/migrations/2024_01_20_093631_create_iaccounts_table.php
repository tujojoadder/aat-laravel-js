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
        Schema::create('iaccounts', function (Blueprint $table) {
            $table->string('iaccount_id')->primary();
            $table->string('identifier')->unique();
            $table->string('iaccount_name');
            $table->string('iaccount_creator');
            $table->string('iaccount_picture');
            $table->string('iaccount_cover');
            $table->integer('reported_count')->default(0);
            $table->timestamps();  
            
            
            $table->foreign('iaccount_creator')->references('user_id')->on('users')
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
        Schema::dropIfExists('iaccounts');
    }
};








