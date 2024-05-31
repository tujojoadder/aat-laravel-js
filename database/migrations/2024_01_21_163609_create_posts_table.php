
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
        Schema::create('posts', function (Blueprint $table) {
            $table->string('post_id')->primary();
            $table->string('author_id');
            $table->string('group_id')->nullable();
            $table->string('page_id')->nullable();
            $table->string('iaccount_id')->nullable();
            $table->text('timeline_ids')->nullable(); // Nullable timeline_ids column
            $table->enum('audience', ['public', 'private','only_me']);
            $table->integer('reported_count')->default(0);
            $table->string('post_type')->default('general');
            $table->timestamps();

            $table->foreign('author_id')->references('user_id')->on('users')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('group_id')->references('group_id')->on('groups')
            ->onDelete('cascade')
            ->onUpdate('cascade');
            $table->foreign('page_id')->references('page_id')->on('pages')
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
        Schema::dropIfExists('posts');
    }
};


