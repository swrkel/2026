<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business', function (Blueprint $table) {
            $table->id();
            $table->string('name', 256);
            $table->string('company_number')->nullable();
            $table->unsignedInteger('currency_id')->index('business_currency_id_foreign');
            $table->integer('is_manged_stock_enable')->default(0)->comment('0 = disable, 1 = enable manage stock in product page');
            $table->text('business_categories')->nullable();
            $table->boolean('show_for_customers')->default(false);
            $table->integer('day_end')->default(0);
            $table->integer('day_end_enable')->nullable();
            $table->date('start_date')->nullable();
            $table->string('tax_number_1', 100)->nullable();
            $table->string('tax_label_1', 10)->nullable();
            $table->string('tax_number_2', 100)->nullable();
            $table->string('tax_label_2', 10)->nullable();
            $table->unsignedInteger('default_sales_tax')->nullable()->index('business_default_sales_tax_foreign');
            $table->double('default_profit_percent', 5, 2)->default(0);
            $table->unsignedInteger('owner_id')->index('business_owner_id_foreign');
            $table->string('time_zone')->default('Asia/Kolkata');
            $table->tinyInteger('fy_start_month')->default(1);
            $table->enum('accounting_method', ['fifo', 'lifo', 'avco'])->default('fifo');
            $table->decimal('default_sales_discount', 5)->nullable();
            $table->enum('sell_price_tax', ['includes', 'excludes'])->default('includes');
            $table->string('logo')->nullable();
            $table->string('sku_prefix')->nullable();
            $table->boolean('enable_product_expiry')->default(false);
            $table->enum('expiry_type', ['add_expiry', 'add_manufacturing'])->default('add_expiry');
            $table->enum('on_product_expiry', ['keep_selling', 'stop_selling', 'auto_delete'])->default('keep_selling');
            $table->integer('stop_selling_before')->comment('Stop selling expied item n days before expiry');
            $table->date('sale_import_date')->nullable();
            $table->date('purchase_import_date')->nullable();
            $table->boolean('enable_tooltip')->default(true);
            $table->boolean('purchase_in_diff_currency')->default(false)->comment('Allow purchase to be in different currency then the business currency');
            $table->unsignedInteger('purchase_currency_id')->nullable()->index('purchase_currency_id');
            $table->decimal('p_exchange_rate', 20, 3)->default(1);
            $table->unsignedInteger('transaction_edit_days')->default(30);
            $table->unsignedInteger('stock_expiry_alert_days')->default(30);
            $table->text('keyboard_shortcuts')->nullable();
            $table->text('pos_settings')->nullable();
            $table->text('manufacturing_settings')->nullable();
            $table->boolean('enable_brand')->default(true);
            $table->boolean('enable_category')->default(true);
            $table->boolean('enable_sub_category')->default(true);
            $table->boolean('enable_price_tax')->default(true);
            $table->boolean('enable_purchase_status')->nullable()->default(true);
            $table->boolean('enable_lot_number')->default(false);
            $table->integer('default_unit')->nullable();
            $table->boolean('enable_sub_units')->default(false);
            $table->boolean('enable_racks')->default(false);
            $table->boolean('enable_row')->default(false);
            $table->boolean('enable_position')->default(false);
            $table->boolean('show_avai_qty_in_qr_catalogue')->default(false);
            $table->boolean('show_in_catalogue_page')->default(false);
            $table->boolean('enable_editing_product_from_purchase')->default(true);
            $table->enum('sales_cmsn_agnt', ['logged_in_user', 'user', 'cmsn_agnt'])->nullable();
            $table->boolean('item_addition_method')->default(true);
            $table->boolean('enable_inline_tax')->default(true);
            $table->enum('currency_symbol_placement', ['before', 'after'])->default('before');
            $table->text('enabled_modules')->nullable();
            $table->string('date_format')->default('m/d/Y');
            $table->enum('time_format', ['12', '24'])->default('24');
            $table->text('ref_no_prefixes')->nullable();
            $table->char('theme_color', 20)->nullable();
            $table->integer('created_by')->nullable();
            $table->text('crm_settings')->nullable();
            $table->boolean('enable_rp')->default(false)->comment('rp is the short form of reward points');
            $table->boolean('enable_free_qty')->default(false);
            $table->boolean('popup_load_save_data')->default(false);
            $table->string('rp_name')->nullable()->comment('rp is the short form of reward points');
            $table->decimal('amount_for_unit_rp', 22, 4)->default(1)->comment('rp is the short form of reward points');
            $table->decimal('min_order_total_for_rp', 22, 4)->default(1)->comment('rp is the short form of reward points');
            $table->integer('max_rp_per_order')->nullable()->comment('rp is the short form of reward points');
            $table->decimal('redeem_amount_per_unit_rp', 22, 4)->default(1)->comment('rp is the short form of reward points');
            $table->decimal('min_order_total_for_redeem', 22, 4)->default(1)->comment('rp is the short form of reward points');
            $table->integer('min_redeem_point')->nullable()->comment('rp is the short form of reward points');
            $table->integer('max_redeem_point')->nullable()->comment('rp is the short form of reward points');
            $table->integer('rp_expiry_period')->nullable()->comment('rp is the short form of reward points');
            $table->enum('rp_expiry_type', ['month', 'year'])->default('year')->comment('rp is the short form of reward points');
            $table->text('email_settings')->nullable();
            $table->text('sms_settings')->nullable();
            $table->text('custom_labels')->nullable();
            $table->text('common_settings')->nullable();
            $table->boolean('enable_line_discount')->default(false);
            $table->string('currency_precision', 15)->nullable()->default('2');
            $table->string('reg_no')->nullable();
            $table->string('quantity_precision', 15)->nullable()->default('2');
            $table->string('search_product_settings')->nullable();
            $table->integer('default_store')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_patient')->default(false);
            $table->boolean('is_hospital')->nullable()->default(false);
            $table->boolean('is_pharmacy')->default(false);
            $table->boolean('is_laboratory')->default(false);
            $table->integer('patient_details_id')->nullable()->index('patient_details_id');
            $table->enum('background_showing_type', ['only_background_image', 'background_image_and_logo'])->nullable();
            $table->text('background_image')->nullable();
            $table->text('ref_no_starting_number')->nullable();
            $table->text('repair_settings')->nullable();
            $table->text('auto_repair_settings');
            $table->text('visitor_qr_data')->nullable();
            $table->text('contact_fields')->nullable();
            $table->boolean('trial_used')->default(false);
            $table->tinyInteger('customer_interest_deduct_option')->default(0);
            $table->integer('customer_interest_deduct')->default(0);
            $table->boolean('service_addition_method')->nullable()->default(false);
            $table->integer('duplicate_orders_allowed')->default(0);
            $table->text('essentials_settings')->nullable();
            $table->text('asset_settings');
            $table->integer('font_size');
            $table->string('font_family', 60);
            $table->longText('hms_settings')->nullable();
            $table->text('weighing_scale_setting')->comment('used to store the configuration of weighing scale');
            $table->boolean('sms_non_delivery')->nullable()->default(false);

            $table->index(['currency_id'], 'currency_id');
            $table->index(['owner_id'], 'owner_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business');
    }
};
