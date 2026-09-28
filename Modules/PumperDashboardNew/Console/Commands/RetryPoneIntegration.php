<?php

namespace Modules\PumperDashboardNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\PumperDashboardNew\Services\Integration\PoneIntegrationProcessor;

class RetryPoneIntegration extends Command
{
    protected $signature = 'pumper-dashboard-new:sync
        {--business= : Restrict processing to one business ID}
        {--limit=100 : Maximum outbox records to process}';

    protected $description = 'Reprocess Pumper Dashboard-New source-ready events for Petro PD-New.';

    public function handle(PoneIntegrationProcessor $processor): int
    {
        $businessId = (int) $this->option('business');
        $limit = min(1000, max(1, (int) $this->option('limit')));
        $result = $processor->processPending($businessId > 0 ? $businessId : null, $limit);

        $this->info(sprintf(
            'Pumper Dashboard-New integration complete: %d processed, %d failed, %d skipped because another worker held the claim.',
            $result['processed'],
            $result['failed'],
            $result['skipped'] ?? 0
        ));

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
