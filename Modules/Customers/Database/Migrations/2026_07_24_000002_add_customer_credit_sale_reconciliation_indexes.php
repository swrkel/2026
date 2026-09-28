<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing(
            'transactions',
            $this->existingColumns('transactions', [
                'business_id',
                'contact_id',
                'type',
                'deleted_at',
                'id',
            ]),
            'cus_tx_biz_contact_type_idx'
        );

        $this->addIndexIfMissing(
            'transactions',
            $this->existingColumns('transactions', [
                'business_id',
                'contact_id',
                'is_credit_sale',
                'deleted_at',
                'id',
            ]),
            'cus_tx_biz_contact_credit_idx'
        );

        $this->addIndexIfMissing(
            'transaction_payments',
            $this->existingColumns('transaction_payments', [
                'business_id',
                'transaction_id',
                'deleted_at',
                'parent_id',
                'id',
            ]),
            'cus_tp_biz_tx_parent_idx'
        );

        $this->addIndexIfMissing(
            'contact_ledgers',
            $this->existingColumns('contact_ledgers', [
                'business_id',
                'contact_id',
                'transaction_id',
                'deleted_at',
            ]),
            'cus_cl_biz_contact_tx_idx'
        );

        $this->addIndexIfMissing(
            'contact_ledgers',
            $this->existingColumns('contact_ledgers', [
                'business_id',
                'contact_id',
                'transaction_payment_id',
                'deleted_at',
            ]),
            'cus_cl_biz_contact_payment_idx'
        );
    }

    public function down(): void
    {
        foreach ([
            'transactions' => [
                'cus_tx_biz_contact_type_idx',
                'cus_tx_biz_contact_credit_idx',
            ],
            'transaction_payments' => [
                'cus_tp_biz_tx_parent_idx',
            ],
            'contact_ledgers' => [
                'cus_cl_biz_contact_tx_idx',
                'cus_cl_biz_contact_payment_idx',
            ],
        ] as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->dropIndexIfExists($table, $index);
            }
        }
    }

    private function existingColumns(string $table, array $columns): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        return array_values(array_filter($columns, function ($column) use ($table) {
            return Schema::hasColumn($table, $column);
        }));
    }

    private function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (!Schema::hasTable($table)
            || count($columns) < 2
            || $this->indexExists($table, $index)
            || $this->equivalentIndexExists($table, $columns)) {
            return;
        }

        // The first two columns are mandatory for every reconciliation query.
        foreach (array_slice($columns, 0, 2) as $requiredColumn) {
            if (!Schema::hasColumn($table, $requiredColumn)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
                $blueprint->index($columns, $index);
            });
        } catch (\Throwable $e) {
            // A tenant may already have an equivalent index under a different
            // name or restricted DDL permissions. Do not stop other tenants.
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $index)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index);
            });
        } catch (\Throwable $e) {
            // Safe tenant rollback.
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        foreach ($this->indexes($table) as $existingIndex => $columns) {
            if ((string) $existingIndex === $index) {
                return true;
            }
        }

        return false;
    }

    private function equivalentIndexExists(string $table, array $columns): bool
    {
        foreach ($this->indexes($table) as $existingColumns) {
            if (array_values($existingColumns) === array_values($columns)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, array<int, string>> */
    private function indexes(string $table): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        try {
            $tableName = DB::getTablePrefix() . $table;
            $rows = DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '`');
            $indexes = [];

            foreach ($rows as $row) {
                $name = (string) ($row->Key_name ?? $row->key_name ?? '');
                $column = (string) ($row->Column_name ?? $row->column_name ?? '');
                $sequence = (int) ($row->Seq_in_index ?? $row->seq_in_index ?? 0);

                if ($name === '' || $column === '' || $sequence <= 0) {
                    continue;
                }

                $indexes[$name][$sequence] = $column;
            }

            foreach ($indexes as &$columns) {
                ksort($columns);
                $columns = array_values($columns);
            }
            unset($columns);

            return $indexes;
        } catch (\Throwable $e) {
            return [];
        }
    }
};
