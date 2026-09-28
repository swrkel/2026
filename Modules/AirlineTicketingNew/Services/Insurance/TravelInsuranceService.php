<?php
namespace Modules\AirlineTicketingNew\Services\Insurance;

use Modules\AirlineTicketingNew\Entities\TravelInsurancePolicy;
use Modules\AirlineTicketingNew\Support\TravelServices\ServiceNumber;

class TravelInsuranceService
{
    public function __construct(private readonly ServiceNumber $numbers)
    {
    }

    public function issue(array $data): TravelInsurancePolicy
    {
        return TravelInsurancePolicy::query()->create(array_merge($data, [
            'policy_no' => $this->numbers->next($data, 'insurance_policy', 'ATIP'),
            'status' => 'issued',
        ]));
    }
}
