<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('hrm_employees', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('location_id')->nullable()->index(); $table->string('employee_code',50)->index(); $table->string('first_name'); $table->string('last_name')->nullable(); $table->string('display_name')->index(); $table->string('nic_no',80)->nullable()->index(); $table->string('mobile',40)->nullable()->index(); $table->string('email')->nullable(); $table->unsignedBigInteger('department_id')->nullable()->index(); $table->unsignedBigInteger('designation_id')->nullable()->index(); $table->date('joining_date')->nullable(); $table->string('employment_type',50)->nullable(); $table->string('status',30)->default('active')->index(); $table->string('photo_path')->nullable(); $table->timestamps(); $table->unique(['business_id','employee_code'],'hrm_emp_business_code_unique'); }); }
    public function down(): void { Schema::dropIfExists('hrm_employees'); }
};
