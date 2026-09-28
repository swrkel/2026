<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTailoringEmployeeProductivitiesTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tailoring_employee_productivities')) {
            Schema::create('tailoring_employee_productivities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->unsignedInteger('employee_id')->nullable()->index();
                $table->date('work_date')->index();
                $table->unsignedInteger('assigned_jobs')->default(0);
                $table->unsignedInteger('completed_jobs')->default(0);
                $table->unsignedInteger('rework_jobs')->default(0);
                $table->decimal('efficiency_percent', 8, 2)->default(0);
                $table->decimal('incentive_amount', 22, 4)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down() { Schema::dropIfExists('tailoring_employee_productivities'); }
}
