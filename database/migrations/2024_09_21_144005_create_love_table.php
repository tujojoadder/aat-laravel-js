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
        Schema::create('love', function (Blueprint $table) {
            $table->string('love_id')->primary();
            $table->string('love_on_id'); // Change the data type as needed
            $table->string('love_on_type');
            $table->string('love_by_id');
            $table->timestamps();
            // Add the following line to create the foreign key relationship
            $table->foreign('love_by_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['love_on_id', 'love_on_type']); // Add index for polymorphic relationship
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('love');
    }
};
