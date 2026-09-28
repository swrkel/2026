<?php

namespace Modules\DistributionNew\Services\SalesRep;

use Modules\DistributionNew\Models\DisnewSalesRep;

class DisnewSalesRepService
{
    public function listForBusiness($businessId, $locationId = null)
    {
        return DisnewSalesRep::where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('business_location_id', $locationId))
            ->orderBy('sales_rep_name')->get();
    }

    public function save(array $data): DisnewSalesRep
    {
        return DisnewSalesRep::updateOrCreate(['id' => $data['id'] ?? null], $data);
    }
}
