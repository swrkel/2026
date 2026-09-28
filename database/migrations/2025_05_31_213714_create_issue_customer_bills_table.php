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
        Schema::create('issue_customer_bills', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->date('date');
            $table->string('customer_bill_no', 50);
            $table->integer('location_id')->index('location_id');
            $table->integer('pump_id')->index('pump_id');
            $table->integer('operator_id')->index('operator_id');
            $table->integer('customer_id')->index('customer_id');
            $table->integer('reference_id')->index('reference_id');
            $table->string('order_bill_no', 50);
            $table->decimal('total_amount', 15);
            $table->boolean('show_in_daily_voucher')->default(false)->comment('1 for yes, 0 for no');
            $table->integer('created_by');
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
        Schema::dropIfExists('issue_customer_bills');
    }
};
