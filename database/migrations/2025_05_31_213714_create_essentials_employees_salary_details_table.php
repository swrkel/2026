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
        Schema::create('essentials_employees_salary_details', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('employee_id')->index('employee_id');
            $table->decimal('current_salary', 10, 3);
            $table->decimal('new_salary', 10, 3);
            $table->integer('created_by');
            $table->timestamp('applicable_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_employees_salary_details');
    }
};
