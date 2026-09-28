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
        Schema::create('airline_suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('airline_suppliers_business_id_foreign');
            $table->integer('opening_balance')->nullable()->default(0);
            $table->enum('type', ['supplier', 'customer', 'both', 'lead']);
            $table->string('supplier_business_name')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('contact_id')->nullable()->index('contact_id');
            $table->string('tax_number')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->text('address_2')->nullable();
            $table->text('address_3')->nullable();
            $table->string('geo_location')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('landmark')->nullable();
            $table->string('mobile')->nullable();
            $table->string('landline')->nullable();
            $table->string('alternate_number')->nullable();
            $table->integer('pay_term_number')->nullable();
            $table->enum('pay_term_type', ['days', 'months'])->nullable();
            $table->decimal('credit_limit', 22, 4)->nullable();
            $table->unsignedInteger('created_by')->index('airline_suppliers_created_by_foreign');
            $table->integer('converted_by')->nullable();
            $table->dateTime('converted_on')->nullable();
            $table->integer('total_rp')->default(0)->comment('rp is the short form of reward points');
            $table->integer('total_rp_used')->default(0)->comment('rp is the short form of reward points');
            $table->integer('total_rp_expired')->default(0)->comment('rp is the short form of reward points');
            $table->boolean('is_default')->default(false);
            $table->integer('customer_group_id')->nullable()->index('customer_group_id');
            $table->string('crm_source', 255)->nullable()->index();
            $table->string('crm_life_stage', 255)->nullable()->index();
            $table->unsignedInteger('supplier_group_id')->nullable()->index('supplier_group_id');
            $table->string('custom_field1')->nullable();
            $table->string('custom_field2')->nullable();
            $table->string('custom_field3')->nullable();
            $table->string('custom_field4')->nullable();
            $table->boolean('sell_over_limit')->default(false);
            $table->boolean('sol_without_approval')->default(false);
            $table->boolean('sol_with_approval')->default(false);
            $table->decimal('over_limit_percentage', 10)->default(0);
            $table->integer('temp_approved_user')->nullable();
            $table->unsignedInteger('temp_requested_by')->nullable();
            $table->boolean('is_property')->default(false);
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
            $table->string('image')->nullable();
            $table->string('signature')->nullable();
            $table->text('payment_account')->nullable();
            $table->text('security_deposit_asset_account')->nullable();
            $table->text('security_deposit_liability_account')->nullable();
            $table->boolean('is_payee')->default(false);
            $table->string('nic_number', 20)->nullable();
            $table->string('contact_status')->default('active');
            $table->text('notification_contacts');
            $table->integer('should_notify')->default(0);
            $table->integer('user_id')->nullable()->index('user_id');
            $table->timestamp('contact_transaction_date')->nullable();
            $table->string('credit_notification', 60)->nullable();
            $table->string('vat_number', 200)->nullable();
            $table->string('sub_customers', 200)->nullable();
            $table->integer('sub_customer')->default(0);

            $table->index(['business_id'], 'business_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('airline_suppliers');
    }
};
