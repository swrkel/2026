<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createLoginAttempts();
        $this->createOperatorProfiles();
        $this->createOperatorSessions();
        $this->createSettings();
        $this->createSequences();
        $this->createShifts();
        $this->createAssignments();
        $this->createMeterReadings();
        $this->createPayments();
        $this->createCreditSales();
        $this->createCreditSaleLines();
        $this->createOtherSales();
        $this->createOtherSaleLines();
        $this->createUnloadStocks();
        $this->createUnloadStockLines();
        $this->createDayEntries();
        $this->createIntegrationOutbox();
        $this->createIntegrationLinks();
        $this->createAuditLogs();
    }

    public function down(): void
    {
        foreach (array_reverse([
            'pone_login_attempts', 'pone_pd_operators', 'pone_operator_sessions',
            'pone_module_settings', 'pone_number_sequences', 'pone_shifts',
            'pone_pump_assignments', 'pone_meter_readings', 'pone_payments',
            'pone_credit_sales', 'pone_credit_sale_lines', 'pone_other_sales',
            'pone_other_sale_lines', 'pone_unload_stocks', 'pone_unload_stock_lines',
            'pone_day_entries', 'pone_integration_outbox', 'pone_integration_links',
            'pone_audit_logs',
        ]) as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createLoginAttempts(): void
    {
        if (Schema::hasTable('pone_login_attempts')) return;
        Schema::create('pone_login_attempts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id')->nullable();
            $table->string('company_number', 100)->nullable();
            $table->string('ip_address', 64);
            $table->string('passcode_fingerprint', 64)->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->timestamp('blocked_until')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
            $table->unique(['company_number', 'ip_address'], 'pone_login_company_ip_uq');
            $table->index(['business_id', 'status'], 'pone_login_business_status_idx');
        });
    }

    private function createOperatorProfiles(): void
    {
        if (Schema::hasTable('pone_pd_operators')) return;
        Schema::create('pone_pd_operators', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedInteger('pd_operator_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('display_name', 191);
            $table->boolean('login_enabled')->default(true);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->longText('settings')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 64)->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'pd_operator_id'], 'pone_pd_operator_business_uq');
            $table->unique(['business_id', 'user_id'], 'pone_pd_operator_user_uq');
            $table->index(['business_id', 'location_id', 'status'], 'pone_pd_operator_scope_idx');
        });
    }

    private function createOperatorSessions(): void
    {
        if (Schema::hasTable('pone_operator_sessions')) return;
        Schema::create('pone_operator_sessions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('session_key', 64)->unique();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->string('shift_number', 80)->nullable();
            $table->timestamp('logged_in_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('logged_out_at')->nullable();
            $table->enum('status', ['active', 'logged_out', 'expired'])->default('active');
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'operator_profile_id', 'status'], 'pone_session_operator_status_idx');
        });
    }

    private function createSettings(): void
    {
        if (Schema::hasTable('pone_module_settings')) return;
        Schema::create('pone_module_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('scope_key', 100)->unique();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->boolean('integration_enabled')->default(true);
            $table->enum('integration_mode', ['petro_pd_new', 'petropd', 'local_only'])->default('petro_pd_new');
            $table->boolean('sync_during_operation')->default(true);
            $table->boolean('require_clean_sync_before_close')->default(true);
            $table->boolean('allow_operator_open_shift')->default(false);
            $table->boolean('allow_close_with_open_pumps')->default(false);
            $table->boolean('allow_close_with_pending_sync')->default(false);
            $table->unsignedTinyInteger('amount_decimals')->default(4);
            $table->unsignedTinyInteger('quantity_decimals')->default(3);
            $table->unsignedTinyInteger('meter_decimals')->default(3);
            $table->string('shift_prefix', 30)->default('PONE-SH-');
            $table->string('payment_prefix', 30)->default('PONE-PAY-');
            $table->string('other_sale_prefix', 30)->default('PONE-OS-');
            $table->string('unload_prefix', 30)->default('PONE-UL-');
            $table->longText('settings')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id'], 'pone_settings_scope_idx');
        });
    }

    private function createSequences(): void
    {
        if (Schema::hasTable('pone_number_sequences')) return;
        Schema::create('pone_number_sequences', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('scope_key', 150)->unique();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->string('sequence_type', 40);
            $table->string('prefix', 30);
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedTinyInteger('padding')->default(6);
            $table->timestamps();
            $table->index(['business_id', 'sequence_type'], 'pone_sequence_business_type_idx');
        });
    }

    private function createShifts(): void
    {
        if (Schema::hasTable('pone_shifts')) return;
        Schema::create('pone_shifts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->unique();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('shift_number', 80);
            $table->enum('status', ['planned', 'open', 'closing', 'closed', 'cancelled'])->default('open');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('meter_sales_total', 22, 4)->default(0);
            $table->decimal('other_sales_total', 22, 4)->default(0);
            $table->decimal('payments_total', 22, 4)->default(0);
            $table->decimal('expected_total', 22, 4)->default(0);
            $table->decimal('shortage_amount', 22, 4)->default(0);
            $table->decimal('excess_amount', 22, 4)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('petropd_shift_id')->nullable();
            $table->unsignedBigInteger('petropd_shift_number')->nullable();
            $table->unsignedBigInteger('petropd_meter_sale_id')->nullable();
            $table->enum('integration_status', ['not_required', 'pending', 'synced', 'failed'])->default('pending');
            $table->text('integration_error')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('closed_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'shift_number'], 'pone_shift_business_number_uq');
            $table->index(['business_id', 'pd_operator_id', 'status'], 'pone_shift_operator_status_idx');
            $table->index(['business_id', 'location_id', 'opened_at'], 'pone_shift_scope_date_idx');
        });
    }

    private function createAssignments(): void
    {
        if (Schema::hasTable('pone_pump_assignments')) return;
        Schema::create('pone_pump_assignments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->unsignedInteger('pump_id');
            $table->unsignedInteger('product_id')->nullable();
            $table->decimal('opening_meter', 22, 6)->default(0);
            $table->decimal('current_meter', 22, 6)->default(0);
            $table->decimal('closing_meter', 22, 6)->nullable();
            $table->decimal('testing_quantity', 22, 6)->default(0);
            $table->decimal('sold_quantity', 22, 6)->default(0);
            $table->decimal('unit_price', 22, 6)->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->enum('status', ['assigned', 'open', 'closed', 'cancelled'])->default('assigned');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('petropd_assignment_id')->nullable();
            $table->unsignedBigInteger('petropd_meter_detail_id')->nullable();
            $table->unsignedBigInteger('petropd_day_entry_id')->nullable();
            $table->enum('integration_status', ['not_required', 'pending', 'synced', 'failed'])->default('pending');
            $table->text('integration_error')->nullable();
            $table->timestamps();
            $table->unique(['shift_id', 'pump_id'], 'pone_assignment_shift_pump_uq');
            $table->unique('petropd_assignment_id', 'pone_assignment_petropd_uq');
            $table->index(['business_id', 'pd_operator_id', 'status'], 'pone_assignment_operator_status_idx');
        });
    }

    private function createMeterReadings(): void
    {
        if (Schema::hasTable('pone_meter_readings')) return;
        Schema::create('pone_meter_readings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pump_id');
            $table->enum('reading_type', ['opening', 'current', 'closing', 'testing']);
            $table->decimal('meter_value', 22, 6)->nullable();
            $table->decimal('testing_quantity', 22, 6)->default(0);
            $table->enum('source', ['manual', 'imported', 'system'])->default('manual');
            $table->timestamp('recorded_at');
            $table->unsignedInteger('recorded_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['assignment_id', 'reading_type', 'recorded_at'], 'pone_reading_assignment_type_idx');
            $table->index(['business_id', 'recorded_at'], 'pone_reading_business_date_idx');
        });
    }

    private function createPayments(): void
    {
        if (Schema::hasTable('pone_payments')) return;
        Schema::create('pone_payments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->unique();
            $table->unsignedBigInteger('shift_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->string('payment_number', 80);
            $table->enum('payment_type', ['cash', 'card', 'cheque', 'credit', 'shortage', 'excess', 'other']);
            $table->decimal('gross_amount', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('reference_no', 191)->nullable();
            $table->string('card_type', 80)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('slip_no', 100)->nullable();
            $table->string('bank_name', 191)->nullable();
            $table->string('cheque_no', 100)->nullable();
            $table->date('cheque_date')->nullable();
            $table->timestamp('transaction_at');
            $table->text('note')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'void'])->default('confirmed');
            $table->unsignedBigInteger('petropd_payment_id')->nullable();
            $table->enum('integration_status', ['not_required', 'pending', 'synced', 'failed'])->default('pending');
            $table->text('integration_error')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'payment_number'], 'pone_payment_business_number_uq');
            $table->unique('petropd_payment_id', 'pone_payment_petropd_uq');
            $table->index(['shift_id', 'payment_type', 'status'], 'pone_payment_shift_type_idx');
            $table->index(['business_id', 'transaction_at'], 'pone_payment_business_date_idx');
        });
    }

    private function createCreditSales(): void
    {
        if (Schema::hasTable('pone_credit_sales')) return;
        Schema::create('pone_credit_sales', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_id')->unique();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('customer_id');
            $table->string('order_number', 191);
            $table->string('bill_number', 191)->nullable();
            $table->string('vehicle_number', 100);
            $table->string('customer_reference', 191)->nullable();
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->boolean('customer_confirmed')->default(false);
            $table->boolean('order_confirmed')->default(false);
            $table->boolean('vehicle_confirmed')->default(false);
            $table->timestamp('customer_confirmed_at')->nullable();
            $table->timestamp('order_confirmed_at')->nullable();
            $table->timestamp('vehicle_confirmed_at')->nullable();
            $table->unsignedTinyInteger('confirmation_rounds')->default(0);
            $table->timestamp('locked_at')->nullable();
            $table->unsignedInteger('confirmed_by')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'customer_id', 'order_date'], 'pone_credit_customer_date_idx');
        });
    }

    private function createCreditSaleLines(): void
    {
        if (Schema::hasTable('pone_credit_sale_lines')) return;
        Schema::create('pone_credit_sale_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('credit_sale_id');
            $table->unsignedInteger('product_id');
            $table->decimal('quantity', 22, 6);
            $table->decimal('unit_price', 22, 6);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('amount', 22, 4);
            $table->unsignedBigInteger('petropd_credit_detail_id')->nullable();
            $table->timestamps();
            $table->index(['credit_sale_id', 'product_id'], 'pone_credit_line_sale_product_idx');
        });
    }

    private function createOtherSales(): void
    {
        if (Schema::hasTable('pone_other_sales')) return;
        Schema::create('pone_other_sales', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->unique();
            $table->unsignedBigInteger('shift_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->unsignedInteger('store_id')->nullable();
            $table->string('sale_number', 80);
            $table->timestamp('sale_at');
            $table->decimal('gross_amount', 22, 4)->default(0);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('net_amount', 22, 4)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'void'])->default('confirmed');
            $table->text('note')->nullable();
            $table->enum('integration_status', ['not_required', 'pending', 'synced', 'failed'])->default('pending');
            $table->text('integration_error')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'sale_number'], 'pone_other_sale_business_number_uq');
            $table->index(['shift_id', 'status'], 'pone_other_sale_shift_status_idx');
        });
    }

    private function createOtherSaleLines(): void
    {
        if (Schema::hasTable('pone_other_sale_lines')) return;
        Schema::create('pone_other_sale_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('other_sale_id');
            $table->unsignedInteger('product_id');
            $table->decimal('quantity', 22, 6);
            $table->decimal('unit_price', 22, 6);
            $table->decimal('discount_amount', 22, 4)->default(0);
            $table->decimal('amount', 22, 4);
            $table->decimal('balance_stock_snapshot', 22, 6)->nullable();
            $table->unsignedBigInteger('petropd_other_sale_id')->nullable();
            $table->timestamps();
            $table->index(['other_sale_id', 'product_id'], 'pone_other_line_sale_product_idx');
        });
    }

    private function createUnloadStocks(): void
    {
        if (Schema::hasTable('pone_unload_stocks')) return;
        Schema::create('pone_unload_stocks', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->unique();
            $table->unsignedBigInteger('shift_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->unsignedInteger('store_id')->nullable();
            $table->string('receipt_number', 80);
            $table->string('bill_number', 191)->nullable();
            $table->string('supplier_reference', 191)->nullable();
            $table->timestamp('unloaded_at');
            $table->decimal('total_quantity', 22, 6)->default(0);
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'void'])->default('confirmed');
            $table->text('note')->nullable();
            $table->enum('integration_status', ['not_required', 'pending', 'synced', 'failed'])->default('pending');
            $table->text('integration_error')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['business_id', 'receipt_number'], 'pone_unload_business_number_uq');
            $table->index(['shift_id', 'status'], 'pone_unload_shift_status_idx');
        });
    }

    private function createUnloadStockLines(): void
    {
        if (Schema::hasTable('pone_unload_stock_lines')) return;
        Schema::create('pone_unload_stock_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('unload_stock_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('tank_id')->nullable();
            $table->decimal('quantity', 22, 6);
            $table->decimal('unit_cost', 22, 6)->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('dip_reading', 22, 6)->nullable();
            $table->decimal('current_stock', 22, 6)->nullable();
            $table->unsignedBigInteger('petropd_unload_stock_id')->nullable();
            $table->timestamps();
            $table->index(['unload_stock_id', 'product_id'], 'pone_unload_line_product_idx');
        });
    }

    private function createDayEntries(): void
    {
        if (Schema::hasTable('pone_day_entries')) return;
        Schema::create('pone_day_entries', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->unsignedBigInteger('assignment_id')->nullable();
            $table->unsignedInteger('pump_id')->nullable();
            $table->enum('entry_type', ['note', 'incident', 'expense', 'deposit', 'meter', 'testing', 'other']);
            $table->string('reference_no', 191)->nullable();
            $table->decimal('quantity', 22, 6)->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->decimal('starting_meter', 22, 6)->nullable();
            $table->decimal('closing_meter', 22, 6)->nullable();
            $table->decimal('testing_quantity', 22, 6)->default(0);
            $table->timestamp('entry_at');
            $table->text('note')->nullable();
            $table->enum('status', ['active', 'void'])->default('active');
            $table->unsignedBigInteger('petropd_day_entry_id')->nullable();
            $table->enum('integration_status', ['not_required', 'pending', 'synced', 'failed'])->default('pending');
            $table->text('integration_error')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['shift_id', 'entry_type', 'entry_at'], 'pone_day_entry_shift_type_idx');
        });
    }

    private function createIntegrationOutbox(): void
    {
        if (Schema::hasTable('pone_integration_outbox')) return;
        Schema::create('pone_integration_outbox', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('idempotency_key', 191)->unique();
            $table->string('aggregate_type', 80);
            $table->unsignedBigInteger('aggregate_id');
            $table->string('event_type', 100);
            $table->longText('payload')->nullable();
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'status', 'available_at'], 'pone_outbox_status_idx');
            $table->index(['aggregate_type', 'aggregate_id'], 'pone_outbox_aggregate_idx');
        });
    }

    private function createIntegrationLinks(): void
    {
        if (Schema::hasTable('pone_integration_links')) return;
        Schema::create('pone_integration_links', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('target_table', 100);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('sync_hash', 64)->nullable();
            $table->enum('status', ['pending', 'synced', 'failed', 'retired'])->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id', 'target_table'], 'pone_integration_source_target_uq');
            $table->index(['business_id', 'status'], 'pone_integration_link_status_idx');
        });
    }

    private function createAuditLogs(): void
    {
        if (Schema::hasTable('pone_audit_logs')) return;
        Schema::create('pone_audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->longText('before_data')->nullable();
            $table->longText('after_data')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['business_id', 'entity_type', 'entity_id'], 'pone_audit_entity_idx');
            $table->index(['business_id', 'created_at'], 'pone_audit_business_date_idx');
        });
    }
};
