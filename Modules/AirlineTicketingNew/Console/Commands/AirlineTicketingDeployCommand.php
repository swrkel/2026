<?php
namespace Modules\AirlineTicketingNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Deployment\EnvironmentValidationService;

class AirlineTicketingDeployCommand extends Command
{
    protected $signature = 'airline-ticketing-new:deploy-validate';
    protected $description = 'Validate Airline Ticketing New production environment';

    public function handle(EnvironmentValidationService $service): int
    {
        $result = $service->validate();
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return $result['status'] === 'ready' ? self::SUCCESS : self::FAILURE;
    }
}
