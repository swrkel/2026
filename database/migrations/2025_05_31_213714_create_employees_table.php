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
        Schema::create('employees', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('employee_id')->index('employee_id');
            $table->integer('business_id')->index('business_id');
            $table->integer('location_id')->index('location_id');
            $table->string('employee_number', 50);
            $table->string('username');
            $table->string('password');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('marital_status', ['Singel', 'Married']);
            $table->string('date_of_birth', 100);
            $table->string('country', 100);
            $table->enum('blood_group', ['A', 'B', 'AB', 'O', 'Do Not Know']);
            $table->string('id_number', 100);
            $table->unsignedInteger('religious')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->string('photo', 100);
            $table->date('joined_date');
            $table->date('probation_end_date')->nullable();
            $table->date('date_of_permanency')->nullable();
            $table->longText('personal_attachment');
            $table->longText('contact_details');
            $table->longText('emergency_contact');
            $table->longText('dependents');
            $table->integer('department_id')->nullable()->index('department_id');
            $table->integer('job_title')->nullable();
            $table->integer('category')->nullable();
            $table->integer('employment_status')->nullable();
            $table->integer('work_shift')->nullable();
            $table->text('deposit');
            $table->timestamp('date_time')->useCurrent();
            $table->tinyInteger('termination')->default(1);
            $table->text('termination_note');
            $table->boolean('active')->default(true);
            $table->tinyInteger('soft_delete')->default(0);
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
        Schema::dropIfExists('employees');
    }
};
