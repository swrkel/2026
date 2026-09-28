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
        Schema::create('mpcs_9a_form_settings', function (Blueprint $table) {
            $table->increments('id')->index('id');
            $table->unsignedInteger('business_id');
            $table->date('date')->nullable();
            $table->string('starting_number', 200)->nullable();
            $table->string('ref_pre_form_number', 200)->nullable();
            $table->decimal('total_sale_to_pre', 15)->nullable();
            $table->decimal('pre_day_cash_sale', 15)->nullable();
            $table->decimal('pre_day_card_sale', 15)->nullable();
            $table->decimal('pre_day_credit_sale', 15)->nullable();
            $table->decimal('pre_day_cash', 15)->nullable();
            $table->decimal('pre_day_cheques', 15)->nullable();
            $table->decimal('pre_day_total', 15)->nullable();
            $table->decimal('pre_day_balance', 15)->nullable();
            $table->decimal('pre_day_grand_total', 15)->nullable();
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
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
        Schema::dropIfExists('mpcs_9a_form_settings');
    }
};
