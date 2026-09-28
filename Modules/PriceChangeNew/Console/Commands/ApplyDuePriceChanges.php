<?php

namespace Modules\PriceChangeNew\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\PriceChangeNew\Services\PriceApplicationService;
use Stancl\Tenancy\Facades\Tenancy;

class ApplyDuePriceChanges extends Command
{
    protected $signature = 'pricechangenew:apply-due
        {--tenant= : Tenant id/subdomain}
        {--all-tenants : Run for every tenant}
        {--business_id= : Optional business filter inside the tenant}
        {--limit=100 : Maximum due records per tenant}';

    protected $description = 'Apply approved Price Change New records whose effective time has been reached.';

    public function handle(PriceApplicationService $service): int
    {
        $tenantId = $this->option('tenant');
        if ($tenantId) {
            $tenant = Tenant::query()->where('id', $tenantId)->first();
            if (! $tenant) {
                $this->error('Tenant not found: ' . $tenantId);
                return self::FAILURE;
            }
            return $this->runTenant($tenant, $service);
        }

        if ($this->option('all-tenants')) {
            $failed = 0;
            Tenant::query()->orderBy('id')->chunk(25, function ($tenants) use ($service, &$failed): void {
                foreach ($tenants as $tenant) {
                    if ($this->runTenant($tenant, $service) !== self::SUCCESS) $failed++;
                }
            });
            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        }

        $database = (string) DB::connection()->getDatabaseName();
        $tenancyInitialized = false;
        try {
            $tenancyInitialized = function_exists('tenancy') && (bool) tenancy()->initialized;
        } catch (\Throwable $e) {
            $tenancyInitialized = false;
        }
        if (! $tenancyInitialized || $database === '' || $database === 'nivasa_base') {
            $this->error('Refusing to run on the central/unresolved database. Use --tenant=TENANT_ID or --all-tenants.');
            return self::FAILURE;
        }

        $this->line('Tenant database: ' . $database);
        $this->printSummary($service->applyDue(
            $this->option('business_id') ? (int) $this->option('business_id') : null,
            max(1, (int) $this->option('limit')),
            true
        ));
        return self::SUCCESS;
    }

    private function runTenant(Tenant $tenant, PriceApplicationService $service): int
    {
        $this->line('Tenant: ' . $tenant->id);
        Tenancy::initialize($tenant);
        try {
            $this->line('Database: ' . DB::connection()->getDatabaseName());
            $this->printSummary($service->applyDue(
                $this->option('business_id') ? (int) $this->option('business_id') : null,
                max(1, (int) $this->option('limit')),
                true
            ));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            Tenancy::end();
        }
    }

    /** @param array<string,int> $summary */
    private function printSummary(array $summary): void
    {
        $this->info(sprintf(
            'Processed: %d | Applied: %d | Partial: %d | Failed: %d',
            $summary['processed'] ?? 0,
            $summary['applied'] ?? 0,
            $summary['partial'] ?? 0,
            $summary['failed'] ?? 0
        ));
    }
}
