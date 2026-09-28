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
        Schema::create('shipping_agent_ledger', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('business_id')->index('business_id');
            $table->integer('transaction_id')->nullable()->index('transaction_id');
            $table->integer('agent_id')->index('agent_id');
            $table->integer('shipment_id')->index('shipment_id');
            $table->string('type');
            $table->string('sub_type')->nullable();
            $table->decimal('amount', 22, 5);
            $table->timestamp('operation_date')->useCurrentOnUpdate()->useCurrent();
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
        Schema::dropIfExists('shipping_agent_ledger');
    }
};
