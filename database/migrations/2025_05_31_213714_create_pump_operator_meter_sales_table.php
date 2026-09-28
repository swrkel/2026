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
        Schema::create('pump_operator_meter_sales', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->timestamp('date_time')->useCurrentOnUpdate()->useCurrent();
            $table->integer('pump_operator_id')->index('pump_operator_id');
            $table->decimal('amount', 10, 3);
            $table->decimal('deposited', 10, 3);
            $table->decimal('balance', 10, 3);
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->integer('shift_id')->nullable();
            $table->string('collection_form_no', 255)->nullable();
            $table->integer('p_o_payment_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pump_operator_meter_sales');
    }
};
