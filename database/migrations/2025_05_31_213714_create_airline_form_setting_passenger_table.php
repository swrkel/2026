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
        Schema::create('airline_form_setting_passenger', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('created_by')->nullable();
            $table->integer('business_id')->nullable();
            $table->integer('name')->nullable();
            $table->integer('passenger_mobile_no')->nullable();
            $table->integer('frequent_flyer_no')->nullable();
            $table->integer('additional_service')->nullable();
            $table->integer('passport_number')->nullable();
            $table->integer('select_passport_image')->nullable();
            $table->integer('child')->nullable();
            $table->integer('additional_service_amount')->nullable();
            $table->integer('vat_number')->nullable();
            $table->integer('need_to_send_sms')->nullable();
            $table->integer('price')->nullable();
            $table->integer('passenger_type')->nullable();
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
        Schema::dropIfExists('airline_form_setting_passenger');
    }
};
