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
        Schema::create('essentials_todo_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->text('comment');
            $table->integer('task_id')->index();
            $table->integer('comment_by')->index();
            $table->timestamps();

            $table->index(['task_id'], 'task_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_todo_comments');
    }
};
