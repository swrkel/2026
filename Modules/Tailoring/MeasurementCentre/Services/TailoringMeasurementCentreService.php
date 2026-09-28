<?php
namespace Modules\Tailoring\MeasurementCentre\Services;
use Illuminate\Http\Request;

class TailoringMeasurementCentreService
{
    public function summary(Request $request): array { return ['profiles'=>0,'versions'=>0,'garment_templates'=>0,'updated_this_month'=>0]; }
    public function profilesForCustomer($customerId): array { return []; }
}
