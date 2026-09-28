<?php
namespace Modules\AirlineTicketingNew\Support\TravelServices;

use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class ServiceNumber
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function next(array $scope, string $type, string $prefix): string
    {
        return $this->numbers->next(
            (int) $scope['business_id'],
            $scope['business_location_id'] ?? null,
            $scope['store_id'] ?? null,
            $type,
            $prefix
        );
    }
}
