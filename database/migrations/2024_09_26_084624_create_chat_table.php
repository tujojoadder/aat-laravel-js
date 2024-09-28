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
        Schema::create('chat', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('sender_id');  // Sender's user ID
            $table->string('receiver_id');  // Receiver's user ID
            $table->text('message');  // Message content
            $table->timestamps();  // Adds created_at and updated_at timestamps

            // Optional: Foreign key constraints (assuming you have a 'users' table)
            $table->foreign('sender_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chat');
    }
};
