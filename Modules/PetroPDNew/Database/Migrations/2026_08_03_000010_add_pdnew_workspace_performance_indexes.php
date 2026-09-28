<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string,array<string,array<int,string>>> */
    private array $indexes = [
        'pdnew_settlements' => [
            'pdnew_settle_scope_status_id_ix' => ['business_id', 'location_id', 'status', 'id'],
        ],
        'pdnew_source_imports' => [
            'pdnew_source_scope_closed_ix' => ['business_id', 'location_id', 'import_status', 'source_closed_at'],
        ],
        'pdnew_operator_mappings' => [
            'pdnew_operator_scope_profile_ix' => ['business_id', 'location_id', 'pone_operator_profile_id', 'status'],
        ],
        'pdnew_reconciliation_issues' => [
            'pdnew_issue_scope_status_ix' => ['business_id', 'status', 'settlement_id'],
        ],
        'pdnew_integration_outbox' => [
            'pdnew_outbox_scope_event_ix' => ['business_id', 'status', 'event_type', 'id'],
        ],
        'pdnew_integration_logs' => [
            'pdnew_log_scope_date_ix' => ['business_id', 'status', 'created_at'],
        ],
        'pdnew_audit_logs' => [
            'pdnew_audit_scope_user_date_ix' => ['business_id', 'location_id', 'user_id', 'created_at'],
        ],
        'pdnew_day_ends' => [
            'pdnew_dayend_scope_status_date_ix' => ['business_id', 'location_id', 'status', 'day_end_date'],
        ],
        'pone_shifts' => [
            'pone_shift_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'status', 'opened_at'],
        ],
        'pone_pump_assignments' => [
            'pone_assignment_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'status', 'assigned_at'],
            'pone_assignment_pump_status_ix' => ['business_id', 'location_id', 'pump_id', 'status'],
        ],
        'pone_payments' => [
            'pone_payment_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'payment_type', 'status', 'transaction_at'],
        ],
        'pone_day_entries' => [
            'pone_dayentry_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'status', 'entry_at'],
        ],
        'pone_meter_readings' => [
            'pone_meter_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'reading_type', 'recorded_at'],
        ],
        'pone_unload_stocks' => [
            'pone_unload_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'status', 'unloaded_at'],
        ],
        'pone_shortage_recoveries' => [
            'pone_shortage_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'status', 'recovery_date'],
        ],
        'pone_excess_commissions' => [
            'pone_commission_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'status', 'commission_date'],
        ],
        'pone_operator_ledger_entries' => [
            'pone_ledger_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'status', 'entry_at'],
        ],
        'pone_daily_collections' => [
            'pone_collection_workspace_ix' => ['business_id', 'location_id', 'operator_profile_id', 'status', 'collection_at'],
        ],
        'pone_shift_settlement_references' => [
            'pone_setref_scope_shift_ix' => ['business_id', 'shift_id', 'settlement_date'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($indexes as $name => $columns) {
                if ($this->indexExists($table, $name) || ! $this->columnsExist($table, $columns)) {
                    continue;
                }

                $quotedColumns = implode(',', array_map(
                    static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`',
                    $columns
                ));

                DB::statement(
                    'ALTER TABLE `' . str_replace('`', '``', $table) . '` ' .
                    'ADD INDEX `' . str_replace('`', '``', $name) . '` (' . $quotedColumns . ')'
                );
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($indexes) as $name) {
                if (! $this->indexExists($table, $name)) {
                    continue;
                }

                DB::statement(
                    'ALTER TABLE `' . str_replace('`', '``', $table) . '` ' .
                    'DROP INDEX `' . str_replace('`', '``', $name) . '`'
                );
            }
        }
    }

    /** @param array<int,string> $columns */
    private function columnsExist(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
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
