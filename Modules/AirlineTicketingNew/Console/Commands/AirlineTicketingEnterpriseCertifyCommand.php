<?php
namespace Modules\AirlineTicketingNew\Console\Commands;
use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Certification\EnterpriseCertificationService;
class AirlineTicketingEnterpriseCertifyCommand extends Command {
 protected $signature='airline-ticketing-new:enterprise-certify {business_id}';
 protected $description='Run final Airline Ticketing New enterprise certification';
 public function handle(EnterpriseCertificationService $service): int {$r=$service->run((int)$this->argument('business_id'));$this->line(json_encode($r,JSON_PRETTY_PRINT));return $r['status']==='passed'?self::SUCCESS:self::FAILURE;}
}
