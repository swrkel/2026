<?php

namespace Modules\StockTakingNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\StockTakingNew\Services\ScheduleService;
use Modules\StockTakingNew\Services\StockTakingSchemaService;

class RunScheduledStockTakes extends Command
{
    protected $signature = 'stock-taking-new:run-schedules {--limit=100 : Maximum due schedules to process}';
    protected $description = 'Create prepared Stock Taking - New sessions for schedules due in the current tenant database.';

    public function handle(StockTakingSchemaService $schema, ScheduleService $schedules): int
    {
        if (! $schema->isInstalled()) {
            $schema->install();
        }

        $result = $schedules->runDue((int) $this->option('limit'));
        $this->info("Created {$result['created']} session(s); {$result['failed']} failed.");

        return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
