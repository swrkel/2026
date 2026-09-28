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
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->unsignedInteger('location_id')->nullable()->index('location_id');
            $table->unsignedInteger('res_table_id')->nullable()->index('res_table_id')->comment('fields to restaurant module');
            $table->unsignedInteger('res_waiter_id')->nullable()->index('res_waiter_id')->comment('fields to restaurant module');
            $table->enum('res_order_status', ['received', 'cooked', 'served'])->nullable();
            $table->string('type', 255)->nullable()->index('type');
            $table->integer('store_id')->nullable()->index('store_id');
            $table->string('sub_type', 20)->nullable()->index();
            $table->enum('status', ['received', 'pending', 'ordered', 'draft', 'final', 'order', 'transit', 'issued', 'outright_purchase', 'mortgaged', 'confirmed', 'cancelled'])->nullable()->default('received')->index('status');
            $table->decimal('cheque_return_charges', 15, 6)->default(0);
            $table->enum('need_to_reserve', ['yes', 'no'])->nullable();
            $table->boolean('is_credit_sale')->default(false);
            $table->integer('credit_sale_id')->nullable()->index('credit_sale_id');
            $table->boolean('is_over_limit_credit_sale')->default(false);
            $table->decimal('customer_limit', 15, 6)->default(0);
            $table->decimal('over_limit_amount', 15, 6)->default(0);
            $table->unsignedInteger('approved_user')->nullable();
            $table->unsignedInteger('requested_by')->nullable();
            $table->string('order_no')->nullable();
            $table->string('order_date')->nullable();
            $table->string('customer_ref')->nullable();
            $table->boolean('is_quotation')->default(false);
            $table->boolean('is_customer_order')->default(false);
            $table->string('order_status')->nullable();
            $table->enum('payment_status', ['paid', 'due', 'partial', 'pending', 'price_later'])->nullable();
            $table->boolean('price_later')->default(false);
            $table->enum('adjustment_type', ['normal', 'abnormal'])->nullable();
            $table->string('stock_adjustment_type', 20)->nullable();
            $table->unsignedInteger('contact_id')->nullable()->index('contact_id');
            $table->unsignedInteger('pump_operator_id')->nullable()->index('pump_operator_id');
            $table->integer('customer_group_id')->nullable()->index('customer_group_id')->comment('used to add customer group while selling');
            $table->string('invoice_no')->nullable()->index('invoice_no');
            $table->string('purchase_entry_no')->nullable();
            $table->string('deed_no')->nullable();
            $table->date('deed_date')->nullable();
            $table->string('ref_no')->nullable();
            $table->string('subscription_no')->nullable();
            $table->dateTime('transaction_date')->index();
            $table->date('invoice_date')->nullable();
            $table->decimal('total_before_tax', 22, 6)->nullable();
            $table->unsignedInteger('tax_id')->nullable()->index('tax_id');
            $table->decimal('tax_amount', 22, 6)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_amount', 22, 6)->nullable()->default(0);
            $table->integer('rp_redeemed')->default(0)->comment('rp is the short form of reward points');
            $table->decimal('rp_redeemed_amount', 22, 6)->default(0)->comment('rp is the short form of reward 

points');
            $table->string('shipping_details')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_status')->nullable();
            $table->string('delivered_to')->nullable();
            $table->decimal('shipping_charges', 22, 6)->default(0);
            $table->decimal('price_adjustment', 22, 6)->default(0);
            $table->text('additional_notes')->nullable();
            $table->text('staff_note')->nullable();
            $table->decimal('final_total', 22, 6)->default(0);
            $table->decimal('amount_paid_from_advance', 15, 6)->default(0);
            $table->unsignedInteger('expense_category_id')->nullable()->index('expense_category_id');
            $table->unsignedInteger('expense_for')->nullable()->index('transactions_expense_for_foreign');
            $table->unsignedInteger('fleet_id')->nullable()->index('fleet_id');
            $table->unsignedInteger('property_id')->nullable()->index('property_id');
            $table->unsignedInteger('expense_account')->nullable();
            $table->unsignedInteger('controller_account')->nullable();
            $table->integer('commission_agent')->nullable();
            $table->string('document')->nullable();
            $table->boolean('is_direct_sale')->default(false);
            $table->boolean('is_suspend')->default(false);
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->decimal('total_amount_recovered', 22, 6)->nullable()->comment('Used for 

stock adjustment.');
            $table->integer('transfer_parent_id')->nullable()->index('transfer_parent_id');
            $table->integer('return_parent_id')->nullable()->index('return_parent_id');
            $table->boolean('is_pos_return')->default(false);
            $table->string('pos_invoice_return')->nullable();
            $table->integer('opening_stock_product_id')->nullable()->index('opening_stock_product_id');
            $table->unsignedInteger('created_by')->index();
            $table->boolean('crm_is_order_request')->nullable()->default(false);
            $table->integer('import_batch')->nullable();
            $table->dateTime('import_time')->nullable();
            $table->integer('types_of_service_id')->nullable()->index('types_of_service_id');
            $table->decimal('packing_charge', 22, 6)->nullable();
            $table->enum('packing_charge_type', ['fixed', 'percent'])->nullable();
            $table->text('service_custom_field_1')->nullable();
            $table->text('service_custom_field_2')->nullable();
            $table->text('service_custom_field_3')->nullable();
            $table->text('service_custom_field_4')->nullable();
            $table->integer('mfg_parent_production_purchase_id')->nullable()->index('mfg_parent_production_purchase_id');
            $table->decimal('mfg_wasted_units', 20, 6)->nullable();
            $table->decimal('mfg_production_cost', 20, 6)->default(0);
            $table->boolean('mfg_is_final')->default(false);
            $table->boolean('is_created_from_api')->default(false);
            $table->integer('rp_earned')->default(0)->comment('rp is the short form of reward points');
            $table->integer('from_store')->nullable();
            $table->text('order_addresses')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->double('recur_interval', 22, 4)->nullable();
            $table->enum('recur_interval_type', ['days', 'months', 'years'])->nullable();
            $table->integer('recur_repetitions')->nullable();
            $table->dateTime('recur_stopped_on')->nullable();
            $table->integer('recur_parent_id')->nullable()->index('recur_parent_id');
            $table->string('invoice_token')->nullable();
            $table->integer('pay_term_number')->nullable();
            $table->enum('pay_term_type', ['days', 'months'])->nullable();
            $table->integer('selling_price_group_id')->nullable()->index('selling_price_group_id');
            $table->boolean('is_duplicate')->default(false);
            $table->boolean('is_settlement')->default(false)->index('is_settlement');
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->boolean('imported')->default(false);
            $table->decimal('advance_remaining', 15, 6)->default(0);
            $table->softDeletes();
            $table->integer('repair_brand_id')->nullable()->index('repair_brand_id');
            $table->text('repair_checklist')->nullable();
            $table->dateTime('repair_completed_on')->nullable();
            $table->text('repair_defects')->nullable();
            $table->integer('repair_device_id')->nullable()->index('repair_device_id');
            $table->dateTime('repair_due_date')->nullable();
            $table->integer('repair_model_id')->nullable()->index('repair_model_id');
            $table->string('repair_security_pattern')->nullable();
            $table->string('repair_security_pwd')->nullable();
            $table->string('repair_serial_no')->nullable();
            $table->integer('repair_status_id')->nullable()->index('repair_status_id');
            $table->boolean('repair_updates_notif')->default(false);
            $table->integer('repair_warranty_id')->nullable()->index('repair_warranty_id');
            $table->string('subscription_repeat_on')->nullable();
            $table->unsignedInteger('repair_job_sheet_id')->nullable()->index('repair_job_sheet_id');
            $table->integer('finance_option_id')->nullable()->index('finance_option_id')->comment('property sell finance option id reference');
            $table->string('balance_quantity', 255)->default('0');
            $table->integer('From_Account')->default(1);
            $table->integer('To_Account')->default(1);
            $table->boolean('is_export')->default(false);
            $table->unsignedInteger('discount_acc_id')->nullable()->index('discount_acc_id')->comment('add discount account  id when discount added');
            $table->integer('reprint_no')->default(0);
            $table->integer('is_post_dated_cheques')->default(0);
            $table->integer('parent_transaction_id')->nullable()->index('parent_transaction_id');
            $table->integer('is_vat')->default(0);
            $table->text('transaction_note')->nullable();
            $table->integer('transaction_updated')->default(1);
            $table->integer('overpayment_setoff')->nullable()->default(0);
            $table->timestamp('new_deleted_at')->nullable();
            $table->integer('new_deleted_by')->nullable();
            $table->unsignedInteger('acc_sub_type_id')->nullable()->index('acc_sub_type_id')->comment('add account sub type id when discount added');
            $table->unsignedInteger('acc_type_id')->nullable()->index('acc_type_id')->comment('add account type id when discount added');
            $table->string('sale_ref', 50)->nullable();
            $table->integer('order_tax_id')->nullable();
            $table->dateTime('hms_booking_arrival_date_time')->nullable();
            $table->dateTime('hms_booking_departure_date_time')->nullable();
            $table->integer('hms_coupon_id')->nullable();
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->unsignedInteger('hms_transaction_class_id')->nullable();
            $table->string('mobile_no', 255)->nullable();
            $table->string('whatsapp_no', 255)->nullable();
            $table->string('shift_number', 200)->nullable();

            $table->index(['business_id', 'transaction_date'], 'idx_transactions_business_date');
            $table->index(['status', 'is_settlement', 'business_id'], 'idx_transactions_status_settlement');
            $table->index(['invoice_no'], 'invoice_no_2');
            $table->index(['invoice_no'], 'invoice_no_3');
            $table->index(['is_settlement'], 'is_settlement_2');
            $table->index(['is_settlement'], 'is_settlement_3');
            $table->index(['status'], 'status_2');
            $table->index(['status'], 'status_3');
            $table->index(['business_id'], 'transactions');
            $table->index(['business_id']);
            $table->index(['contact_id']);
            $table->index(['expense_category_id']);
            $table->index(['location_id']);
            $table->index(['return_parent_id']);
            $table->index(['tax_id'], 'transactions_tax_id_foreign');
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
