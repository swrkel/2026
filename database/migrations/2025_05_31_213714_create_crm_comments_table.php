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
        Schema::create('crm_comments', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('crm_id')->index('crm_id');
            $table->integer('user_id')->nullable()->index('user_id');
            $table->date('comment_date');
            $table->string('comments');
            $table->date('next_follow_up');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_comments');
    }
};
