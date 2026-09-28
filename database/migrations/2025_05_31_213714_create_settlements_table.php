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
        Schema::create('settlements', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('settlement_no', 255)->default('')->index('settlement_no');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->date('transaction_date');
            $table->date('finish_date')->nullable();
            $table->unsignedInteger('location_id')->index('location_id');
            $table->unsignedInteger('pump_operator_id')->index('pump_operator_id');
            $table->boolean('bulk_store_product');
            $table->string('work_shift');
            $table->text('note')->nullable();
            $table->text('cash_denomination')->nullable();
            $table->string('total_amount', 25)->default('0');
            $table->boolean('status')->default(true)->comment('1: Active , 0: Inactive');
            $table->integer('is_edit')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['settlement_no'], 'settlement_no_2');
            $table->index(['settlement_no'], 'settlement_no_3');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settlements');
    }
};
