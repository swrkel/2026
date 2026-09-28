<?php

namespace Modules\Distribution\Repositories;

use Modules\Distribution\Entities\DistributionBusinessLocation;

/**
 * Distribution-owned repository for DistributionBusinessLocation.
 *
 * This keeps controllers/services depending on Distribution repositories
 * instead of direct main ERP model references. Existing table behaviour is
 * preserved through the Distribution entity wrapper.
 */
class DistributionBusinessLocationRepository
{
    public function query()
    {
        return DistributionBusinessLocation::query();
    }

    public function find($id)
    {
        return DistributionBusinessLocation::find($id);
    }

    public function findOrFail($id)
    {
        return DistributionBusinessLocation::findOrFail($id);
    }

    public function forBusiness($businessId)
    {
        return $this->query()->where('business_id', $businessId);
    }
}
