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
        Schema::create('daily_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('collection_no', 255);
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('customer_id')->index('customer_id');
            $table->integer('pump_operator_id')->nullable()->index('pump_operator_id');
            $table->timestamp('date')->nullable();
            $table->unsignedInteger('card_type')->nullable();
            $table->string('card_number', 100)->nullable();
            $table->decimal('amount', 15, 6);
            $table->integer('customer_payment_id')->nullable()->index('customer_payment_id');
            $table->text('note')->nullable();
            $table->string('slip_no', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->integer('used_status')->nullable();
            $table->string('settlement_no', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_cards');
    }
};
