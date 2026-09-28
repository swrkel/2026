<?php
namespace Modules\AirlineTicketingNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Scheduler\ModuleSchedulerService;

class AirlineTicketingDailyCommand extends Command
{
    protected $signature = 'airline-ticketing-new:daily {business_id}';
    protected $description = 'Run Airline Ticketing New daily maintenance tasks';

    public function handle(ModuleSchedulerService $service): int
    {
        $result = $service->runDaily((int) $this->argument('business_id'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
