<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index(); $table->string('code',30)->nullable(); $table->string('name',120); $table->text('description')->nullable(); $table->unsignedBigInteger('manager_employee_id')->nullable(); $table->boolean('is_active')->default(true); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps(); $table->unique(['business_id','code']); });
        Schema::create('hr_designations', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('department_id')->nullable()->index(); $table->string('code',30)->nullable(); $table->string('name',120); $table->string('grade_level',50)->nullable(); $table->text('description')->nullable(); $table->boolean('is_active')->default(true); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps(); $table->unique(['business_id','code']); });
        Schema::create('hr_shifts', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index(); $table->string('code',30)->nullable(); $table->string('name',120); $table->time('start_time'); $table->time('end_time'); $table->unsignedSmallInteger('break_minutes')->default(0); $table->unsignedSmallInteger('grace_in_minutes')->default(0); $table->unsignedSmallInteger('grace_out_minutes')->default(0); $table->unsignedSmallInteger('overtime_after_minutes')->default(0); $table->boolean('is_night_shift')->default(false); $table->boolean('is_active')->default(true); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps(); $table->unique(['business_id','code']); });
        Schema::create('hr_holidays', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index(); $table->date('holiday_date')->index(); $table->string('name',150); $table->string('type',50)->nullable(); $table->boolean('is_paid')->default(true); $table->text('notes')->nullable(); $table->boolean('is_active')->default(true); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps(); });
        Schema::create('hr_weekly_offs', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index(); $table->string('name',120); $table->unsignedTinyInteger('day_of_week'); $table->boolean('is_active')->default(true); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps(); });
    }
    public function down(): void
    {
        Schema::dropIfExists('hr_weekly_offs'); Schema::dropIfExists('hr_holidays'); Schema::dropIfExists('hr_shifts'); Schema::dropIfExists('hr_designations'); Schema::dropIfExists('hr_departments');
    }
};
