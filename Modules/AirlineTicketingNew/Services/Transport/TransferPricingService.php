<?php
namespace Modules\AirlineTicketingNew\Services\Transport;

use Modules\AirlineTicketingNew\Entities\TransferRoute;

class TransferPricingService
{
    public function price(int $businessId, int $routeId, int $passengers, int $vehicles = 1): array
    {
        $route = TransferRoute::query()
            ->where('business_id', $businessId)
            ->where('id', $routeId)
            ->where('is_active', true)
            ->firstOrFail();

        return [
            'cost_amount' => round((float) $route->base_cost * max(1, $vehicles), 4),
            'sale_amount' => round((float) $route->base_sale * max(1, $vehicles), 4),
            'passengers' => $passengers,
            'vehicles' => $vehicles,
        ];
    }
}
