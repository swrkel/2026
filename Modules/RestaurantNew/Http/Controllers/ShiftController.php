<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewCashierShift;
use Modules\RestaurantNew\Services\RestaurantStaffService;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = RestaurantNewCashierShift::latest()->paginate(25);
        return view('restaurantnew::shifts.index', compact('shifts'));
    }

    public function open(Request $request, RestaurantStaffService $service)
    {
        $data = $request->validate([
            'business_id' => 'required|integer',
            'location_id' => 'nullable|integer',
            'staff_member_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'opening_cash' => 'required|numeric|min:0',
            'opening_note' => 'nullable|string',
        ]);

        $service->openShift($data);
        return back()->with('status', __('restaurantnew::lang.shift_opened'));
    }

    public function cashMovement(Request $request, RestaurantNewCashierShift $shift, RestaurantStaffService $service)
    {
        $data = $request->validate([
            'movement_type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.0001',
            'reference_no' => 'nullable|string|max:191',
            'note' => 'nullable|string',
        ]);

        $service->addCashMovement($shift, $data['movement_type'], (float) $data['amount'], $data);
        return back()->with('status', __('restaurantnew::lang.cash_movement_saved'));
    }

    public function close(Request $request, RestaurantNewCashierShift $shift, RestaurantStaffService $service)
    {
        $data = $request->validate([
            'counted_cash' => 'required|numeric|min:0',
            'closing_note' => 'nullable|string',
        ]);

        $service->closeShift($shift, (float) $data['counted_cash'], $data['closing_note'] ?? null);
        return back()->with('status', __('restaurantnew::lang.shift_closed'));
    }

    public function distributeServiceCharge(Request $request, RestaurantNewCashierShift $shift, RestaurantStaffService $service)
    {
        $data = $request->validate(['base_amount' => 'required|numeric|min:0']);
        $count = $service->distributeServiceCharge($shift, (float) $data['base_amount']);

        return back()->with('status', $count . ' service charge distribution rows created.');
    }
}
