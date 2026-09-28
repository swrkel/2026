<?php

namespace Modules\LeadsNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\LeadsNew\Services\Release\LeadsNewReleaseValidationService;

class LeadsNewSmokeTestCommand extends Command
{
    protected $signature = 'leadsnew:smoke-test';
    protected $description = 'Run a lightweight Leads-New release smoke-test checklist.';

    public function handle(LeadsNewReleaseValidationService $service): int
    {
        $this->info('Leads-New release smoke-test checklist');
        foreach ($service->checklist() as $item) {
            $this->line('[ ] ' . $item);
        }
        return self::SUCCESS;
    }
}
