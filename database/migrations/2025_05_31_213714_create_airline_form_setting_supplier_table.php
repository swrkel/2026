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
        Schema::create('airline_form_setting_supplier', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('created_by')->nullable();
            $table->integer('business_id')->nullable();
            $table->integer('type')->nullable();
            $table->integer('tax_number')->nullable();
            $table->integer('transaction_date')->nullable();
            $table->integer('mobile')->nullable();
            $table->integer('address')->nullable();
            $table->integer('country')->nullable();
            $table->integer('custom_field_1')->nullable();
            $table->integer('custom_field_2')->nullable();
            $table->integer('custom_field_3')->nullable();
            $table->integer('custom_field_4')->nullable();
            $table->integer('name')->nullable();
            $table->integer('opening_balance')->nullable();
            $table->integer('supplier_group')->nullable();
            $table->integer('alternate_contact_number')->nullable();
            $table->integer('city')->nullable();
            $table->integer('landmark')->nullable();
            $table->integer('contact_id')->nullable();
            $table->integer('pay_term')->nullable();
            $table->integer('email')->nullable();
            $table->integer('landline')->nullable();
            $table->integer('state')->nullable();
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
        Schema::dropIfExists('airline_form_setting_supplier');
    }
};
