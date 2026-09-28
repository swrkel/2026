<?php

namespace Modules\LeadsNew\Console;

use Illuminate\Console\Command;
use Modules\LeadsNew\Services\LeadsNewFinalAuditService;

class VerifyLeadsNewCommand extends Command
{
    protected $signature = 'leads-new:verify';
    protected $description = 'Verify Leads-New standalone module registration and dependency isolation.';

    public function handle(LeadsNewFinalAuditService $audit): int
    {
        $result = $audit->scan(base_path('Modules/LeadsNew'));
        $this->info('Leads-New audit status: ' . $result['status']);

        foreach ($result['findings'] as $finding) {
            $this->warn($finding['file'] . ' => ' . $finding['match']);
        }

        return $result['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
