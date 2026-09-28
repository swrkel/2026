<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * These indexes will significantly improve the loading speed of daily reports
     * by optimizing the most frequently executed queries.
     *
     * @return void
     */
    public function up()
    {
        // Check if index exists helper
        $indexExists = function ($table, $indexName) {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        };

        // ==============================================
        // TRANSACTIONS TABLE INDEXES
        // ==============================================

        // Index for daily report main queries
        if (!$indexExists('transactions', 'idx_transactions_daily_report')) {
            DB::statement('CREATE INDEX idx_transactions_daily_report ON transactions(business_id, type, status, transaction_date, is_settlement, is_credit_sale)');
        }

        // Index for transaction dates and business
        if (!$indexExists('transactions', 'idx_transactions_date_business')) {
            DB::statement('CREATE INDEX idx_transactions_date_business ON transactions(transaction_date, business_id, status)');
        }

        // Index for settlement queries
        if (!$indexExists('transactions', 'idx_transactions_settlement')) {
            DB::statement('CREATE INDEX idx_transactions_settlement ON transactions(is_settlement, type, sub_type, business_id, transaction_date)');
        }

        // Index for invoice number lookups
        if (!$indexExists('transactions', 'idx_transactions_invoice_no')) {
            DB::statement('CREATE INDEX idx_transactions_invoice_no ON transactions(invoice_no, business_id)');
        }

        // Index for location-based queries
        if (!$indexExists('transactions', 'idx_transactions_location')) {
            DB::statement('CREATE INDEX idx_transactions_location ON transactions(location_id, business_id, transaction_date)');
        }

        // Index for created_by (cashier queries)
        if (!$indexExists('transactions', 'idx_transactions_created_by')) {
            DB::statement('CREATE INDEX idx_transactions_created_by ON transactions(created_by, business_id, transaction_date, type)');
        }

        // ==============================================
        // TRANSACTION_SELL_LINES TABLE INDEXES
        // ==============================================

        // Index for sell lines queries
        if (!$indexExists('transaction_sell_lines', 'idx_sell_lines_transaction')) {
            DB::statement('CREATE INDEX idx_sell_lines_transaction ON transaction_sell_lines(transaction_id, product_id)');
        }

        // Index for created_at date queries
        if (!$indexExists('transaction_sell_lines', 'idx_sell_lines_created_at')) {
            DB::statement('CREATE INDEX idx_sell_lines_created_at ON transaction_sell_lines(created_at)');
        }

        // ==============================================
        // TRANSACTION_PAYMENTS TABLE INDEXES
        // ==============================================

        // Index for payment queries by transaction and method
        if (!$indexExists('transaction_payments', 'idx_payments_transaction_method')) {
            DB::statement('CREATE INDEX idx_payments_transaction_method ON transaction_payments(transaction_id, method, paid_on)');
        }

        // Index for payment dates
        if (!$indexExists('transaction_payments', 'idx_payments_paid_on')) {
            DB::statement('CREATE INDEX idx_payments_paid_on ON transaction_payments(paid_on)');
        }

        // ==============================================
        // SETTLEMENTS TABLE INDEXES
        // ==============================================

        // Index for settlement queries
        if (!$indexExists('settlements', 'idx_settlements_main')) {
            DB::statement('CREATE INDEX idx_settlements_main ON settlements(settlement_no, pump_operator_id, business_id)');
        }

        // Index for pump operator lookups
        if (!$indexExists('settlements', 'idx_settlements_pump_operator')) {
            DB::statement('CREATE INDEX idx_settlements_pump_operator ON settlements(pump_operator_id, business_id)');
        }

        // ==============================================
        // ACCOUNT_TRANSACTIONS TABLE INDEXES
        // ==============================================

        // Index for account transaction queries
        if (!$indexExists('account_transactions', 'idx_account_trans_main')) {
            DB::statement('CREATE INDEX idx_account_trans_main ON account_transactions(account_id, type, operation_date, sub_type)');
        }

        // Index for operation dates
        if (!$indexExists('account_transactions', 'idx_account_trans_date')) {
            DB::statement('CREATE INDEX idx_account_trans_date ON account_transactions(operation_date)');
        }

        // Index for transaction_id
        if (!$indexExists('account_transactions', 'idx_account_trans_transaction')) {
            DB::statement('CREATE INDEX idx_account_trans_transaction ON account_transactions(transaction_id)');
        }

        // ==============================================
        // ACCOUNTS TABLE INDEXES
        // ==============================================

        // Index for account lookups
        if (!$indexExists('accounts', 'idx_accounts_business_type')) {
            DB::statement('CREATE INDEX idx_accounts_business_type ON accounts(business_id, account_type_id, asset_type, disabled)');
        }

        // ==============================================
        // PRODUCTS TABLE INDEXES
        // ==============================================

        // Index for product category queries
        if (!$indexExists('products', 'idx_products_categories')) {
            DB::statement('CREATE INDEX idx_products_categories ON products(business_id, category_id, sub_category_id)');
        }

        // ==============================================
        // PUMP_OPERATORS TABLE INDEXES
        // ==============================================

        // Index for pump operator queries
        if (!$indexExists('pump_operators', 'idx_pump_operators_business')) {
            DB::statement('CREATE INDEX idx_pump_operators_business ON pump_operators(business_id, id)');
        }

        // ==============================================
        // DIP_READINGS TABLE INDEXES
        // ==============================================

        // Index for dip reading queries
        if (!$indexExists('dip_readings', 'idx_dip_readings_date')) {
            DB::statement('CREATE INDEX idx_dip_readings_date ON dip_readings(business_id, transaction_date, location_id, tank_id)');
        }

        // ==============================================
        // CONTACTS TABLE INDEXES
        // ==============================================

        // Index for contact type queries
        if (!$indexExists('contacts', 'idx_contacts_business_type')) {
            DB::statement('CREATE INDEX idx_contacts_business_type ON contacts(business_id, type, customer_group_id)');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop all indexes
        $dropIndexIfExists = function ($table, $indexName) {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            if (!empty($indexes)) {
                DB::statement("DROP INDEX {$indexName} ON {$table}");
            }
        };

        // Transactions indexes
        $dropIndexIfExists('transactions', 'idx_transactions_daily_report');
        $dropIndexIfExists('transactions', 'idx_transactions_date_business');
        $dropIndexIfExists('transactions', 'idx_transactions_settlement');
        $dropIndexIfExists('transactions', 'idx_transactions_invoice_no');
        $dropIndexIfExists('transactions', 'idx_transactions_location');
        $dropIndexIfExists('transactions', 'idx_transactions_created_by');

        // Transaction_sell_lines indexes
        $dropIndexIfExists('transaction_sell_lines', 'idx_sell_lines_transaction');
        $dropIndexIfExists('transaction_sell_lines', 'idx_sell_lines_created_at');

        // Transaction_payments indexes
        $dropIndexIfExists('transaction_payments', 'idx_payments_transaction_method');
        $dropIndexIfExists('transaction_payments', 'idx_payments_paid_on');

        // Settlements indexes
        $dropIndexIfExists('settlements', 'idx_settlements_main');
        $dropIndexIfExists('settlements', 'idx_settlements_pump_operator');

        // Account_transactions indexes
        $dropIndexIfExists('account_transactions', 'idx_account_trans_main');
        $dropIndexIfExists('account_transactions', 'idx_account_trans_date');
        $dropIndexIfExists('account_transactions', 'idx_account_trans_transaction');

        // Accounts indexes
        $dropIndexIfExists('accounts', 'idx_accounts_business_type');

        // Products indexes
        $dropIndexIfExists('products', 'idx_products_categories');

        // Pump_operators indexes
        $dropIndexIfExists('pump_operators', 'idx_pump_operators_business');

        // Dip_readings indexes
        $dropIndexIfExists('dip_readings', 'idx_dip_readings_date');

        // Contacts indexes
        $dropIndexIfExists('contacts', 'idx_contacts_business_type');
    }
};
