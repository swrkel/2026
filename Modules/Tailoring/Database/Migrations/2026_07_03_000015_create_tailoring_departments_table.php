<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTailoringDepartmentsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('tailoring_departments')) {
            Schema::create('tailoring_departments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('location_id')->nullable()->index();
                $table->string('name');
                $table->string('code')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }
    public function down() { Schema::dropIfExists('tailoring_departments'); }
}
