<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Modified by Engr. Alex -- task 7889
return new class extends Migration
{
    public function up()
    {
        $indexExists = function ($table, $indexName) {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        };

        // contacts(business_id, type) — speeds up getCustomerContact() WHERE business_id = ? AND type = 'customer'
        // composite is more selective than scanning all contacts for a business
        if (!$indexExists('contacts', 'idx_contacts_business_type')) {
            DB::statement('CREATE INDEX idx_contacts_business_type ON contacts(business_id, type)');
        }

        // transaction_payments(business_id, payment_for, deleted_at) — getContactsBalance() query 2
        // joins transaction_payments where payment_for IN (...) and deleted_at IS NULL;
        // composite covers both the filter and the soft-delete check in one index scan
        if (!$indexExists('transaction_payments', 'idx_tp_business_payment_for_deleted')) {
            DB::statement('CREATE INDEX idx_tp_business_payment_for_deleted ON transaction_payments(business_id, payment_for, deleted_at)');
        }
    }

    public function down()
    {
        $dropIndexIfExists = function ($table, $indexName) {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
            if (!empty($indexes)) {
                DB::statement("DROP INDEX {$indexName} ON {$table}");
            }
        };

        $dropIndexIfExists('contacts', 'idx_contacts_business_type');
        $dropIndexIfExists('transaction_payments', 'idx_tp_business_payment_for_deleted');
    }
};
