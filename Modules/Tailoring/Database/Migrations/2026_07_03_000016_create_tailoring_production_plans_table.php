<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTailoringProductionPlansTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tailoring_production_plans')) {
            Schema::create('tailoring_production_plans', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->date('plan_date')->index();
                $table->string('plan_type', 20)->default('daily');
                $table->unsignedInteger('department_id')->nullable()->index();
                $table->unsignedInteger('assigned_user_id')->nullable()->index();
                $table->unsignedInteger('planned_qty')->default(0);
                $table->unsignedInteger('completed_qty')->default(0);
                $table->string('priority', 20)->default('normal');
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down() { Schema::dropIfExists('tailoring_production_plans'); }
}
