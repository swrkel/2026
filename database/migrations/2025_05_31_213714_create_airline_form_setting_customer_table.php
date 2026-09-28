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
        Schema::create('airline_form_setting_customer', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('created_by')->nullable();
            $table->integer('business_id')->nullable();
            $table->integer('location')->nullable();
            $table->integer('name')->nullable();
            $table->integer('vat_no')->nullable();
            $table->integer('credit_limit')->nullable();
            $table->integer('mobile')->nullable();
            $table->integer('address')->nullable();
            $table->integer('state')->nullable();
            $table->integer('tax_number')->nullable();
            $table->integer('confirm_password')->nullable();
            $table->integer('sub_customer')->nullable();
            $table->integer('passport_nic_no')->nullable();
            $table->integer('need_to_send_sms')->nullable();
            $table->integer('opening_balance')->nullable();
            $table->integer('transaction_date')->nullable();
            $table->integer('landline')->nullable();
            $table->integer('address_line_2')->nullable();
            $table->integer('country')->nullable();
            $table->integer('pay_term')->nullable();
            $table->integer('email')->nullable();
            $table->integer('vehicle_no')->nullable();
            $table->integer('passport_nic_image')->nullable();
            $table->integer('credit_notification_type')->nullable();
            $table->integer('customer_group')->nullable();
            $table->integer('add_more_mobile_numbers')->nullable();
            $table->integer('assigned_to')->nullable();
            $table->integer('city')->nullable();
            $table->text('landmark')->nullable();
            $table->integer('password')->nullable();
            $table->integer('alternate_contact_number')->nullable();
            $table->text('address_line_3')->nullable();
            $table->integer('signature')->nullable();
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
        Schema::dropIfExists('airline_form_setting_customer');
    }
};
