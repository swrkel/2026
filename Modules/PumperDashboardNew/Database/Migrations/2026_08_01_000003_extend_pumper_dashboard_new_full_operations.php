<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendSettings();
        $this->extendShifts();
        $this->extendAssignments();
        $this->extendPayments();
        $this->extendCreditSales();
        $this->extendOtherSales();
        $this->extendUnloadStocks();
        $this->extendDayEntries();

        $this->createAssignmentEvents();
        $this->createCashDenominations();
        $this->createCardLines();
        $this->createPaymentEditHistories();
        $this->createDailyCollections();
        $this->createSettlementReferences();
        $this->createShortageRecoveries();
        $this->createExcessCommissions();
        $this->createOperatorLedgerEntries();
        $this->createOperatorDocuments();
        $this->createOperatorNotes();
        $this->createPrintLogs();
    }

    public function down(): void
    {
        foreach ([
            'pone_print_logs', 'pone_operator_notes', 'pone_operator_documents',
            'pone_operator_ledger_entries', 'pone_excess_commissions',
            'pone_shortage_recoveries', 'pone_shift_settlement_references',
            'pone_daily_collections', 'pone_payment_edit_histories',
            'pone_payment_card_lines', 'pone_payment_cash_denominations',
            'pone_assignment_events',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        $this->dropColumns('pone_day_entries', ['settlement_no', 'edited_by', 'edited_at', 'voided_by', 'voided_at']);
        $this->dropColumns('pone_unload_stocks', ['supplier_id', 'edited_by', 'edited_at', 'voided_by', 'voided_at', 'printed_count', 'last_printed_at']);
        $this->dropColumns('pone_other_sales', ['customer_id', 'payment_method', 'collection_form_no', 'edited_by', 'edited_at', 'voided_by', 'voided_at', 'printed_count', 'last_printed_at']);
        $this->dropColumns('pone_credit_sales', ['print_copy_option', 'printed_count', 'last_printed_at']);
        $this->dropColumns('pone_payments', ['collection_form_no', 'parent_payment_id', 'account_id', 'edit_version', 'edit_reason', 'edited_by', 'edited_at', 'print_copy_option', 'printed_count', 'last_printed_at']);
        $this->dropColumns('pone_pump_assignments', ['accepted_by', 'confirmed_by', 'closed_by', 'closing_note']);
        $this->dropColumns('pone_shifts', ['collection_form_no', 'settlement_no', 'declared_total', 'reconciliation_status', 'reconciled_at', 'reconciled_by', 'closed_statement_printed_at']);
        $this->dropColumns('pone_module_settings', [
            'cash_denomination_enabled', 'multi_card_enabled', 'cheque_enabled',
            'credit_sale_enabled', 'require_credit_dual_confirmation', 'require_collection_before_close',
            'allow_payment_edit', 'payment_edit_lock_minutes', 'auto_logout_minutes',
            'receipt_paper_size', 'default_store_id', 'settlement_prefix',
            'collection_prefix', 'recovery_prefix', 'commission_prefix',
        ]);
    }

    private function extendSettings(): void
    {
        if (! Schema::hasTable('pone_module_settings')) return;
        $this->addColumn('pone_module_settings', 'cash_denomination_enabled', fn (Blueprint $t) => $t->boolean('cash_denomination_enabled')->default(true));
        $this->addColumn('pone_module_settings', 'multi_card_enabled', fn (Blueprint $t) => $t->boolean('multi_card_enabled')->default(true));
        $this->addColumn('pone_module_settings', 'cheque_enabled', fn (Blueprint $t) => $t->boolean('cheque_enabled')->default(true));
        $this->addColumn('pone_module_settings', 'credit_sale_enabled', fn (Blueprint $t) => $t->boolean('credit_sale_enabled')->default(true));
        $this->addColumn('pone_module_settings', 'require_credit_dual_confirmation', fn (Blueprint $t) => $t->boolean('require_credit_dual_confirmation')->default(true));
        $this->addColumn('pone_module_settings', 'require_collection_before_close', fn (Blueprint $t) => $t->boolean('require_collection_before_close')->default(false));
        $this->addColumn('pone_module_settings', 'allow_payment_edit', fn (Blueprint $t) => $t->boolean('allow_payment_edit')->default(true));
        $this->addColumn('pone_module_settings', 'payment_edit_lock_minutes', fn (Blueprint $t) => $t->unsignedSmallInteger('payment_edit_lock_minutes')->default(1440));
        $this->addColumn('pone_module_settings', 'auto_logout_minutes', fn (Blueprint $t) => $t->unsignedSmallInteger('auto_logout_minutes')->default(0));
        $this->addColumn('pone_module_settings', 'receipt_paper_size', fn (Blueprint $t) => $t->string('receipt_paper_size', 20)->default('80mm'));
        $this->addColumn('pone_module_settings', 'default_store_id', fn (Blueprint $t) => $t->unsignedInteger('default_store_id')->nullable());
        $this->addColumn('pone_module_settings', 'settlement_prefix', fn (Blueprint $t) => $t->string('settlement_prefix', 30)->default('PONE-SET-'));
        $this->addColumn('pone_module_settings', 'collection_prefix', fn (Blueprint $t) => $t->string('collection_prefix', 30)->default('PONE-COL-'));
        $this->addColumn('pone_module_settings', 'recovery_prefix', fn (Blueprint $t) => $t->string('recovery_prefix', 30)->default('PONE-REC-'));
        $this->addColumn('pone_module_settings', 'commission_prefix', fn (Blueprint $t) => $t->string('commission_prefix', 30)->default('PONE-COM-'));
    }

    private function extendShifts(): void
    {
        if (! Schema::hasTable('pone_shifts')) return;
        $this->addColumn('pone_shifts', 'collection_form_no', fn (Blueprint $t) => $t->string('collection_form_no', 100)->nullable());
        $this->addColumn('pone_shifts', 'settlement_no', fn (Blueprint $t) => $t->string('settlement_no', 100)->nullable());
        $this->addColumn('pone_shifts', 'declared_total', fn (Blueprint $t) => $t->decimal('declared_total', 22, 4)->default(0));
        $this->addColumn('pone_shifts', 'reconciliation_status', fn (Blueprint $t) => $t->enum('reconciliation_status', ['pending', 'balanced', 'shortage', 'excess'])->default('pending'));
        $this->addColumn('pone_shifts', 'reconciled_at', fn (Blueprint $t) => $t->timestamp('reconciled_at')->nullable());
        $this->addColumn('pone_shifts', 'reconciled_by', fn (Blueprint $t) => $t->unsignedInteger('reconciled_by')->nullable());
        $this->addColumn('pone_shifts', 'closed_statement_printed_at', fn (Blueprint $t) => $t->timestamp('closed_statement_printed_at')->nullable());
    }

    private function extendAssignments(): void
    {
        if (! Schema::hasTable('pone_pump_assignments')) return;
        $this->addColumn('pone_pump_assignments', 'accepted_by', fn (Blueprint $t) => $t->unsignedInteger('accepted_by')->nullable());
        $this->addColumn('pone_pump_assignments', 'confirmed_by', fn (Blueprint $t) => $t->unsignedInteger('confirmed_by')->nullable());
        $this->addColumn('pone_pump_assignments', 'closed_by', fn (Blueprint $t) => $t->unsignedInteger('closed_by')->nullable());
        $this->addColumn('pone_pump_assignments', 'closing_note', fn (Blueprint $t) => $t->text('closing_note')->nullable());
    }

    private function extendPayments(): void
    {
        if (! Schema::hasTable('pone_payments')) return;
        $this->addColumn('pone_payments', 'collection_form_no', fn (Blueprint $t) => $t->string('collection_form_no', 100)->nullable());
        $this->addColumn('pone_payments', 'parent_payment_id', fn (Blueprint $t) => $t->unsignedBigInteger('parent_payment_id')->nullable());
        $this->addColumn('pone_payments', 'account_id', fn (Blueprint $t) => $t->unsignedInteger('account_id')->nullable());
        $this->addColumn('pone_payments', 'edit_version', fn (Blueprint $t) => $t->unsignedSmallInteger('edit_version')->default(1));
        $this->addColumn('pone_payments', 'edit_reason', fn (Blueprint $t) => $t->text('edit_reason')->nullable());
        $this->addColumn('pone_payments', 'edited_by', fn (Blueprint $t) => $t->unsignedInteger('edited_by')->nullable());
        $this->addColumn('pone_payments', 'edited_at', fn (Blueprint $t) => $t->timestamp('edited_at')->nullable());
        $this->addColumn('pone_payments', 'print_copy_option', fn (Blueprint $t) => $t->string('print_copy_option', 30)->nullable());
        $this->addColumn('pone_payments', 'printed_count', fn (Blueprint $t) => $t->unsignedSmallInteger('printed_count')->default(0));
        $this->addColumn('pone_payments', 'last_printed_at', fn (Blueprint $t) => $t->timestamp('last_printed_at')->nullable());
    }

    private function extendCreditSales(): void
    {
        if (! Schema::hasTable('pone_credit_sales')) return;
        $this->addColumn('pone_credit_sales', 'print_copy_option', fn (Blueprint $t) => $t->string('print_copy_option', 30)->nullable());
        $this->addColumn('pone_credit_sales', 'printed_count', fn (Blueprint $t) => $t->unsignedSmallInteger('printed_count')->default(0));
        $this->addColumn('pone_credit_sales', 'last_printed_at', fn (Blueprint $t) => $t->timestamp('last_printed_at')->nullable());
    }

    private function extendOtherSales(): void
    {
        if (! Schema::hasTable('pone_other_sales')) return;
        $this->addColumn('pone_other_sales', 'customer_id', fn (Blueprint $t) => $t->unsignedInteger('customer_id')->nullable());
        $this->addColumn('pone_other_sales', 'payment_method', fn (Blueprint $t) => $t->string('payment_method', 40)->default('cash'));
        $this->addColumn('pone_other_sales', 'collection_form_no', fn (Blueprint $t) => $t->string('collection_form_no', 100)->nullable());
        $this->addColumn('pone_other_sales', 'edited_by', fn (Blueprint $t) => $t->unsignedInteger('edited_by')->nullable());
        $this->addColumn('pone_other_sales', 'edited_at', fn (Blueprint $t) => $t->timestamp('edited_at')->nullable());
        $this->addColumn('pone_other_sales', 'voided_by', fn (Blueprint $t) => $t->unsignedInteger('voided_by')->nullable());
        $this->addColumn('pone_other_sales', 'voided_at', fn (Blueprint $t) => $t->timestamp('voided_at')->nullable());
        $this->addColumn('pone_other_sales', 'printed_count', fn (Blueprint $t) => $t->unsignedSmallInteger('printed_count')->default(0));
        $this->addColumn('pone_other_sales', 'last_printed_at', fn (Blueprint $t) => $t->timestamp('last_printed_at')->nullable());
    }

    private function extendUnloadStocks(): void
    {
        if (! Schema::hasTable('pone_unload_stocks')) return;
        $this->addColumn('pone_unload_stocks', 'supplier_id', fn (Blueprint $t) => $t->unsignedInteger('supplier_id')->nullable());
        $this->addColumn('pone_unload_stocks', 'edited_by', fn (Blueprint $t) => $t->unsignedInteger('edited_by')->nullable());
        $this->addColumn('pone_unload_stocks', 'edited_at', fn (Blueprint $t) => $t->timestamp('edited_at')->nullable());
        $this->addColumn('pone_unload_stocks', 'voided_by', fn (Blueprint $t) => $t->unsignedInteger('voided_by')->nullable());
        $this->addColumn('pone_unload_stocks', 'voided_at', fn (Blueprint $t) => $t->timestamp('voided_at')->nullable());
        $this->addColumn('pone_unload_stocks', 'printed_count', fn (Blueprint $t) => $t->unsignedSmallInteger('printed_count')->default(0));
        $this->addColumn('pone_unload_stocks', 'last_printed_at', fn (Blueprint $t) => $t->timestamp('last_printed_at')->nullable());
    }

    private function extendDayEntries(): void
    {
        if (! Schema::hasTable('pone_day_entries')) return;
        $this->addColumn('pone_day_entries', 'settlement_no', fn (Blueprint $t) => $t->string('settlement_no', 100)->nullable());
        $this->addColumn('pone_day_entries', 'edited_by', fn (Blueprint $t) => $t->unsignedInteger('edited_by')->nullable());
        $this->addColumn('pone_day_entries', 'edited_at', fn (Blueprint $t) => $t->timestamp('edited_at')->nullable());
        $this->addColumn('pone_day_entries', 'voided_by', fn (Blueprint $t) => $t->unsignedInteger('voided_by')->nullable());
        $this->addColumn('pone_day_entries', 'voided_at', fn (Blueprint $t) => $t->timestamp('voided_at')->nullable());
    }

    private function createAssignmentEvents(): void
    {
        if (Schema::hasTable('pone_assignment_events')) return;
        Schema::create('pone_assignment_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('operator_profile_id');
            $table->enum('event_type', ['assigned', 'accepted', 'confirmed', 'current_meter', 'closed', 'reopened', 'cancelled']);
            $table->decimal('meter_value', 22, 6)->nullable();
            $table->decimal('testing_quantity', 22, 6)->default(0);
            $table->text('note')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['assignment_id', 'occurred_at'], 'pone_assignment_event_assignment_idx');
            $table->index(['business_id', 'event_type', 'occurred_at'], 'pone_assignment_event_scope_idx');
        });
    }

    private function createCashDenominations(): void
    {
        if (Schema::hasTable('pone_payment_cash_denominations')) return;
        Schema::create('pone_payment_cash_denominations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_id');
            $table->decimal('denomination', 16, 2);
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('amount', 22, 4)->default(0);
            $table->timestamps();
            $table->unique(['payment_id', 'denomination'], 'pone_cash_denom_payment_value_uq');
        });
    }

    private function createCardLines(): void
    {
        if (Schema::hasTable('pone_payment_card_lines')) return;
        Schema::create('pone_payment_card_lines', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_id');
            $table->string('card_type', 80)->nullable();
            $table->string('last_four', 4)->nullable();
            $table->string('slip_no', 100)->nullable();
            $table->unsignedInteger('account_id')->nullable();
            $table->string('reference_no', 191)->nullable();
            $table->decimal('amount', 22, 4);
            $table->timestamps();
            $table->index(['payment_id', 'slip_no'], 'pone_card_line_payment_slip_idx');
        });
    }

    private function createPaymentEditHistories(): void
    {
        if (Schema::hasTable('pone_payment_edit_histories')) return;
        Schema::create('pone_payment_edit_histories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_id');
            $table->unsignedInteger('business_id');
            $table->unsignedSmallInteger('version_no');
            $table->text('reason');
            $table->longText('before_data');
            $table->longText('after_data');
            $table->unsignedInteger('edited_by')->nullable();
            $table->timestamp('edited_at');
            $table->timestamps();
            $table->unique(['payment_id', 'version_no'], 'pone_payment_history_version_uq');
            $table->index(['business_id', 'edited_at'], 'pone_payment_history_business_idx');
        });
    }

    private function createDailyCollections(): void
    {
        if (Schema::hasTable('pone_daily_collections')) return;
        Schema::create('pone_daily_collections', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('uuid', 36)->unique();
            $table->unsignedBigInteger('shift_id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->string('collection_number', 80);
            $table->timestamp('collection_at');
            $table->decimal('expected_amount', 22, 4)->default(0);
            $table->decimal('cash_amount', 22, 4)->default(0);
            $table->decimal('card_amount', 22, 4)->default(0);
            $table->decimal('cheque_amount', 22, 4)->default(0);
            $table->decimal('credit_amount', 22, 4)->default(0);
            $table->decimal('other_amount', 22, 4)->default(0);
            $table->decimal('declared_amount', 22, 4)->default(0);
            $table->decimal('difference_amount', 22, 4)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'void'])->default('confirmed');
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('confirmed_by')->nullable();
            $table->unsignedInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'collection_number'], 'pone_collection_business_number_uq');
            $table->index(['shift_id', 'status', 'collection_at'], 'pone_collection_shift_idx');
        });
    }

    private function createSettlementReferences(): void
    {
        if (Schema::hasTable('pone_shift_settlement_references')) return;
        Schema::create('pone_shift_settlement_references', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shift_id');
            $table->unsignedInteger('business_id');
            $table->string('settlement_no', 100);
            $table->date('settlement_date');
            $table->text('note')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'shift_id'], 'pone_settlement_business_shift_uq');
            $table->unique(['shift_id', 'settlement_no'], 'pone_settlement_shift_number_uq');
            $table->index(['business_id', 'settlement_date'], 'pone_settlement_business_date_idx');
        });
    }

    private function createShortageRecoveries(): void
    {
        if (Schema::hasTable('pone_shortage_recoveries')) return;
        Schema::create('pone_shortage_recoveries', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->string('recovery_number', 80);
            $table->date('recovery_date');
            $table->decimal('amount', 22, 4);
            $table->string('payment_method', 40)->default('cash');
            $table->string('reference_no', 191)->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['confirmed', 'void'])->default('confirmed');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'recovery_number'], 'pone_recovery_business_number_uq');
            $table->index(['operator_profile_id', 'recovery_date', 'status'], 'pone_recovery_operator_idx');
        });
    }

    private function createExcessCommissions(): void
    {
        if (Schema::hasTable('pone_excess_commissions')) return;
        Schema::create('pone_excess_commissions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->string('commission_number', 80);
            $table->date('commission_date');
            $table->decimal('base_excess_amount', 22, 4)->default(0);
            $table->enum('commission_type', ['fixed', 'percentage'])->default('percentage');
            $table->decimal('commission_rate', 14, 6)->default(0);
            $table->decimal('commission_amount', 22, 4)->default(0);
            $table->text('note')->nullable();
            $table->enum('status', ['confirmed', 'void'])->default('confirmed');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'commission_number'], 'pone_commission_business_number_uq');
            $table->index(['operator_profile_id', 'commission_date', 'status'], 'pone_commission_operator_idx');
        });
    }

    private function createOperatorLedgerEntries(): void
    {
        if (Schema::hasTable('pone_operator_ledger_entries')) return;
        Schema::create('pone_operator_ledger_entries', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('entry_key', 191)->unique();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedInteger('pd_operator_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('entry_at');
            $table->string('reference_no', 100)->nullable();
            $table->string('description', 500);
            $table->decimal('debit', 22, 4)->default(0);
            $table->decimal('credit', 22, 4)->default(0);
            $table->enum('status', ['active', 'void'])->default('active');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['operator_profile_id', 'entry_at', 'status'], 'pone_ledger_operator_date_idx');
            $table->index(['source_type', 'source_id'], 'pone_ledger_source_idx');
        });
    }

    private function createOperatorDocuments(): void
    {
        if (Schema::hasTable('pone_operator_documents')) return;
        Schema::create('pone_operator_documents', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->string('title', 191);
            $table->string('category', 80)->default('general');
            $table->string('original_name', 255);
            $table->string('disk', 40)->default('public');
            $table->string('path', 500);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->enum('visibility', ['operator', 'management'])->default('operator');
            $table->unsignedInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['operator_profile_id', 'category'], 'pone_document_operator_category_idx');
        });
    }

    private function createOperatorNotes(): void
    {
        if (Schema::hasTable('pone_operator_notes')) return;
        Schema::create('pone_operator_notes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('operator_profile_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->enum('note_type', ['general', 'incident', 'hand_over', 'settlement'])->default('general');
            $table->string('title', 191);
            $table->text('body');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['operator_profile_id', 'status', 'created_at'], 'pone_note_operator_idx');
        });
    }

    private function createPrintLogs(): void
    {
        if (Schema::hasTable('pone_print_logs')) return;
        Schema::create('pone_print_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedBigInteger('operator_profile_id')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->string('printable_type', 80);
            $table->unsignedBigInteger('printable_id');
            $table->string('template_name', 100);
            $table->string('paper_size', 20)->default('80mm');
            $table->string('copy_type', 30)->default('original');
            $table->timestamp('printed_at');
            $table->unsignedInteger('printed_by')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->timestamps();
            $table->index(['printable_type', 'printable_id', 'printed_at'], 'pone_print_log_entity_idx');
            $table->index(['business_id', 'printed_at'], 'pone_print_log_business_idx');
        });
    }

    private function addColumn(string $table, string $column, callable $definition): void
    {
        if (Schema::hasColumn($table, $column)) return;
        Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
            $definition($blueprint);
        });
    }

    private function dropColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) return;
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
            }
        }
    }
};
