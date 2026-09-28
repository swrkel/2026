<?php

namespace Modules\DistributionNew\Services\Vehicles;

use Modules\DistributionNew\Models\DisnewVehicle;
use Modules\DistributionNew\Models\DisnewVehicleLimit;

class DisnewVehicleLimitService
{
    public function getLimit(int $businessId): ?DisnewVehicleLimit
    {
        return DisnewVehicleLimit::where('business_id', $businessId)->first();
    }

    public function activeVehicleCount(int $businessId): int
    {
        return DisnewVehicle::where('business_id', $businessId)
            ->where('status', '!=', 'inactive')
            ->count();
    }

    public function canCreate(int $businessId): array
    {
        $limit = $this->getLimit($businessId);
        if (!$limit || $limit->allow_unlimited) {
            return ['allowed' => true, 'limit' => null, 'used' => $this->activeVehicleCount($businessId)];
        }
        $used = $this->activeVehicleCount($businessId);
        return [
            'allowed' => $used < (int) $limit->vehicle_limit,
            'limit' => (int) $limit->vehicle_limit,
            'used' => $used,
        ];
    }

    public function assertCanCreate(int $businessId): void
    {
        $state = $this->canCreate($businessId);
        if (!$state['allowed']) {
            throw new \RuntimeException('Vehicle limit reached for this business. Please contact Super Admin to increase the allowed vehicle count.');
        }
    }
}
