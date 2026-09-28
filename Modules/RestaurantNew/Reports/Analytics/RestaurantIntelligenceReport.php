<?php

namespace Modules\RestaurantNew\Reports\Analytics;

use Modules\RestaurantNew\Services\RestaurantAnalyticsService;

class RestaurantIntelligenceReport
{
    protected RestaurantAnalyticsService $analytics;

    public function __construct(RestaurantAnalyticsService $analytics)
    {
        $this->analytics = $analytics;
    }

    public function data(int $businessId, ?int $locationId = null, ?string $from = null, ?string $to = null): array
    {
        return $this->analytics->dashboard($businessId, $locationId, $from, $to);
    }
}
