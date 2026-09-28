<?php

namespace Modules\PetroPD\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\PetroPD\Services\PetroPdHistoricalPaymentRepairService;
use Stancl\Tenancy\Facades\Tenancy;
use Throwable;

class RepairPetroPdPaymentIntegrity extends Command
{
    protected $signature = 'petropd:repair-payment-integrity
        {--tenant= : Tenant id/subdomain}
        {--all-tenants : Audit every tenant intentionally}
        {--business_id= : Optional business filter inside the tenant}
        {--shift_id= : Optional exact Shift ID filter}
        {--settlement_no= : Optional settlement reference filter}
        {--apply : Apply only conservative, unambiguous link/scope repairs}';

    protected $description = 'Audit PetroPD master/detail payment integrity and optionally apply only unambiguous historical repairs.';

    public function handle(PetroPdHistoricalPaymentRepairService $service): int
    {
        if ($this->option('all-tenants')) {
            if ($this->option('apply') && ! $this->confirm('Apply safe repairs to ALL tenants?', false)) {
                $this->warn('Cancelled.');
                return self::SUCCESS;
            }

            $failed = 0;
            Tenant::query()->orderBy('id')->chunk(20, function ($tenants) use ($service, &$failed) {
                foreach ($tenants as $tenant) {
                    try {
                        $this->runForTenant($tenant, $service);
                    } catch (Throwable $e) {
                        $failed++;
                        $this->error("Tenant {$tenant->id}: {$e->getMessage()}");
                    }
                }
            });

            return $failed === 0 ? self::SUCCESS : self::FAILURE;
        }

        if ($tenantId = $this->option('tenant')) {
            $tenant = Tenant::where('id', $tenantId)->first();
            if (! $tenant) {
                $this->error('Tenant not found: ' . $tenantId);
                return self::FAILURE;
            }

            return $this->runForTenant($tenant, $service);
        }

        $this->warn('No tenant option supplied. Running on the CURRENT database connection only.');
        return $this->runCurrent($service, null);
    }

    private function runForTenant(Tenant $tenant, PetroPdHistoricalPaymentRepairService $service): int
    {
        $this->newLine();
        $this->info('Tenant: ' . $tenant->id);
        Tenancy::initialize($tenant);

        try {
            return $this->runCurrent($service, (string) $tenant->id);
        } finally {
            Tenancy::end();
        }
    }

    private function runCurrent(PetroPdHistoricalPaymentRepairService $service, ?string $tenantReference): int
    {
        $apply = (bool) $this->option('apply');
        $this->line('Database: ' . DB::connection()->getDatabaseName());
        $this->line('Mode: ' . ($apply ? 'APPLY SAFE REPAIRS' : 'DRY-RUN AUDIT'));

        $result = $service->run([
            'tenant_reference' => $tenantReference,
            'business_id' => $this->option('business_id') ? (int) $this->option('business_id') : null,
            'shift_id' => $this->option('shift_id') ? (int) $this->option('shift_id') : null,
            'settlement_no' => $this->option('settlement_no') ?: null,
            'apply' => $apply,
        ]);

        $this->table(
            ['Metric', 'Value'],
            collect([
                'Scanned rows' => $result['scanned_rows'] ?? 0,
                'Issues found' => $result['issues_found'] ?? 0,
                'Critical issues' => $result['critical_issues'] ?? 0,
                'Warnings' => $result['warnings'] ?? 0,
                'Safe repairs found' => $result['safe_repairs_found'] ?? 0,
                'Repairs applied' => $result['repairs_applied'] ?? 0,
                'Unlinked rows' => $result['unlinked_rows'] ?? 0,
                'Ambiguous rows' => $result['ambiguous_rows'] ?? 0,
                'Scope mismatches' => $result['scope_mismatches'] ?? 0,
                'Amount mismatches' => $result['amount_mismatches'] ?? 0,
                'Duplicate supporting rows' => $result['duplicate_supporting_rows'] ?? 0,
                'Master rows missing Shift ID' => $result['missing_master_shift'] ?? 0,
            ])->map(fn ($value, $label) => [$label, $value])->values()->all()
        );

        if (! $apply) {
            $this->warn('Dry run only. Re-run with --apply to perform only the listed safe repairs.');
        }
        if (($result['critical_issues'] ?? 0) > 0) {
            $this->error('Critical issues remain. Review the repair action table and Payment Reconciliation Report before finalizing affected settlements.');
        }

        return self::SUCCESS;
    }
}
