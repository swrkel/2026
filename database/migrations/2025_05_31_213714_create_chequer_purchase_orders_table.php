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
        Schema::create('chequer_purchase_orders', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('business_id');
            $table->string('po_number', 499);
            $table->string('purchase_bill_no', 255)->default('');
            $table->integer('total_items');
            $table->string('discount_amount', 255);
            $table->string('discount_percentage', 30);
            $table->string('subTotal', 255);
            $table->string('tax', 255);
            $table->string('grandTotal', 255);
            $table->integer('supplier_id');
            $table->string('supplier_name', 499);
            $table->string('supplier_email', 499);
            $table->longText('supplier_address');
            $table->string('supplier_tel', 255);
            $table->string('supplier_fax', 255);
            $table->integer('outlet_id');
            $table->string('outlet_name', 499);
            $table->longText('outlet_address');
            $table->string('outlet_contact', 499);
            $table->integer('warehouse_id');
            $table->integer('payment_method');
            $table->string('payment_method_name', 255);
            $table->string('cheque_number', 499);
            $table->string('gift_card', 90);
            $table->string('card_number', 90);
            $table->string('paid_amt', 255);
            $table->string('return_change', 255);
            $table->date('po_date');
            $table->string('attachment_file', 499);
            $table->longText('note');
            $table->integer('created_user_id');
            $table->dateTime('created_datetime');
            $table->integer('updated_user_id');
            $table->dateTime('updated_datetime');
            $table->integer('received_user_id');
            $table->dateTime('received_datetime');
            $table->date('transation_date');
            $table->integer('status');
            $table->integer('vt_status')->comment('0: Debit Payment, 1: Completed ');
            $table->integer('refund_status')->comment('1: Full Refund, 2: Partial Refund ');
            $table->integer('pid');
            $table->string('fuel_tank_id', 255);
            $table->string('supplier_order_no', 50)->nullable();
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
        Schema::dropIfExists('chequer_purchase_orders');
    }
};
