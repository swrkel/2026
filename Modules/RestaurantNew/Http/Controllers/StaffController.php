<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewStaffMember;
use Modules\RestaurantNew\Services\RestaurantStaffService;

class StaffController extends Controller
{
    public function index()
    {
        $staff = RestaurantNewStaffMember::latest()->paginate(25);
        return view('restaurantnew::staff.index', compact('staff'));
    }

    public function store(Request $request, RestaurantStaffService $service)
    {
        $data = $request->validate([
            'business_id' => 'required|integer',
            'location_id' => 'nullable|integer',
            'staff_code' => 'nullable|string|max:191',
            'name' => 'required|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:191',
            'role' => 'required|string|max:50',
            'service_charge_share_percent' => 'nullable|numeric|min:0|max:100',
            'can_take_orders' => 'nullable|boolean',
            'can_cashier' => 'nullable|boolean',
        ]);

        $data['can_take_orders'] = $request->boolean('can_take_orders');
        $data['can_cashier'] = $request->boolean('can_cashier');
        $service->createStaff($data);

        return back()->with('status', __('restaurantnew::lang.staff_saved'));
    }
}
