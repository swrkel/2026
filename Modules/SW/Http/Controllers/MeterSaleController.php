<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SW\Services\MeterSaleLookupService;

/**
 * What the Meter Sales entry form asks for — 8043.
 *
 * Two endpoints: the pumps at a location, and what to fill in when one is
 * chosen.
 */
class MeterSaleController extends Controller
{
    public function __construct(protected MeterSaleLookupService $lookup)
    {
    }

    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    /** The pumps at a location. */
    public function pumps(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = (int) $request->input('location_id');
        $operatorId = (int) $request->input('operator_id');
        $shiftIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('shift_ids', [])))));

        if ($businessId <= 0 || $locationId <= 0) {
            return response()->json([]);
        }

        return response()->json($this->lookup->pumps($businessId, $locationId, $operatorId, $shiftIds));
    }

    /**
     * Starting meter and price for one pump.
     *
     * The starting meter comes from SW's own previous settlement for that pump,
     * or the pump's own reading if SW has never closed it.
     */
    public function pumpDetails(Request $request)
    {
        $businessId = $this->businessId();
        $pumpId = (int) $request->input('pump_id');

        if ($businessId <= 0 || $pumpId <= 0) {
            return response()->json(['found' => false]);
        }

        $shiftIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('shift_ids', [])))));
        $operatorId = (int) $request->input('operator_id');

        return response()->json(
            $this->lookup->forPump(
                $businessId,
                $pumpId,
                (int) $request->input('location_id'),
                $operatorId,
                $shiftIds
            )
        );
    }
}
