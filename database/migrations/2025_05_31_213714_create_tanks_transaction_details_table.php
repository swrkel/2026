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
        Schema::create('tanks_transaction_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('settlement_id', 255)->default('')->index('settlement_id');
            $table->string('purchase_order_no', 255)->default('');
            $table->string('tank_id', 255)->default('')->index('tank_id');
            $table->string('location_id', 255)->default('')->index('location_id');
            $table->string('tank_qty', 255)->default('0');
            $table->string('total_tank_balance', 255)->default('0');
            $table->string('available_tank_qty', 255)->default('0');
            $table->string('purchase_qty', 255)->default('0');
            $table->string('sold_qty', 255)->default('0');
            $table->string('created_by', 255)->default('');
            $table->string('status', 255)->default('0');
            $table->date('transaction_date');
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
        Schema::dropIfExists('tanks_transaction_details');
    }
};
