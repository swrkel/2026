<?php

namespace Modules\PetroDirect\Services;

use Modules\PetroDirect\Entities\FuelTank;

class TankService
{
    public function queryForBusiness(int $businessId)
    {
        return FuelTank::where('business_id', $businessId);
    }
}
