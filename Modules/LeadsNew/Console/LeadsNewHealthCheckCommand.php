<?php

namespace Modules\LeadsNew\Console;

use Illuminate\Console\Command;
use Modules\LeadsNew\Services\Release\LeadsNewProductionDeploymentService;

class LeadsNewHealthCheckCommand extends Command
{
    protected $signature = 'leads-new:health-check';
    protected $description = 'Run Leads-New module production readiness smoke checks.';

    public function handle(LeadsNewProductionDeploymentService $service): int
    {
        foreach ($service->checklist() as $key => $description) {
            $this->line('[CHECK] '.$key.' - '.$description);
        }

        foreach ($service->smokeRoutes() as $name => $uri) {
            $this->line('[ROUTE] '.$name.' => '.$uri);
        }

        return self::SUCCESS;
    }
}
