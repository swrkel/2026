<?php

namespace Modules\DistributionNew\Services\Routes;

use Modules\DistributionNew\Models\DisnewTerritory;

class DisnewTerritoryService
{
    public function listForBusiness($businessId, $locationId = null)
    {
        return DisnewTerritory::where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('business_location_id', $locationId))
            ->orderBy('name')->get();
    }

    public function save(array $data): DisnewTerritory
    {
        return DisnewTerritory::updateOrCreate(['id' => $data['id'] ?? null], $data);
    }
}
