<?php

namespace Modules\DistributionNew\Reports;

use Modules\DistributionNew\Models\DisnewRoute;

class DisnewRouteCoverageReport
{
    public function rows($businessId, $locationId = null)
    {
        return DisnewRoute::where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('business_location_id', $locationId))
            ->orderBy('route_code')
            ->get();
    }
}
