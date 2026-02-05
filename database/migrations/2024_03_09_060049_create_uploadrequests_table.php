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
        Schema::create('uploadrequests', function (Blueprint $table) {
            $table->string('uploadrequest_id')->primary();
            $table->string('uploadrequest_on_id');
            $table->string('uploadrequest_on_type');/* model */
            $table->string('uploadrequest_by');/* authUser */
            $table->string('photo_url');
            $table->enum('audience', ['public', 'private','only_me']);
            $table->string('type');//'user_profile', 'user_cover', 'page_profile','page_cover','group_profile','group_cover','iaccount_profile','iaccount_cover'
            $table->enum('status', ['pending', 'accepted', 'rejected','canceled'])->default('pending'); //canceled-->>user request second time before that request peocessed then 1st request will be canceled
            $table->timestamps();
            $table->index(['uploadrequest_on_id', 'uploadrequest_on_type']);
      
            $table->foreign('uploadrequest_by')->references('user_id')->on('users')
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
        Schema::dropIfExists('uploadrequests');
    }
};
