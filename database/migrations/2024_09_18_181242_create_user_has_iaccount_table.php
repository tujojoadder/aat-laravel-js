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
        Schema::create('users_has_iaccount', function (Blueprint $table) {
            $table->string('user_id');
            $table->string('iaccount_id');
            $table->timestamps();
            // Make user_id and group_id a composite primary key
            $table->primary(['user_id', 'iaccount_id']);

            $table->foreign('user_id')->references('user_id')->on('users')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('iaccount_id')->references('iaccount_id')->on('iaccounts')
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
        Schema::dropIfExists('user_has_iaccount');
    }
};
