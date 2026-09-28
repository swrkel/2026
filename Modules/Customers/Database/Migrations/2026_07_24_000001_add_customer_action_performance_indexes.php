<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $ledgerDateColumn = $this->firstExistingColumn('contact_ledgers', [
            'operation_date',
            'transaction_date',
            'created_at',
        ]);

        $this->addIndexIfMissing(
            'contact_ledgers',
            array_values(array_filter([
                'business_id',
                'contact_id',
                Schema::hasColumn('contact_ledgers', 'deleted_at') ? 'deleted_at' : null,
                $ledgerDateColumn,
                Schema::hasColumn('contact_ledgers', 'id') ? 'id' : null,
            ])),
            'cus_cl_biz_contact_date_idx'
        );

        $this->addIndexIfMissing(
            'customer_notes',
            ['business_id', 'customer_id', 'created_at'],
            'cus_notes_biz_customer_date_idx'
        );

        $this->addIndexIfMissing(
            'customer_activities',
            ['business_id', 'customer_id', 'created_at'],
            'cus_activities_biz_customer_date_idx'
        );

        $this->addIndexIfMissing(
            'customer_attachments',
            ['business_id', 'customer_id', 'created_at'],
            'cus_attach_biz_customer_date_idx'
        );

        $this->addIndexIfMissing(
            'business_locations',
            array_values(array_filter([
                'business_id',
                Schema::hasColumn('business_locations', 'is_active') ? 'is_active' : null,
                Schema::hasColumn('business_locations', 'id') ? 'id' : null,
            ])),
            'cus_locations_biz_active_idx'
        );

        $this->addIndexIfMissing(
            'accounts',
            array_values(array_filter([
                'business_id',
                Schema::hasColumn('accounts', 'asset_type') ? 'asset_type' : null,
                Schema::hasColumn('accounts', 'is_closed') ? 'is_closed' : null,
            ])),
            'cus_accounts_biz_group_idx'
        );
    }

    public function down(): void
    {
        foreach ([
            'contact_ledgers' => 'cus_cl_biz_contact_date_idx',
            'customer_notes' => 'cus_notes_biz_customer_date_idx',
            'customer_activities' => 'cus_activities_biz_customer_date_idx',
            'customer_attachments' => 'cus_attach_biz_customer_date_idx',
            'business_locations' => 'cus_locations_biz_active_idx',
            'accounts' => 'cus_accounts_biz_group_idx',
        ] as $table => $index) {
            $this->dropIndexIfExists($table, $index);
        }
    }

    private function addIndexIfMissing(string $table, array $columns, string $index): void
    {
        if (!Schema::hasTable($table) || count($columns) < 2 || $this->indexExists($table, $index)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $index) {
                $blueprint->index($columns, $index);
            });
        } catch (\Throwable $e) {
            // Do not block a tenant migration when an equivalent manual index
            // already exists under another name or the database restricts DDL.
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
            // Safe rollback for tenant databases with restricted DDL permissions.
        }
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        if (!Schema::hasTable($table)) {
            return null;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function indexExists(string $table, string $index): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        try {
            $tableName = DB::getTablePrefix() . $table;
            $rows = DB::select('SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '`');

            foreach ($rows as $row) {
                $keyName = $row->Key_name ?? $row->key_name ?? null;
                if ((string) $keyName === $index) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
};
