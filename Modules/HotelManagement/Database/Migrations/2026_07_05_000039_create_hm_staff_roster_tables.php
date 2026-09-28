<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hm_staff_roles')) {
            Schema::create('hm_staff_roles', function (Blueprint $table) {
                $table->bigIncrements('id'); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('business_location_id')->nullable();
                $table->string('role_name',120); $table->string('department',100)->nullable(); $table->decimal('standard_hours',8,2)->default(0); $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
                $table->index(['business_id','department']); $table->index('business_location_id');
            });
        }
        if (!Schema::hasTable('hm_staff_members')) {
            Schema::create('hm_staff_members', function (Blueprint $table) {
                $table->bigIncrements('id'); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('business_location_id')->nullable();
                $table->string('employee_no',60)->nullable(); $table->string('name',160); $table->string('mobile',40)->nullable(); $table->string('email',160)->nullable();
                $table->string('department',100)->nullable(); $table->unsignedBigInteger('role_id')->nullable(); $table->string('status',40)->default('active');
                $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
                $table->index(['business_id','status']); $table->index('business_location_id'); $table->index('employee_no');
            });
        }
        if (!Schema::hasTable('hm_staff_roster_shifts')) {
            Schema::create('hm_staff_roster_shifts', function (Blueprint $table) {
                $table->bigIncrements('id'); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('business_location_id')->nullable();
                $table->string('shift_no',60)->nullable(); $table->unsignedBigInteger('staff_id')->nullable(); $table->date('shift_date'); $table->time('start_time')->nullable(); $table->time('end_time')->nullable();
                $table->string('department',100)->nullable(); $table->string('station',120)->nullable(); $table->string('status',40)->default('planned'); $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
                $table->index(['business_id','shift_date']); $table->index('business_location_id'); $table->index(['staff_id','shift_date']); $table->index('status');
            });
        }
        if (!Schema::hasTable('hm_staff_attendance_logs')) {
            Schema::create('hm_staff_attendance_logs', function (Blueprint $table) {
                $table->bigIncrements('id'); $table->unsignedBigInteger('business_id'); $table->unsignedBigInteger('business_location_id')->nullable();
                $table->unsignedBigInteger('staff_id'); $table->date('attendance_date'); $table->time('clock_in')->nullable(); $table->time('clock_out')->nullable(); $table->string('status',40)->default('present'); $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps();
                $table->index(['business_id','attendance_date']); $table->index('business_location_id'); $table->index(['staff_id','attendance_date']); $table->index('status');
            });
        }
    }
    public function down(): void
    {
        Schema::dropIfExists('hm_staff_attendance_logs'); Schema::dropIfExists('hm_staff_roster_shifts'); Schema::dropIfExists('hm_staff_members'); Schema::dropIfExists('hm_staff_roles');
    }
};
