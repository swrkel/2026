<?php

namespace Modules\PumperDashboardNew\Services;

use Illuminate\Support\Facades\Schema;
use Modules\PumperDashboardNew\Database\Seeders\PumperDashboardNewPermissionSeeder;

class PoneSchemaService
{
    private ?bool $installed = null;

    public const TABLES = [
        'pone_login_attempts',
        'pone_pd_operators',
        'pone_operator_sessions',
        'pone_module_settings',
        'pone_number_sequences',
        'pone_shifts',
        'pone_pump_assignments',
        'pone_meter_readings',
        'pone_payments',
        'pone_credit_sales',
        'pone_credit_sale_lines',
        'pone_other_sales',
        'pone_other_sale_lines',
        'pone_unload_stocks',
        'pone_unload_stock_lines',
        'pone_day_entries',
        'pone_integration_outbox',
        'pone_integration_links',
        'pone_audit_logs',
        'pone_assignment_events',
        'pone_payment_cash_denominations',
        'pone_payment_card_lines',
        'pone_payment_edit_histories',
        'pone_daily_collections',
        'pone_shift_settlement_references',
        'pone_shortage_recoveries',
        'pone_excess_commissions',
        'pone_operator_ledger_entries',
        'pone_operator_documents',
        'pone_operator_notes',
        'pone_print_logs',
    ];

    private const REQUIRED_COLUMNS = [
        'pone_pd_operators' => ['pd_operator_id', 'display_name', 'login_enabled', 'status'],
        'pone_operator_sessions' => ['session_key', 'pd_operator_id', 'shift_id', 'shift_number', 'last_seen_at'],
        'pone_module_settings' => ['cash_denomination_enabled'],
        'pone_shifts' => ['collection_form_no', 'reconciliation_status'],
        'pone_payments' => ['edit_version'],
        'pone_integration_links' => ['status'],
    ];

    private const RELEASE_MIGRATIONS = [
        '2026_08_01_000001_create_pumper_dashboard_new_tables.php',
        '2026_08_01_000005_harden_pumper_dashboard_new_release.php',
        '2026_08_01_000003_extend_pumper_dashboard_new_full_operations.php',
    ];

    public function isInstalled(): bool
    {
        if ($this->installed !== null) {
            return $this->installed;
        }

        try {
            foreach (self::TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    return $this->installed = false;
                }
            }

            foreach (self::REQUIRED_COLUMNS as $table => $columns) {
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        return $this->installed = false;
                    }
                }
            }

            return $this->installed = true;
        } catch (\Throwable) {
            return $this->installed = false;
        }
    }

    public function installIfMissing(): void
    {
        if (! $this->isInstalled()) {
            $this->install();
        }
    }

    public function install(): void
    {
        $migrationDirectory = dirname(__DIR__) . '/Database/Migrations';

        foreach (self::RELEASE_MIGRATIONS as $migrationFile) {
            $migration = require $migrationDirectory . '/' . $migrationFile;
            $migration->up();
        }

        (new PumperDashboardNewPermissionSeeder())->run();
        $this->installed = null;
    }
}
