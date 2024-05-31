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
        Schema::create('groups', function (Blueprint $table) {
            $table->string('group_id')->primary();
            $table->string('identifier')->unique();
            $table->string('group_name');
            $table->text('group_details');
            $table->string('group_creator');
            $table->longText('group_admins');
            $table->string('group_picture');
            $table->string('group_cover');
            $table->enum('audience', ['public', 'private']);
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
        Schema::dropIfExists('groups');
    }
};
