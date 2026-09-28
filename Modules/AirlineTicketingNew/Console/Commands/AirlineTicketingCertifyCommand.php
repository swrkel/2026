<?php
namespace Modules\AirlineTicketingNew\Console\Commands;

use Illuminate\Console\Command;
use Modules\AirlineTicketingNew\Services\Certification\CertificationService;

class AirlineTicketingCertifyCommand extends Command
{
    protected $signature = 'airline-ticketing-new:certify {business_id}';
    protected $description = 'Run Airline Ticketing New production certification checks';

    public function handle(CertificationService $service): int
    {
        $result = $service->run((int) $this->argument('business_id'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return $result['status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
