<?php

namespace Modules\SimpleAudit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\SimpleAudit\Services\PurchaseAuditService;
use Modules\SimpleAudit\Services\TenantConnectionManager;
use Throwable;

class TestPurchaseAuditCommand extends Command
{
    protected $signature = 'simple-audit:test-report
        {--tenant= : Tenant ID}
        {--business= : Business ID; defaults to first active business}
        {--location= : Location ID; defaults to first active location for the business}
        {--from= : Start date YYYY-MM-DD; defaults to first day of current year}
        {--to= : End date YYYY-MM-DD; defaults to today}';

    protected $description = 'Low-memory backend test of the Purchase Audit report for one tenant/business/location.';

    public function handle(TenantConnectionManager $tenants, PurchaseAuditService $audit)
    {
        try {
            $tenantId = $this->option('tenant') ?: null;
            $connection = $tenants->connectionForTenant($tenantId);
            $database = DB::connection($connection)->getDatabaseName();

            $businessId = (int) ($this->option('business') ?: 0);
            if (!$businessId) {
                $businessId = (int) DB::connection($connection)->table('business')
                    ->where(function ($q) {
                        $q->whereNull('is_active')->orWhere('is_active', 1);
                    })
                    ->orderBy('name')
                    ->value('id');
            }
            if (!$businessId) {
                $this->error('No active business was found in ' . $database . '.');
                return self::FAILURE;
            }

            $locationId = (int) ($this->option('location') ?: 0);
            if (!$locationId) {
                $locationId = (int) DB::connection($connection)->table('business_locations')
                    ->where('business_id', $businessId)
                    ->whereNull('deleted_at')
                    ->where('is_active', 1)
                    ->orderBy('name')
                    ->value('id');
            }

            $filters = [
                'business_id' => $businessId,
                'location_id' => $locationId ?: null,
                'store_id' => null,
                'from' => $this->option('from') ?: date('Y-01-01'),
                'to' => $this->option('to') ?: date('Y-m-d'),
            ];

            $this->line('Tenant: ' . ($tenantId ?: '[current]'));
            $this->line('Database: ' . $database);
            $this->line('Business ID: ' . $businessId);
            $this->line('Location ID: ' . ($locationId ?: '[all]'));
            $this->line('Period: ' . $filters['from'] . ' to ' . $filters['to']);

            $report = $audit->build($connection, $filters, $tenantId);

            foreach (['purchases','stock_movements','supplier_payments','accounts','supplier_ledgers'] as $section) {
                $count = count($report['sections'][$section]['rows'] ?? []);
                $this->line('[OK] ' . $section . ': ' . $count . ' rows');
            }
            $this->line('[OK] audit_changes: ' . (int) ($report['audit_changes']['total'] ?? 0));
            $this->info('Result: PASS - Purchase Audit backend report built successfully.');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Result: FAILED - ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
