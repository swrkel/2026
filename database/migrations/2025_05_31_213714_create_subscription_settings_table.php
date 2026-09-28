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
        Schema::create('subscription_settings', function (Blueprint $table) {
            $table->integer('id', true);
            $table->timestamp('transaction_date')->useCurrentOnUpdate()->useCurrent();
            $table->string('product', 200);
            $table->decimal('base_amount', 15, 5);
            $table->string('subscription_cycle', 200);
            $table->integer('business_id')->index('business_id');
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
        Schema::dropIfExists('subscription_settings');
    }
};
