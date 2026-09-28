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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('month', 50);
            $table->unsignedInteger('employee_id')->index('employee_id');
            $table->unsignedInteger('department_id')->index('department_id');
            $table->decimal('gross_salary', 13)->default(0);
            $table->decimal('deduction', 13)->default(0);
            $table->integer('total_hour')->nullable();
            $table->decimal('net_salary', 13)->default(0);
            $table->longText('award');
            $table->decimal('fine_deduction', 13)->default(0);
            $table->decimal('bonus', 13)->default(0);
            $table->string('payment_method', 100);
            $table->text('note')->nullable();
            $table->decimal('net_payment', 13)->default(0);
            $table->enum('type', ['Monthly', 'Hourly']);
            $table->string('date_range', 100)->nullable();
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
        Schema::dropIfExists('payrolls');
    }
};
