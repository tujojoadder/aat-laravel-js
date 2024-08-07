<?php

use App\Models\Hadith;
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
        Schema::create('hadith', function (Blueprint $table) {
            $table->string('hadith_id')->primary();
            $table->string('has_ques')->default('no');
            $table->string('book')->default('bukhari');
            $table->string('language')->default('bangla');
            $table->text('hadith');
            
           
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
        Schema::dropIfExists('hadith');
    }
};
