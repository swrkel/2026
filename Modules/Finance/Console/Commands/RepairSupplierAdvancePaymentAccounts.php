<?php

namespace Modules\Finance\Console\Commands;

use Illuminate\Console\Command;
use Modules\Finance\Services\Payments\SupplierAdvancePaymentAccountNormalizer;

class RepairSupplierAdvancePaymentAccounts extends Command
{
    protected $signature = 'finance:repair-supplier-advance-payment-accounts
                            {--business_id= : Limit the repair to one business ID}
                            {--dry-run : Report repairable rows without changing data}';

    protected $description = 'Move supplier advance bank-transfer entries wrongly posted to Cash onto their selected/configured payment account.';

    public function handle(SupplierAdvancePaymentAccountNormalizer $normalizer): int
    {
        $businessId = $this->option('business_id');
        $businessId = $businessId !== null && $businessId !== '' ? (int) $businessId : null;
        $dryRun = (bool) $this->option('dry-run');

        $stats = $normalizer->repairHistorical($businessId, $dryRun);

        $this->table(
            ['Scanned', 'Repairable', 'Fixed', 'Unresolved'],
            [[
                $stats['scanned'],
                $stats['repairable'],
                $stats['fixed'],
                $stats['unresolved'],
            ]]
        );

        if ($dryRun) {
            $this->info('Dry run only. No rows were changed.');
        } else {
            $this->info('Supplier advance payment account repair completed.');
        }

        if ($stats['unresolved'] > 0) {
            $this->warn('Some rows could not be resolved safely because no selected/configured non-Cash bank-transfer account was available. Those rows were left unchanged.');
        }

        return 0;
    }
}
