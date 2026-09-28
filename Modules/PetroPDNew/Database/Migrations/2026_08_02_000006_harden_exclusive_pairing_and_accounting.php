<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSettlementAccountingColumns();
        $this->enforcePumperDashboardNewPairing();
    }

    public function down(): void
    {
        // Release hardening is intentionally retained. Removing these fields or
        // the one-shift/one-settlement key could invalidate finalized records.
    }

    private function addSettlementAccountingColumns(): void
    {
        if (! Schema::hasTable('pdnew_settlements')) {
            return;
        }

        $definitions = [
            'source_declared_total',
            'source_shortage_total',
            'source_excess_total',
            'manual_shortage_total',
            'manual_excess_total',
            'shortage_recovery_total',
            'excess_commission_total',
            'expected_adjustments_total',
            'received_adjustments_total',
            'operational_variance_amount',
        ];

        foreach ($definitions as $column) {
            if (Schema::hasColumn('pdnew_settlements', $column)) {
                continue;
            }

            Schema::table('pdnew_settlements', function (Blueprint $table) use ($column): void {
                $table->decimal($column, 22, 4)->default(0);
            });
        }
    }

    private function enforcePumperDashboardNewPairing(): void
    {
        foreach ([
            'pone_module_settings',
            'pone_shifts',
            'pone_shift_settlement_references',
        ] as $requiredTable) {
            if (! Schema::hasTable($requiredTable)) {
                throw new \RuntimeException(
                    'Petro PD-New requires Pumper Dashboard-New to be installed first. Missing table: '
                    . $requiredTable
                );
            }
        }

        if (Schema::hasColumn('pone_module_settings', 'integration_mode')) {
            DB::statement(
                "ALTER TABLE `pone_module_settings` MODIFY COLUMN `integration_mode` "
                . "ENUM('petro_pd_new','petropd','local_only') NOT NULL DEFAULT 'petro_pd_new'"
            );
        }

        DB::table('pone_module_settings')->update([
            'integration_enabled' => 1,
            'integration_mode' => 'petro_pd_new',
            'sync_during_operation' => 1,
            'require_clean_sync_before_close' => 1,
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('pone_integration_links')) {
            // Petro PD-New uses a pull-based immutable snapshot contract.  All
            // historical push-link rows therefore become audit-only records;
            // no target table is invoked or required by the paired modules.
            DB::table('pone_integration_links')
                ->where('status', '<>', 'retired')
                ->update([
                    'status' => 'retired',
                    'last_error' => 'Retired: Pumper Dashboard-New is paired exclusively with Petro PD-New.',
                    'updated_at' => now(),
                ]);
        }

        foreach ([
            'pone_shifts',
            'pone_pump_assignments',
            'pone_payments',
            'pone_other_sales',
            'pone_unload_stocks',
            'pone_day_entries',
        ] as $sourceTable) {
            if (! Schema::hasTable($sourceTable) || ! Schema::hasColumn($sourceTable, 'integration_status')) {
                continue;
            }

            $query = DB::table($sourceTable)->whereIn('integration_status', ['pending', 'failed']);
            if ($sourceTable === 'pone_shifts') {
                $query->where('status', 'closed');
            }

            $values = [
                'integration_status' => 'synced',
                'integration_error' => null,
            ];
            if (Schema::hasColumn($sourceTable, 'updated_at')) {
                $values['updated_at'] = now();
            }
            $query->update($values);
        }

        $duplicateGroups = (int) (DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM ('
            . ' SELECT `business_id`, `shift_id`'
            . ' FROM `pone_shift_settlement_references`'
            . ' GROUP BY `business_id`, `shift_id`'
            . ' HAVING COUNT(*) > 1'
            . ') AS duplicate_reference_groups'
        )->aggregate ?? 0);

        if ($duplicateGroups > 0) {
            throw new \RuntimeException(
                'Duplicate Pumper Dashboard-New settlement references exist for the same business and shift. '
                . 'Resolve the duplicates before installing Petro PD-New.'
            );
        }

        $indexExists = (int) (DB::selectOne(
            "SELECT COUNT(*) AS aggregate FROM information_schema.statistics "
            . "WHERE table_schema = DATABASE() "
            . "AND table_name = 'pone_shift_settlement_references' "
            . "AND index_name = 'pone_settlement_business_shift_uq'"
        )->aggregate ?? 0) > 0;

        if (! $indexExists) {
            DB::statement(
                'ALTER TABLE `pone_shift_settlement_references` '
                . 'ADD UNIQUE KEY `pone_settlement_business_shift_uq` (`business_id`,`shift_id`)'
            );
        }
    }
};
