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
        Schema::create('temp_data', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->text('sale_create_data')->nullable();
            $table->text('pos_create_data')->nullable();
            $table->text('sale_return_data')->nullable();
            $table->text('stock_transfer_data')->nullable();
            $table->text('stock_adjustment_data')->nullable();
            $table->text('add_expense_data')->nullable();
            $table->text('add_pos_data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->text('settlement_sw_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_data');
    }
};
