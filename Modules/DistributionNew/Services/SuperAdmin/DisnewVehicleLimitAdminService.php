<?php

namespace Modules\DistributionNew\Services\SuperAdmin;

use Modules\DistributionNew\Models\DisnewVehicleLimit;

class DisnewVehicleLimitAdminService
{
    public function saveLimit(int $businessId, array $data): DisnewVehicleLimit
    {
        return DisnewVehicleLimit::updateOrCreate(
            ['business_id' => $businessId],
            [
                'vehicle_limit' => !empty($data['allow_unlimited']) ? null : (int) ($data['vehicle_limit'] ?? 0),
                'allow_unlimited' => !empty($data['allow_unlimited']),
                'note' => $data['note'] ?? null,
                'updated_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]
        );
    }
}
