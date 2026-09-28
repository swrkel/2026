<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::create('hrm_face_profiles', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('business_id')->nullable()->index(); $table->unsignedBigInteger('employee_id')->index(); $table->string('provider',80)->default('browser-face-api'); $table->string('face_template_hash',128)->nullable(); $table->json('template_payload')->nullable(); $table->dateTime('registered_at')->nullable(); $table->unsignedBigInteger('registered_by')->nullable(); $table->string('status',30)->default('active')->index(); $table->timestamps(); $table->unique(['business_id','employee_id'],'hrm_face_business_employee_unique'); }); }
    public function down(): void { Schema::dropIfExists('hrm_face_profiles'); }
};
