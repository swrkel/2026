<?php
namespace Modules\AirlineTicketingNew\Console\Commands;
use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Installer\FinalInstallerService;
class AirlineTicketingFinalInstallCommand extends Command {
 protected $signature='airline-ticketing-new:final-install';
 protected $description='Run final Airline Ticketing New production installer';
 public function handle(FinalInstallerService $service): int {$r=$service->install();$this->line(json_encode($r,JSON_PRETTY_PRINT));return $r['status']==='installed'?self::SUCCESS:self::FAILURE;}
}
