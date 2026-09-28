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
        Schema::create('essentials_employee_payment_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('liability_account_id')->nullable();
            $table->string('name', 255);
            $table->tinyInteger('status')->nullable()->default(1);
            $table->integer('employee_ledger')->default(1);
            $table->dateTime('datetime_entered')->nullable();
            $table->string('remarks', 255)->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('business_id')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_employee_payment_settings');
    }
};
