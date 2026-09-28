<?php

namespace Modules\Finance\Console\Commands;

use App\Business;
use Illuminate\Console\Command;
use Modules\Finance\Services\FinanceAccountNumberService;

class SyncSuperadminAccountNumbers extends Command
{
    protected $signature = 'finance:sync-superadmin-account-numbers
                            {--business_id= : Sync only one business ID}';

    protected $description = 'Renumber existing Finance accounts from Super Admin Default Accounts -> Account Numbers.';

    public function handle(FinanceAccountNumberService $numbering): int
    {
        $requestedBusinessId = trim((string) $this->option('business_id'));

        $businessIds = Business::query()
            ->when($requestedBusinessId !== '', function ($query) use ($requestedBusinessId) {
                $query->where('id', (int) $requestedBusinessId);
            })
            ->orderBy('id')
            ->pluck('id');

        if ($businessIds->isEmpty()) {
            $this->warn('No matching businesses were found on the current database connection.');
            return 0;
        }

        $rows = [];
        $totalUpdated = 0;

        foreach ($businessIds as $businessId) {
            try {
                $result = $numbering->renumberExistingAccountsForBusiness((int) $businessId);
                $updated = (int) ($result['updated_accounts'] ?? 0);
                $series = (int) ($result['configured_series'] ?? 0);
                $totalUpdated += $updated;
                $rows[] = [(int) $businessId, $series, $updated, 'OK'];
            } catch (\Throwable $e) {
                $rows[] = [(int) $businessId, '-', '-', $e->getMessage()];
            }
        }

        $this->table(['Business ID', 'Configured Series', 'Accounts Updated', 'Status'], $rows);
        $this->info('Completed. Total Accounts renumbered: ' . $totalUpdated);

        return 0;
    }
}
