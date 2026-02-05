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
        Schema::create('reports', function (Blueprint $table) {
            $table->string('report_id')->primary();
            $table->string('report_on_type');
            $table->string('report_on_id'); // Change the data type as needed
            $table->string('report_by_id');
            $table->string('report_type');// Inappropriate Content,Hate Speech,Harassment or Bullying,Spam or Misinformation
           //in cratagory 
           //Inappropriate Content : female in photo ,graphic violence, nudity, or explicit language
            //Hate Speech :  ethnicity, religion, or nationality.
            //Harassment or Bullying : derogatory remarks, threats, or engaging in cyberbullying
            //Spam or Misinformation : misinformation, or promote scams or phishing attempts
            $table->string('report_category');
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
            // Add the following line to create the foreign key relationship
            $table->foreign('report_by_id')->references('user_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->index(['report_on_id', 'report_on_type']); // Add index for polymorphic relationship
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reports');
    }
};
