<?php

namespace Modules\PetroDirect\Services;

use Modules\PetroDirect\Entities\Pump;

class PumpService
{
    public function queryForBusiness(int $businessId)
    {
        return Pump::where('business_id', $businessId);
    }
}
