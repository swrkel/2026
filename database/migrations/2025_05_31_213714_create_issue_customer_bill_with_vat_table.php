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
        Schema::create('issue_customer_bill_with_vat', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->date('date');
            $table->string('customer_bill_no', 50);
            $table->string('order_number', 30)->nullable();
            $table->integer('location_id')->index('location_id');
            $table->integer('customer_id')->index('customer_id');
            $table->integer('pump_id')->index('pump_id');
            $table->integer('operator_id')->index('operator_id');
            $table->string('reference_id', 30)->nullable()->index('reference_id');
            $table->decimal('total_amount', 15);
            $table->decimal('tax_amount', 15, 3)->default(0);
            $table->decimal('discount_amount', 15, 3)->default(0);
            $table->decimal('outstanding_amount', 15);
            $table->decimal('new_outstanding_amount', 15);
            $table->string('credit_limit', 20)->nullable();
            $table->string('limit_balance', 20)->nullable();
            $table->string('prefix', 20);
            $table->integer('created_by');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issue_customer_bill_with_vat');
    }
};
