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
        Schema::create('customer_statement_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('statement_id')->index('statement_id')->comment('id form customer statement table');
            $table->date('date');
            $table->string('location', 256);
            $table->string('invoice_no')->nullable();
            $table->string('customer_reference')->nullable();
            $table->string('order_no')->nullable();
            $table->date('order_date')->nullable();
            $table->string('product')->nullable();
            $table->decimal('unit_price', 15)->nullable();
            $table->decimal('qty', 15)->nullable();
            $table->string('vehicle_number', 100)->nullable();
            $table->string('route_name', 100)->nullable();
            $table->decimal('invoice_amount', 15)->nullable();
            $table->decimal('due_amount', 15)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->string('type', 20)->default('transaction');
            $table->integer('transaction_id')->nullable()->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_statement_details');
    }
};
