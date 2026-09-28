<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('hrm_attendance_logs', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index(); $table->unsignedBigInteger('employee_id')->index(); $table->date('attendance_date')->index(); $table->string('punch_type',30); $table->dateTime('punch_time')->index(); $table->string('source',40)->default('manual'); $table->string('device_uid')->nullable(); $table->string('ip_address',80)->nullable(); $table->decimal('latitude',12,8)->nullable(); $table->decimal('longitude',12,8)->nullable(); $table->decimal('match_score',8,4)->nullable(); $table->string('status',30)->default('approved')->index(); $table->text('note')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->timestamps(); }); }
    public function down(): void { Schema::dropIfExists('hrm_attendance_logs'); }
};
