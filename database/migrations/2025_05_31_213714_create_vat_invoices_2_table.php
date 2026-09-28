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
        Schema::create('vat_invoices_2', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id');
            $table->date('date');
            $table->string('sale_type', 30)->default('Product');
            $table->string('customer_bill_no', 50);
            $table->integer('location_id');
            $table->integer('customer_id');
            $table->integer('reference_id')->nullable();
            $table->decimal('total_amount', 15);
            $table->decimal('tax_amount', 15, 3)->default(0);
            $table->decimal('discount_amount', 15, 3)->default(0);
            $table->decimal('outstanding_amount', 15);
            $table->string('credit_limit', 20)->nullable();
            $table->string('prefix', 20);
            $table->integer('created_by');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->integer('sub_customer')->nullable();
            $table->string('invoice_to', 20)->default('customer');
            $table->date('supplied_on')->nullable();
            $table->decimal('unit_vat_rate_total', 10)->default(0);
            $table->string('name', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->decimal('price_adjustment', 22, 5)->nullable()->default(0);
            $table->integer('route_operation_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vat_invoices_2');
    }
};
