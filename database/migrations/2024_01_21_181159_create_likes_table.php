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
        Schema::create('likes', function (Blueprint $table) {
            $table->string('like_id')->primary();
            $table->string('like_on_id'); // Change the data type as needed
            $table->string('reaction_type'); // Change the data type as needed 
            $table->string('like_on_type');
            $table->string('like_by_id');
            $table->timestamps();
            // Add the following line to create the foreign key relationship
            $table->foreign('like_by_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['like_on_id', 'like_on_type']); // Add index for polymorphic relationship
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('likes');
    }
};
