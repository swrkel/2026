<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pdirectnew_settings')) {
            Schema::create('pdirectnew_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('setting_key', 120);
                $table->longText('setting_value')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'location_id', 'setting_key'], 'pdn_settings_scope_key_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_number_sequences')) {
            Schema::create('pdirectnew_number_sequences', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->default(0)->index();
                $table->string('sequence_type', 80);
                $table->string('prefix', 40)->nullable();
                $table->unsignedBigInteger('next_number')->default(1);
                $table->unsignedTinyInteger('padding')->default(5);
                $table->timestamps();
                $table->unique(['business_id', 'location_id', 'sequence_type'], 'pdn_sequence_scope_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_operators')) {
            Schema::create('pdirectnew_operators', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('source_operator_id')->nullable();
                $table->string('operator_no', 80);
                $table->string('name', 190);
                $table->text('address')->nullable();
                $table->string('mobile', 50)->nullable();
                $table->string('landline', 50)->nullable();
                $table->date('dob')->nullable();
                $table->string('nic', 80)->nullable();
                $table->string('email', 190)->nullable();
                $table->string('username', 100)->nullable();
                $table->string('passcode_hash', 255)->nullable();
                $table->decimal('opening_balance', 22, 4)->default(0);
                $table->string('commission_type', 30)->default('none');
                $table->decimal('commission_value', 22, 4)->default(0);
                $table->decimal('short_amount', 22, 4)->default(0);
                $table->decimal('excess_amount', 22, 4)->default(0);
                $table->date('transaction_date')->nullable();
                $table->boolean('is_default')->default(false);
                $table->boolean('can_fullscreen')->default(false);
                $table->boolean('hide_in_direct_settlement_if_pending_shifts')->default(false);
                $table->string('status', 30)->default('active')->index();
                $table->boolean('can_login')->default(false);
                $table->boolean('is_active')->default(true)->index();
                $table->dateTime('source_updated_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'operator_no'], 'pdn_operator_no_uq');
                $table->unique(['business_id', 'source_operator_id'], 'pdn_operator_source_uq');
                $table->index(['business_id', 'location_id', 'is_active', 'name'], 'pdn_operator_list_idx');
            });
        }

        if (!Schema::hasTable('pdirectnew_tanks')) {
            Schema::create('pdirectnew_tanks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->string('tank_no', 80);
                $table->string('name', 190);
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->decimal('capacity', 22, 3)->default(0);
                $table->decimal('current_stock', 22, 3)->default(0);
                $table->decimal('reorder_level', 22, 3)->default(0);
                $table->string('status', 30)->default('active')->index();
                $table->timestamps();
                $table->unique(['business_id', 'location_id', 'tank_no'], 'pdn_tank_no_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_pumps')) {
            Schema::create('pdirectnew_pumps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->string('pump_no', 80);
                $table->string('name', 190);
                $table->unsignedBigInteger('tank_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->string('meter_type', 30)->default('digital');
                $table->decimal('opening_meter', 22, 3)->default(0);
                $table->decimal('current_meter', 22, 3)->default(0);
                $table->string('status', 30)->default('active')->index();
                $table->timestamps();
                $table->unique(['business_id', 'location_id', 'pump_no'], 'pdn_pump_no_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_shifts')) {
            Schema::create('pdirectnew_shifts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->string('shift_no', 80);
                $table->unsignedBigInteger('operator_id')->index();
                $table->dateTime('opened_at')->nullable()->index();
                $table->dateTime('closed_at')->nullable()->index();
                $table->string('status', 30)->default('open')->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('closed_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'shift_no'], 'pdn_shift_no_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_assignments')) {
            Schema::create('pdirectnew_assignments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('shift_id')->index();
                $table->unsignedBigInteger('operator_id')->index();
                $table->unsignedBigInteger('pump_id')->index();
                $table->decimal('opening_meter', 22, 3)->default(0);
                $table->decimal('closing_meter', 22, 3)->nullable();
                $table->decimal('testing_qty', 22, 3)->default(0);
                $table->decimal('sold_qty', 22, 3)->default(0);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->decimal('sales_amount', 22, 4)->default(0);
                $table->string('status', 30)->default('assigned')->index();
                $table->dateTime('received_at')->nullable();
                $table->dateTime('closed_at')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'shift_id', 'pump_id'], 'pdn_assignment_shift_pump_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_meter_readings')) {
            Schema::create('pdirectnew_meter_readings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('shift_id')->nullable()->index();
                $table->unsignedBigInteger('assignment_id')->nullable()->index();
                $table->unsignedBigInteger('pump_id')->index();
                $table->unsignedBigInteger('operator_id')->nullable()->index();
                $table->string('reading_type', 30)->default('current')->index();
                $table->decimal('reading', 22, 3);
                $table->decimal('testing_qty', 22, 3)->default(0);
                $table->dateTime('recorded_at')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_meter_resets')) {
            Schema::create('pdirectnew_meter_resets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('pump_id')->index();
                $table->decimal('old_meter', 22, 3);
                $table->decimal('new_meter', 22, 3);
                $table->string('reason', 500);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('reset_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_settlements')) {
            Schema::create('pdirectnew_settlements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->string('settlement_no', 80);
                $table->unsignedBigInteger('shift_id')->nullable()->index();
                $table->unsignedBigInteger('operator_id')->index();
                $table->date('transaction_date')->index();
                $table->string('work_shift', 80)->nullable();
                $table->string('status', 30)->default('draft')->index();
                $table->text('note')->nullable();
                $table->decimal('expected_total', 22, 4)->default(0);
                $table->decimal('received_total', 22, 4)->default(0);
                $table->decimal('variance', 22, 4)->default(0);
                $table->dateTime('finalized_at')->nullable();
                $table->unsignedBigInteger('finalized_by')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'settlement_no'], 'pdn_settlement_no_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_settlement_meter_sales')) {
            Schema::create('pdirectnew_settlement_meter_sales', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('settlement_id')->index();
                $table->unsignedBigInteger('assignment_id')->nullable()->index();
                $table->unsignedBigInteger('pump_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->decimal('opening_meter', 22, 3)->default(0);
                $table->decimal('closing_meter', 22, 3)->default(0);
                $table->decimal('testing_qty', 22, 3)->default(0);
                $table->decimal('sold_qty', 22, 3)->default(0);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->string('discount_type', 30)->default('fixed');
                $table->decimal('discount_value', 22, 4)->default(0);
                $table->decimal('amount', 22, 4)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_settlement_payments')) {
            Schema::create('pdirectnew_settlement_payments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('settlement_id')->index();
                $table->string('payment_type', 50)->index();
                $table->string('reference_no', 190)->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('account_id')->nullable()->index();
                $table->decimal('amount', 22, 4);
                $table->date('payment_date')->nullable()->index();
                $table->string('status', 30)->default('active')->index();
                $table->json('details')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_settlement_other_sales')) {
            Schema::create('pdirectnew_settlement_other_sales', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('settlement_id')->index();
                $table->string('reference_no', 190)->nullable();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->decimal('total', 22, 4)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_settlement_other_sale_lines')) {
            Schema::create('pdirectnew_settlement_other_sale_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('other_sale_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->decimal('qty', 22, 4)->default(0);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->decimal('tax_amount', 22, 4)->default(0);
                $table->decimal('line_total', 22, 4)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_settlement_other_income')) {
            Schema::create('pdirectnew_settlement_other_income', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('settlement_id')->index();
                $table->string('description', 500);
                $table->decimal('amount', 22, 4)->default(0);
                $table->unsignedBigInteger('account_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_settlement_customer_payments')) {
            Schema::create('pdirectnew_settlement_customer_payments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('settlement_id')->index();
                $table->unsignedBigInteger('contact_id')->index();
                $table->string('reference_no', 190)->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('payment_method', 50)->default('cash');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_daily_collections')) {
            Schema::create('pdirectnew_daily_collections', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->string('collection_no', 80);
                $table->date('collection_date')->index();
                $table->unsignedBigInteger('operator_id')->nullable()->index();
                $table->unsignedBigInteger('shift_id')->nullable()->index();
                $table->string('status', 30)->default('draft')->index();
                $table->decimal('cash_total', 22, 4)->default(0);
                $table->decimal('card_total', 22, 4)->default(0);
                $table->decimal('cheque_total', 22, 4)->default(0);
                $table->decimal('credit_total', 22, 4)->default(0);
                $table->decimal('other_total', 22, 4)->default(0);
                $table->decimal('shortage', 22, 4)->default(0);
                $table->decimal('excess', 22, 4)->default(0);
                $table->decimal('grand_total', 22, 4)->default(0);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('finalized_by')->nullable();
                $table->dateTime('finalized_at')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'collection_no'], 'pdn_collection_no_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_collection_lines')) {
            Schema::create('pdirectnew_collection_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('daily_collection_id')->index();
                $table->string('line_type', 50)->index();
                $table->string('reference_type', 100)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable()->index();
                $table->decimal('amount', 22, 4)->default(0);
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }


        if (!Schema::hasTable('pdirectnew_pumper_day_entries')) {
            Schema::create('pdirectnew_pumper_day_entries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('operator_id')->index();
                $table->unsignedBigInteger('shift_id')->nullable()->index();
                $table->date('entry_date')->index();
                $table->string('entry_type', 50)->default('general')->index();
                $table->string('reference_no', 190)->nullable();
                $table->decimal('amount', 22, 4)->default(0);
                $table->decimal('quantity', 22, 3)->default(0);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_unload_stocks')) {
            Schema::create('pdirectnew_unload_stocks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->string('unload_no', 80);
                $table->unsignedBigInteger('operator_id')->nullable()->index();
                $table->unsignedBigInteger('shift_id')->nullable()->index();
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->string('reference_no', 190)->nullable();
                $table->date('unload_date')->index();
                $table->decimal('total_qty', 22, 3)->default(0);
                $table->string('status', 30)->default('completed')->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'unload_no'], 'pdn_unload_no_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_unload_stock_lines')) {
            Schema::create('pdirectnew_unload_stock_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('unload_stock_id')->index();
                $table->unsignedBigInteger('tank_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->decimal('quantity', 22, 3);
                $table->decimal('unit_cost', 22, 4)->default(0);
                $table->decimal('line_total', 22, 4)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_tank_transfers')) {
            Schema::create('pdirectnew_tank_transfers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->string('transfer_no', 80);
                $table->unsignedBigInteger('from_tank_id')->index();
                $table->unsignedBigInteger('to_tank_id')->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->decimal('quantity', 22, 3);
                $table->date('transfer_date')->index();
                $table->string('status', 30)->default('completed')->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'transfer_no'], 'pdn_transfer_no_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_dip_charts')) {
            Schema::create('pdirectnew_dip_charts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('tank_id')->index();
                $table->string('name', 190);
                $table->string('unit', 30)->default('litre');
                $table->string('status', 30)->default('active')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_dip_chart_lines')) {
            Schema::create('pdirectnew_dip_chart_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('dip_chart_id')->index();
                $table->decimal('dip_value', 22, 3);
                $table->decimal('volume', 22, 3);
                $table->timestamps();
                $table->unique(['dip_chart_id', 'dip_value'], 'pdn_dip_chart_line_uq');
            });
        }

        if (!Schema::hasTable('pdirectnew_dip_readings')) {
            Schema::create('pdirectnew_dip_readings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('tank_id')->index();
                $table->unsignedBigInteger('dip_chart_id')->nullable()->index();
                $table->date('reading_date')->index();
                $table->decimal('dip_value', 22, 3);
                $table->decimal('calculated_stock', 22, 3)->default(0);
                $table->decimal('actual_stock', 22, 3)->default(0);
                $table->decimal('variance', 22, 3)->default(0);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_adjustments')) {
            Schema::create('pdirectnew_adjustments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('settlement_id')->nullable()->index();
                $table->unsignedBigInteger('operator_id')->nullable()->index();
                $table->string('adjustment_type', 50)->index();
                $table->decimal('amount', 22, 4);
                $table->text('reason');
                $table->string('status', 30)->default('pending')->index();
                $table->unsignedBigInteger('requested_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_print_logs')) {
            Schema::create('pdirectnew_print_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->string('document_type', 100)->index();
                $table->unsignedBigInteger('document_id')->nullable()->index();
                $table->unsignedBigInteger('printed_by')->nullable();
                $table->dateTime('printed_at')->nullable()->index();
                $table->string('ip_address', 64)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_audit_logs')) {
            Schema::create('pdirectnew_audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('action', 80)->index();
                $table->string('entity_type', 120)->index();
                $table->unsignedBigInteger('entity_id')->nullable()->index();
                $table->longText('before_data')->nullable();
                $table->longText('after_data')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pdirectnew_saved_report_filters')) {
            Schema::create('pdirectnew_saved_report_filters', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('report_type', 80)->index();
                $table->string('name', 190);
                $table->json('filters')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse([
            'pdirectnew_settings','pdirectnew_number_sequences','pdirectnew_operators','pdirectnew_tanks','pdirectnew_pumps',
            'pdirectnew_shifts','pdirectnew_assignments','pdirectnew_meter_readings','pdirectnew_meter_resets',
            'pdirectnew_settlements','pdirectnew_settlement_meter_sales','pdirectnew_settlement_payments',
            'pdirectnew_settlement_other_sales','pdirectnew_settlement_other_sale_lines','pdirectnew_settlement_other_income',
            'pdirectnew_settlement_customer_payments','pdirectnew_daily_collections','pdirectnew_collection_lines',
            'pdirectnew_pumper_day_entries','pdirectnew_unload_stocks','pdirectnew_unload_stock_lines','pdirectnew_tank_transfers','pdirectnew_dip_charts','pdirectnew_dip_chart_lines','pdirectnew_dip_readings',
            'pdirectnew_adjustments','pdirectnew_print_logs','pdirectnew_audit_logs','pdirectnew_saved_report_filters',
        ]) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
