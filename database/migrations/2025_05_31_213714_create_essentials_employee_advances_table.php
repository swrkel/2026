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
        Schema::create('essentials_employee_advances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('employee_id')->nullable()->index('employee_id');
            $table->double('amount')->nullable()->default(0);
            $table->double('amount_paid')->nullable()->default(0);
            $table->integer('payment_status')->nullable()->default(1);
            $table->integer('employee_ledger_applicable')->default(1);
            $table->dateTime('payment_datetime')->nullable();
            $table->string('check_no', 255)->nullable();
            $table->string('reference_no', 255)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->date('salary_period_start')->nullable();
            $table->date('salary_period_end')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->dateTime('datetime_entered')->nullable();
            $table->integer('payment_type_id')->nullable();
            $table->integer('account_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_employee_advances');
    }
};
