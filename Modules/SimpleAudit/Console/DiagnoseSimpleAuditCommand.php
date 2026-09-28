<?php

namespace Modules\SimpleAudit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SimpleAudit\Services\TenantConnectionManager;
use Throwable;

class DiagnoseSimpleAuditCommand extends Command
{
    protected $signature = 'simple-audit:diagnose {--tenant= : Tenant ID}';
    protected $description = 'Check Simple Audit source tables, sau_ tables, trigger installation and tenant connection.';

    public function handle(TenantConnectionManager $tenants)
    {
        try {
            $tenantId = $this->option('tenant') ?: null;
            if ($tenantId) {
                $resolved = $tenants->resolveTenantDatabaseName($tenantId);
                $this->line('Tenant ID: ' . $tenantId . ' / Resolved DB: ' . ($resolved ?: '[not resolved]'));
            }
            $connection = $tenants->connectionForTenant($tenantId);
            $database = DB::connection($connection)->getDatabaseName();
            $this->info('Connection: ' . $connection . ' / DB: ' . $database);

            $requiredSource = ['business','business_locations','transactions','purchase_lines','transaction_payments','contacts','accounts','account_transactions','products','variations','variation_location_details'];
            $optionalSource = ['stores','stock_adjustment_lines','contact_ledgers'];
            $module = ['sau_settings','sau_change_events','sau_stock_snapshots','sau_report_shares','sau_activity_logs'];

            foreach ($requiredSource as $table) {
                $this->line(($this->has($connection,$table) ? '[OK] ' : '[MISSING] ') . $table);
            }
            foreach ($optionalSource as $table) {
                $this->line(($this->has($connection,$table) ? '[OK optional] ' : '[not present optional] ') . $table);
            }
            foreach ($module as $table) {
                $this->line(($this->has($connection,$table) ? '[OK module] ' : '[MISSING module] ') . $table);
            }

            if ($this->has($connection, 'account_transactions')) {
                $hasLocation = Schema::connection($connection)->hasColumn('account_transactions', 'location_id');
                $this->line('account_transactions.location_id: ' . ($hasLocation ? 'PRESENT' : 'NOT PRESENT - supported by v1.0.9 compatibility mode'));
            }

            if ($this->has($connection, 'sau_stock_snapshots')) {
                $this->line('Stock snapshots: ' . DB::connection($connection)->table('sau_stock_snapshots')->count());
            }
            if ($this->has($connection, 'sau_change_events')) {
                $this->line('Captured change events: ' . DB::connection($connection)->table('sau_change_events')->count());
            }

            $expectedTriggers = [
                'sau_vld_ai','sau_vld_au','sau_vld_ad',
                'sau_transactions_ai','sau_transactions_au','sau_transactions_ad',
                'sau_purchase_lines_ai','sau_purchase_lines_au','sau_purchase_lines_ad',
                'sau_stock_adjustment_lines_ai','sau_stock_adjustment_lines_au','sau_stock_adjustment_lines_ad',
                'sau_transaction_payments_ai','sau_transaction_payments_au','sau_transaction_payments_ad',
                'sau_account_transactions_ai','sau_account_transactions_au','sau_account_transactions_ad',
                'sau_contact_ledgers_ai','sau_contact_ledgers_au','sau_contact_ledgers_ad',
            ];

            $triggerCount = (int) DB::connection($connection)->table('information_schema.TRIGGERS')
                ->where('TRIGGER_SCHEMA', $database)
                ->whereIn('TRIGGER_NAME', $expectedTriggers)
                ->count();
            $this->line('Simple Audit triggers: ' . $triggerCount . ' / 21');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    protected function has($connection, $table)
    {
        return Schema::connection($connection)->hasTable($table);
    }
}
