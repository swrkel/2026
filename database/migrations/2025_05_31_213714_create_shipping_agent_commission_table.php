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
        Schema::create('shipping_agent_commission', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('agent_id')->index('agent_id');
            $table->integer('business_id')->index('business_id');
            $table->integer('shipment_id')->index('shipment_id');
            $table->integer('transaction_id')->index('transaction_id');
            $table->decimal('amount', 15, 5);
            $table->timestamp('transaction_date')->useCurrentOnUpdate()->useCurrent();
            $table->integer('created_by');
            $table->timestamp('updated_at')->default('0000-00-00 00:00:00');
            $table->timestamp('created_at')->default('0000-00-00 00:00:00');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_agent_commission');
    }
};
