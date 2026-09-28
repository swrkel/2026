<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SW\Services\CreditSaleLookupService;

/**
 * What the Credit Sales form asks for — 8047.
 */
class CreditSaleController extends Controller
{
    public function __construct(protected CreditSaleLookupService $lookup)
    {
    }

    protected function businessId(): int
    {
        foreach ([
            session('user.business_id'),
            session('business.id'),
            session('business_id'),
            optional(auth()->user())->business_id,
        ] as $candidate) {
            $businessId = (int) $candidate;
            if ($businessId > 0) {
                return $businessId;
            }
        }

        return 0;
    }

    public function customers()
    {
        $businessId = $this->businessId();

        return response()->json(
            $businessId > 0 ? $this->lookup->customers($businessId) : []
        );
    }

    public function customerVehicles(Request $request)
    {
        $businessId = $this->businessId();
        $contactId = (int) $request->input('contact_id');

        return response()->json(
            $businessId > 0 && $contactId > 0
                ? $this->lookup->vehicles($businessId, $contactId)
                : []
        );
    }

    public function products()
    {
        $businessId = $this->businessId();

        return response()->json(
            $businessId > 0 ? $this->lookup->products($businessId) : []
        );
    }

    /** Credit sales already recorded on the Daily tab for these shifts. */
    public function dailyCreditSales(Request $request)
    {
        $businessId = $this->businessId();

        $shiftIds = array_filter(array_map('intval', (array) $request->input('shift_ids', [])));

        if ($businessId <= 0 || empty($shiftIds)) {
            return response()->json([]);
        }

        return response()->json($this->lookup->fromDailyTab(
            $businessId, $shiftIds, (int) $request->input('pump_operator_id')
        ));
    }

    /**
     * Add a vehicle number to a customer — 8047.
     *
     * Written to Customer References rather than held on the settlement, so it
     * is available next time and on every other screen that reads them.
     */
    public function addVehicle(Request $request)
    {
        $businessId = $this->businessId();

        $data = $request->validate([
            'contact_id' => 'required|integer',
            'vehicle_no' => 'required|string|max:100',
        ]);

        $ok = $this->lookup->addVehicle(
            $businessId, (int) $data['contact_id'], $data['vehicle_no']
        );

        return response()->json(['success' => $ok]);
    }
}
