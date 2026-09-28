<?php

namespace Modules\RiceMill\Console;

use App\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\RiceMill\Services\PerformanceIndexService;

/**
 * Apply Rice Mill performance indexes safely to one or all tenant databases.
 * No central/core table is altered.
 */
class OptimizePerformance extends Command
{
    protected $signature = 'rcm:optimize-performance {--tenant= : Tenant UID} {--all : Apply to every tenant}';

    protected $description = 'Add safe Rice Mill performance indexes to tenant database(s).';

    public function handle(PerformanceIndexService $indexes): int
    {
        $tenantId = trim((string) $this->option('tenant'));
        $all = (bool) $this->option('all');

        if (($tenantId === '' && ! $all) || ($tenantId !== '' && $all)) {
            $this->error('Use either --tenant=TENANT_UID or --all.');
            return 1;
        }

        // Resolve IDs before tenancy is switched so discovery always occurs on
        // the central connection. Tenant::find() is then performed while the
        // central context is active for each iteration.
        $tenantIds = $all
            ? Tenant::query()->pluck('id')->map(static fn ($id) => (string) $id)->all()
            : [$tenantId];

        if (! $tenantIds) {
            $this->warn('No tenants found.');
            return 0;
        }

        $failed = 0;
        foreach ($tenantIds as $id) {
            try {
                $tenant = Tenant::find($id);
                if (! $tenant) {
                    $failed++;
                    $this->error('Tenant '.$id.' was not found.');
                    continue;
                }

                tenancy()->initialize($tenant);
                $database = DB::connection()->getDatabaseName();
                $result = $indexes->apply();

                $this->info(
                    'Tenant '.$id.' ['.$database.']: '
                    .$result['created'].' index(es) created, '
                    .$result['existing'].' already present, '
                    .$result['missing'].' skipped because table/column is not present.'
                );
            } catch (\Throwable $e) {
                $failed++;
                $this->error('Tenant '.$id.': '.$e->getMessage());
            } finally {
                try { tenancy()->end(); } catch (\Throwable $e) { /* no-op */ }
            }
        }

        return $failed > 0 ? 1 : 0;
    }
}
