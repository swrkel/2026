<?php

namespace Modules\RestaurantNew\Reports\Ai;

use Modules\RestaurantNew\Services\Ai\RestaurantAiOperationsService;

class RestaurantAiOperationsReport
{
    protected RestaurantAiOperationsService $service;
    public function __construct(RestaurantAiOperationsService $service) { $this->service = $service; }
    public function data(int $businessId, ?int $locationId = null): array { return $this->service->commandCenter($businessId, $locationId); }
}
