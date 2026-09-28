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
        Schema::create('employee_awards', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('award_name', 200);
            $table->integer('employee_id')->index('employee_id');
            $table->integer('department_id')->index('department_id');
            $table->string('gift_item', 100);
            $table->decimal('award_amount', 13);
            $table->string('award_month', 10);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_awards');
    }
};
