<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex(
            'contacts',
            'sup_contacts_business_type_active_idx',
            ['business_id', 'type', 'active', 'id']
        );

        $this->addIndex(
            'transactions',
            'sup_transactions_contact_date_idx',
            ['business_id', 'contact_id', 'transaction_date', 'id']
        );

        $this->addIndex(
            'transactions',
            'sup_transactions_contact_type_status_idx',
            ['business_id', 'contact_id', 'type', 'status', 'deleted_at']
        );

        $this->addIndex(
            'transaction_payments',
            'sup_payments_supplier_date_idx',
            ['business_id', 'payment_for', 'paid_on', 'id']
        );

        $this->addIndex(
            'transaction_payments',
            'sup_payments_transaction_parent_idx',
            ['transaction_id', 'parent_id', 'deleted_at']
        );

        $this->addIndex(
            'purchase_lines',
            'sup_purchase_lines_transaction_product_idx',
            ['transaction_id', 'product_id', 'id']
        );

        $this->addIndex(
            'contact_ledgers',
            'sup_contact_ledgers_contact_transaction_idx',
            ['contact_id', 'transaction_id', 'deleted_at']
        );

        $this->addIndex(
            'media',
            'sup_media_supplier_lookup_idx',
            ['business_id', 'model_type', 'model_id', 'created_at'],
            ['`business_id`', '`model_type`(100)', '`model_id`', '`created_at`']
        );

        $this->addIndex(
            'notes',
            'sup_notes_supplier_lookup_idx',
            ['business_id', 'notable_type', 'notable_id', 'created_at'],
            ['`business_id`', '`notable_type`(100)', '`notable_id`', '`created_at`']
        );

        $this->addIndex(
            'activity_log',
            'sup_activity_supplier_lookup_idx',
            ['subject_type', 'subject_id', 'created_at'],
            ['`subject_type`(100)', '`subject_id`', '`created_at`']
        );

        $this->addIndex(
            'products',
            'sup_products_business_name_idx',
            ['business_id', 'name'],
            ['`business_id`', '`name`(100)']
        );
    }

    public function down(): void
    {
        foreach ([
            'contacts' => ['sup_contacts_business_type_active_idx'],
            'transactions' => [
                'sup_transactions_contact_date_idx',
                'sup_transactions_contact_type_status_idx',
            ],
            'transaction_payments' => [
                'sup_payments_supplier_date_idx',
                'sup_payments_transaction_parent_idx',
            ],
            'purchase_lines' => ['sup_purchase_lines_transaction_product_idx'],
            'contact_ledgers' => ['sup_contact_ledgers_contact_transaction_idx'],
            'media' => ['sup_media_supplier_lookup_idx'],
            'notes' => ['sup_notes_supplier_lookup_idx'],
            'activity_log' => ['sup_activity_supplier_lookup_idx'],
            'products' => ['sup_products_business_name_idx'],
        ] as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->dropIndex($table, $index);
            }
        }
    }

    private function addIndex(
        string $table,
        string $index,
        array $requiredColumns,
        ?array $indexDefinitions = null
    ): void {
        if (! Schema::hasTable($table) || $this->indexExists($table, $index)) {
            return;
        }

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        $definitions = $indexDefinitions ?: array_map(
            static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`',
            $requiredColumns
        );

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (%s)',
            str_replace('`', '``', $table),
            str_replace('`', '``', $index),
            implode(', ', $definitions)
        ));
    }

    private function dropIndex(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $index)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP INDEX `%s`',
            str_replace('`', '``', $table),
            str_replace('`', '``', $index)
        ));
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
