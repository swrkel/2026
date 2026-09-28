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
        Schema::create('daily_vouchers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('daily_vouchers_no');
            $table->unsignedInteger('location_id')->index('location_id');
            $table->date('transaction_date');
            $table->unsignedInteger('operator_id')->index('operator_id');
            $table->unsignedInteger('pump_id')->nullable()->index('pump_id');
            $table->unsignedInteger('customer_id')->index('customer_id');
            $table->decimal('current_outstanding', 15)->nullable();
            $table->decimal('outstanding_pending', 15)->nullable();
            $table->string('voucher_order_number');
            $table->date('voucher_order_date');
            $table->unsignedInteger('vehicle_no')->comment('this is customer_references');
            $table->decimal('total_amount', 15);
            $table->unsignedInteger('created_by');
            $table->string('settlement_no', 255)->nullable();
            $table->boolean('status');
            $table->boolean('is_issue_customer_bill')->default(false);
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
        Schema::dropIfExists('daily_vouchers');
    }
};
