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
        Schema::create('abouts', function (Blueprint $table) {
            $table->string('about_id')->primary(); 
            $table->string('user_id');
            $table->string('location')->nullable();
            $table->enum('relationship_status', ['single', 'married', 'divorced']);
            $table->string('work')->nullable();
            $table->string('education')->nullable();
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
        Schema::dropIfExists('abouts');
    }
};
