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
        Schema::create('essentials_to_dos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->index('business_id');
            $table->text('task');
            $table->dateTime('date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('task_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('estimated_hours')->nullable();
            $table->string('priority')->nullable()->index();
            $table->integer('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['business_id']);
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
        Schema::dropIfExists('essentials_to_dos');
    }
};
