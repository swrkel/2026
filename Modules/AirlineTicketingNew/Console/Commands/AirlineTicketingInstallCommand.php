<?php
namespace Modules\AirlineTicketingNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Installer\ModuleInstallerService;

class AirlineTicketingInstallCommand extends Command
{
    protected $signature = 'airline-ticketing-new:install';
    protected $description = 'Install Airline Ticketing New module';

    public function handle(ModuleInstallerService $service): int
    {
        $result = $service->install();
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return $result['status'] === 'installed' ? self::SUCCESS : self::FAILURE;
    }
}
