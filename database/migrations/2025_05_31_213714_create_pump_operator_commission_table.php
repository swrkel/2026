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
        Schema::create('pump_operator_commission', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('pump_operator_id')->index('pump_operator_id');
            $table->integer('meter_sale_id')->index('meter_sale_id');
            $table->timestamp('transaction_date')->useCurrentOnUpdate()->useCurrent();
            $table->decimal('amount', 15, 3);
            $table->string('type', 50);
            $table->decimal('value', 15, 3);
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
        Schema::dropIfExists('pump_operator_commission');
    }
};
