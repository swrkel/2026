<?php

namespace Modules\DistributionNew\Services\Routes;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewRoute;
use Modules\DistributionNew\Models\DisnewRouteCustomer;

class DisnewRouteService
{
    public function listForBusiness($businessId, $locationId = null)
    {
        return DisnewRoute::where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('business_location_id', $locationId))
            ->orderBy('route_code')->get();
    }

    public function save(array $data): DisnewRoute
    {
        return DisnewRoute::updateOrCreate(['id' => $data['id'] ?? null], $data);
    }

    public function assignCustomers(int $routeId, array $customerIds): void
    {
        DB::transaction(function () use ($routeId, $customerIds) {
            DisnewRouteCustomer::where('disnew_route_id', $routeId)->delete();
            foreach ($customerIds as $sequence => $customerId) {
                DisnewRouteCustomer::create([
                    'disnew_route_id' => $routeId,
                    'customer_id' => $customerId,
                    'visit_sequence' => $sequence + 1,
                    'is_active' => 1,
                ]);
            }
        });
    }
}
