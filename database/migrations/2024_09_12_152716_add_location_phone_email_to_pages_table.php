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
        Schema::table('pages', function (Blueprint $table) {
            $table->string('location')->nullable()->after('page_cover'); // Add location column
            $table->string('phone')->nullable()->after('location'); // Add phone column
            $table->string('email')->nullable()->after('phone'); // Add email column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['location', 'phone', 'email']);
        });
    }
};
