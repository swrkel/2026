<?php
namespace Modules\AirlineTicketingNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Deployment\DeploymentReadinessService;

class AirlineTicketingDeployCheckCommand extends Command
{
    protected $signature = 'airline-ticketing-new:deploy-check';
    protected $description = 'Check Airline Ticketing New deployment readiness';

    public function handle(DeploymentReadinessService $service): int
    {
        $result = $service->check();
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return $result['status'] === 'ready' ? self::SUCCESS : self::FAILURE;
    }
}
