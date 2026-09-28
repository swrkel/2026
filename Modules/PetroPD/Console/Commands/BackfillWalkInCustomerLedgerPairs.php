<?php

namespace Modules\PetroPD\Console\Commands;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;
use Modules\PetroPD\Services\SettlementRewrite\Ledger\WalkInCustomerLedgerPairService;

class BackfillWalkInCustomerLedgerPairs extends Command
{
    protected $signature = 'petropd:backfill-walkin-ledgers
        {--tenant= : Tenant id/subdomain, e.g. skydirect}
        {--all-tenants : Run for all tenants; use only if you intentionally want every tenant checked}
        {--business_id= : Optional tenant business_id filter}
        {--settlement_no= : Optional settlement number filter}
        {--dry-run : Show what would be processed without creating rows}';

    protected $description = 'Create missing historical Walk-In Customer debit/credit ledger pairs for Petro PD settlement Cash/Card rows.';

    public function handle(WalkInCustomerLedgerPairService $service): int
    {
        $tenantId = $this->option('tenant');
        $allTenants = (bool) $this->option('all-tenants');

        if ($tenantId) {
            $tenant = Tenant::where('id', $tenantId)->first();
            if (! $tenant) {
                $this->error('Tenant not found: ' . $tenantId);
                return self::FAILURE;
            }

            return $this->runForTenant($tenant, $service);
        }

        if ($allTenants) {
            $total = [
                'source_rows_processed' => 0,
                'source_rows_posted_or_verified' => 0,
                'historical_ledger_rows_scanned' => 0,
                'historical_opposite_rows_created' => 0,
                'walkin_contacts_found' => 0,
            ];

            Tenant::query()->orderBy('id')->chunk(25, function ($tenants) use ($service, &$total) {
                foreach ($tenants as $tenant) {
                    $result = $this->executeInTenant($tenant, $service);
                    foreach ($total as $key => $value) {
                        $total[$key] += (int) ($result[$key] ?? 0);
                    }
                }
            });

            $this->line('--- All tenant total ---');
            $this->printResult($total);
            return self::SUCCESS;
        }

        $this->warn('No tenant option supplied. Running on the CURRENT database connection only.');
        $this->warn('For your hosted tenant, run for example: php artisan petropd:backfill-walkin-ledgers --tenant=skydirect --verbose');

        $result = $service->backfill(
            $this->option('business_id') ? (int) $this->option('business_id') : null,
            $this->option('settlement_no') ? (string) $this->option('settlement_no') : null,
            (bool) $this->option('dry-run')
        );

        $this->printResult($result);
        return self::SUCCESS;
    }

    private function runForTenant(Tenant $tenant, WalkInCustomerLedgerPairService $service): int
    {
        $result = $this->executeInTenant($tenant, $service);
        $this->printResult($result);
        return self::SUCCESS;
    }

    private function executeInTenant(Tenant $tenant, WalkInCustomerLedgerPairService $service): array
    {
        $this->line('Tenant: ' . $tenant->id);

        Tenancy::initialize($tenant);

        try {
            $this->line('Database: ' . DB::connection()->getDatabaseName());

            return $service->backfill(
                $this->option('business_id') ? (int) $this->option('business_id') : null,
                $this->option('settlement_no') ? (string) $this->option('settlement_no') : null,
                (bool) $this->option('dry-run')
            );
        } finally {
            Tenancy::end();
        }
    }

    private function printResult(array $result): void
    {
        $this->info('Connection: ' . ($result['connection'] ?? DB::connection()->getName()));
        $this->info('Database: ' . ($result['database'] ?? DB::connection()->getDatabaseName()));
        $this->info('Walk-In contacts found: ' . ($result['walkin_contacts_found'] ?? 0));
        $this->info('Historical ledger rows scanned: ' . ($result['historical_ledger_rows_scanned'] ?? 0));
        $this->info('Historical opposite rows created: ' . ($result['historical_opposite_rows_created'] ?? 0));
        $this->info('Source rows processed: ' . ($result['source_rows_processed'] ?? $result['processed'] ?? 0));
        $this->info('Source rows posted/verified: ' . ($result['source_rows_posted_or_verified'] ?? $result['posted_or_verified'] ?? 0));

        if (! empty($result['dry_run'])) {
            $this->warn('Dry run only. No ledger rows were created.');
        }
    }
}
