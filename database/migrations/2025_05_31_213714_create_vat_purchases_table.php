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
        Schema::create('vat_purchases', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->string('purchase_no', 60);
            $table->string('invoice_no', 60);
            $table->timestamp('invoice_date')->useCurrentOnUpdate()->useCurrent();
            $table->integer('vat_invoice');
            $table->integer('supplier_id');
            $table->string('purchase_status', 60);
            $table->decimal('discount_amount', 15, 3);
            $table->decimal('vat_amount', 15, 3);
            $table->decimal('sub_total', 15, 3);
            $table->decimal('total_amount', 15, 3);
            $table->string('payment_status', 60);
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
        Schema::dropIfExists('vat_purchases');
    }
};
