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
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->dateTime('date_and_time');
            $table->string('task_id')->index('task_id');
            $table->string('task_heading');
            $table->text('task_details');
            $table->text('task_footer')->nullable();
            $table->integer('group_id')->nullable()->index('group_id');
            $table->unsignedInteger('priority_id')->index('priority_id');
            $table->string('priority_name')->nullable();
            $table->text('members');
            $table->enum('status', ['new', 'in_progress', 'on_hold', 'completed'])->default('new');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('estimated_hours', 10);
            $table->text('reminder');
            $table->string('color', 50);
            $table->integer('created_by');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
