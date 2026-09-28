<?php

namespace Modules\AirlineTicketingNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Health\ModuleHealthCheckService;

class AirlineTicketingHealthCommand extends Command
{
    protected $signature = 'airline-ticketing-new:health';
    protected $description = 'Run Airline Ticketing New module health checks';

    public function handle(ModuleHealthCheckService $service): int
    {
        $health = $service->run();
        $this->line(json_encode($health, JSON_PRETTY_PRINT));

        return $health['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
    }
}
