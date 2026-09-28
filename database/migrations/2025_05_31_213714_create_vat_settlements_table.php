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
        Schema::create('vat_settlements', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('settlement_no', 255)->default('');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('transaction_date');
            $table->date('finish_date')->nullable();
            $table->unsignedInteger('pump_operator_id')->index('pump_operator_id');
            $table->boolean('bulk_store_product');
            $table->text('note')->nullable();
            $table->text('cash_denomination')->nullable();
            $table->string('total_amount', 25)->default('0');
            $table->boolean('status')->default(true)->comment('1: Active , 0: Inactive');
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
        Schema::dropIfExists('vat_settlements');
    }
};
