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
        Schema::create('daily_collections', function (Blueprint $table) {
            $table->increments('id');
            $table->string('shift_number', 50)->nullable();
            $table->integer('daily_shift')->nullable();
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('collection_form_no', 255)->default('');
            $table->unsignedInteger('pump_operator_id')->index('pump_operator_id');
            $table->unsignedInteger('settlement_id')->nullable()->index('settlement_id');
            $table->dateTime('settlement_date')->nullable();
            $table->decimal('balance_collection', 15)->default(0);
            $table->decimal('current_amount', 15)->default(0);
            $table->unsignedInteger('location_id')->index('location_id');
            $table->unsignedInteger('created_by');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->unsignedInteger('shift_id')->nullable();
            $table->integer('bring_forward');
            $table->integer('customer_bill_id')->nullable()->index('customer_bill_id');
            $table->string('denom_qty', 255)->nullable();
            $table->integer('added_to_account')->nullable();
            $table->string('shift_no', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('daily_collections');
    }
};
