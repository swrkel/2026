<?php

namespace Modules\DistributionNew\Reports;

use Modules\DistributionNew\Models\DisnewVehicle;

class DisnewVehicleReport
{
    public function query(array $filters = [])
    {
        return DisnewVehicle::query()
            ->when($filters['business_id'] ?? null, fn ($q, $businessId) => $q->where('business_id', $businessId))
            ->when($filters['business_location_id'] ?? null, fn ($q, $locationId) => $q->where('business_location_id', $locationId))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy('vehicle_no');
    }
}
