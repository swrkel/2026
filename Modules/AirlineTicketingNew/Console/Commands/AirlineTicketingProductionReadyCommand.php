<?php
namespace Modules\AirlineTicketingNew\Console\Commands;
use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Readiness\ProductionReadinessService;
class AirlineTicketingProductionReadyCommand extends Command {
 protected $signature='airline-ticketing-new:production-ready';
 protected $description='Run final production readiness check';
 public function handle(ProductionReadinessService $service): int {$r=$service->check();$this->line(json_encode($r,JSON_PRETTY_PRINT));return $r['status']==='ready'?self::SUCCESS:self::FAILURE;}
}
