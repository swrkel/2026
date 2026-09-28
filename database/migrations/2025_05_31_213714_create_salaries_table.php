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
        Schema::create('salaries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->integer('employee_id')->index('employee_id');
            $table->integer('grade_id')->nullable()->index('grade_id');
            $table->longText('comment')->nullable();
            $table->double('total_payable')->nullable();
            $table->double('total_cost_company')->nullable();
            $table->double('total_deduction')->nullable();
            $table->double('total_statutory');
            $table->longText('component')->nullable();
            $table->enum('type', ['Monthly', 'Hourly']);
            $table->decimal('hourly_salary', 13)->nullable();
            $table->year('salary_year');
            $table->string('salary_month', 5);
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
        Schema::dropIfExists('salaries');
    }
};
