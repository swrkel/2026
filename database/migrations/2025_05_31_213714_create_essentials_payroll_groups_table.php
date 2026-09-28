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
        Schema::create('essentials_payroll_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('business_id')->index('business_id');
            $table->integer('location_id')->nullable()->index('location_id')->comment('payroll for work location');
            $table->string('name');
            $table->string('status');
            $table->string('payment_status')->default('due');
            $table->decimal('gross_total', 22, 4)->default(0);
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_payroll_groups');
    }
};
