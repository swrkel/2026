<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\LoyaltyService;

class LoyaltyController extends Controller
{
    protected LoyaltyService $service;

    public function __construct(LoyaltyService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $loyalty = $this->service->dashboard();
        return view('hotelmanagement::loyalty.index', compact('loyalty'));
    }

    public function tier(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:50',
            'min_points' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'benefits' => 'nullable|string|max:2000',
            'is_active' => 'nullable',
        ]);
        $this->service->saveTier($data, optional($request->user())->id);
        return redirect()->route('hotel-management.loyalty.index')->with('status', 'Loyalty tier saved successfully.');
    }

    public function member(Request $request)
    {
        $data = $request->validate([
            'guest_id' => 'nullable|integer',
            'member_no' => 'nullable|string|max:60',
            'guest_name' => 'required|string|max:150',
            'mobile' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'tier_id' => 'nullable|integer',
            'join_date' => 'nullable|date',
            'status' => 'nullable|string|max:30',
        ]);
        $this->service->saveMember($data, optional($request->user())->id);
        return redirect()->route('hotel-management.loyalty.index')->with('status', 'Loyalty member saved successfully.');
    }

    public function points(Request $request, int $memberId)
    {
        $data = $request->validate([
            'transaction_date' => 'nullable|date',
            'type' => 'required|string|max:20',
            'points' => 'required|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'reference_type' => 'nullable|string|max:60',
            'reference_no' => 'nullable|string|max:80',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->postPoints($memberId, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.loyalty.index')->with('status', 'Loyalty points posted successfully.');
    }
}
