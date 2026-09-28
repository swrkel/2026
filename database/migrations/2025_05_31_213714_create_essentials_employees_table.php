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
        Schema::create('essentials_employees', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100);
            $table->integer('employee_no');
            $table->timestamp('date_joined')->nullable();
            $table->integer('designation');
            $table->integer('department');
            $table->decimal('salary', 10, 3);
            $table->timestamp('probation_ends')->nullable();
            $table->integer('business_id')->index('business_id');
            $table->integer('created_by')->nullable();
            $table->timestamp('dob')->default('0000-00-00 00:00:00');
            $table->string('nic');
            $table->string('address');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->default('0000-00-00 00:00:00');
            $table->integer('sales_target_applicable');
            $table->decimal('employee_ob', 10)->nullable();
            $table->integer('transaction_id')->nullable()->index('transaction_id');
            $table->text('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_employees');
    }
};
