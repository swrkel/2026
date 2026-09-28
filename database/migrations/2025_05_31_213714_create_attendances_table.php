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
        Schema::create('attendances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->integer('employee_id')->index('employee_id');
            $table->integer('leave_category_id')->nullable()->index('leave_category_id');
            $table->date('date');
            $table->boolean('attendance_status')->default(false)->comment('status 1=present 0=absen and 3= onleave');
            $table->time('in_time');
            $table->time('out_time');
            $table->boolean('over_time')->default(false);
            $table->integer('ot_hours')->default(0);
            $table->integer('ot_minutes')->default(0);
            $table->integer('approved_ot_hours')->default(0);
            $table->integer('approved_ot_minutes')->default(0);
            $table->boolean('late_time')->default(false);
            $table->integer('lt_hours')->default(0);
            $table->integer('lt_minutes')->default(0);
            $table->integer('approved_lt_hours')->default(0);
            $table->integer('approved_lt_minutes')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};
