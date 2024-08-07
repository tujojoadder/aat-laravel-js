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
        Schema::create('users', function (Blueprint $table) {
            $table->string('user_id')->primary();
            $table->string('profile_picture');
            $table->string('user_fname');
            $table->string('user_lname');
            $table->string('email');
            $table->string('password');
            $table->enum('gender', ['male', 'female', 'others']);
            $table->enum('privacy_setting',['public', 'locked'])->default('public');
            $table->integer('total_quiz_point')->default(0);
            $table->boolean('blueticks')->default(false); // Adding blueticks column with boolean data type
            $table->string('identifier')->unique();




            $table->string('cover_photo');

            $table->date('birthdate');
            $table->integer('reported_count')->default(0);
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
        Schema::dropIfExists('users');
    }
};
