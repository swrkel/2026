<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 150)->nullable();
            $table->string('nic_no', 50)->nullable()->index();
            $table->string('passport_no', 50)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('mobile', 30)->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable()->index();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('postal_code', 30)->nullable();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('designation_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('shift_id')->nullable()->index();
            $table->date('joining_date')->nullable();
            $table->string('employment_type', 50)->default('full_time');
            $table->string('employee_status', 50)->default('active')->index();
            $table->decimal('basic_salary', 20, 4)->nullable();
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_branch', 150)->nullable();
            $table->string('bank_account_no', 100)->nullable();
            $table->string('epf_no', 100)->nullable();
            $table->string('etf_no', 100)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hr_employee_emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('contact_name', 150)->nullable();
            $table->string('relationship', 100)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_employee_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->index();
            $table->string('document_type', 100)->nullable();
            $table->string('document_title', 150)->nullable();
            $table->string('document_no', 100)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_documents');
        Schema::dropIfExists('hr_employee_emergency_contacts');
        Schema::dropIfExists('hr_employees');
    }
};
