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

        // transactions(business_id, contact_id) — speeds up getCustomerContact() JOIN
        // and getContactsBalance() queries that filter on both columns
        if (!$indexExists('transactions', 'idx_transactions_business_contact')) {
            DB::statement('CREATE INDEX idx_transactions_business_contact ON transactions(business_id, contact_id)');
        }

        // transaction_payments(payment_for) — payment_for stores contact_id;
        // used in getContactsBalance() whereIn('payment_for', $contact_ids)
        if (!$indexExists('transaction_payments', 'idx_payments_payment_for')) {
            DB::statement('CREATE INDEX idx_payments_payment_for ON transaction_payments(payment_for)');
        }

        // customer_payments(customer_id) — used in getContactsBalance()
        if (!$indexExists('customer_payments', 'idx_customer_payments_customer')) {
            DB::statement('CREATE INDEX idx_customer_payments_customer ON customer_payments(customer_id)');
        }

        // contact_ledgers(contact_id) — used in getContactsBalance()
        if (!$indexExists('contact_ledgers', 'idx_contact_ledgers_contact')) {
            DB::statement('CREATE INDEX idx_contact_ledgers_contact ON contact_ledgers(contact_id)');
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

        $dropIndexIfExists('transactions', 'idx_transactions_business_contact');
        $dropIndexIfExists('transaction_payments', 'idx_payments_payment_for');
        $dropIndexIfExists('customer_payments', 'idx_customer_payments_customer');
        $dropIndexIfExists('contact_ledgers', 'idx_contact_ledgers_contact');
    }
};
