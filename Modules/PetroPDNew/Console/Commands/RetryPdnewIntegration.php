<?php

namespace Modules\PetroPDNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\PetroPDNew\Services\Integration\PdnewIntegrationProcessor;

class RetryPdnewIntegration extends Command
{
    protected $signature = 'petro-pd-new:retry-integration {--business=} {--limit=100}';
    protected $description = 'Retry pending Petro PD-New internal integration operations.';

    public function handle(PdnewIntegrationProcessor $processor): int
    {
        $businessId = (int) $this->option('business') ?: null;
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $result = $processor->processPending($businessId, $limit);
        $this->info("Processed {$result['processed']}; failed {$result['failed']}; skipped {$result['skipped']}.");
        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
