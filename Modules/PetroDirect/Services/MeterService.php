<?php

namespace Modules\PetroDirect\Services;

use Modules\PetroDirect\Entities\CurrentMeter;
use Modules\PetroDirect\Entities\OpeningMeter;
use Modules\PetroDirect\Entities\MeterResetting;

class MeterService
{
    public function openingMetersForBusiness(int $businessId)
    {
        return OpeningMeter::where('business_id', $businessId);
    }

    public function currentMetersForBusiness(int $businessId)
    {
        return CurrentMeter::where('business_id', $businessId);
    }

    public function meterResettingsForBusiness(int $businessId)
    {
        return MeterResetting::where('business_id', $businessId);
    }
}
