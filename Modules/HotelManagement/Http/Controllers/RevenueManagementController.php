<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\RevenueManagementService;

class RevenueManagementController extends Controller
{
    protected RevenueManagementService $service;

    public function __construct(RevenueManagementService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $revenueManagement = $this->service->dashboard($request->all());
        return view('hotelmanagement::revenue_management.index', compact('revenueManagement'));
    }

    public function season(Request $request)
    {
        $data = $request->validate([
            'season_code' => 'required|string|max:40',
            'season_name' => 'required|string|max:160',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'rate_adjustment_type' => 'required|string|max:30',
            'rate_adjustment_value' => 'required|numeric',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveSeason($data, optional($request->user())->id);
        return redirect()->route('hotel-management.revenue-management.index')->with('status', 'Seasonal pricing saved successfully.');
    }

    public function yieldRule(Request $request)
    {
        $data = $request->validate([
            'rule_name' => 'required|string|max:160',
            'occupancy_from' => 'required|numeric|min:0|max:100',
            'occupancy_to' => 'required|numeric|min:0|max:100',
            'adjustment_type' => 'required|string|max:30',
            'adjustment_value' => 'required|numeric',
            'priority' => 'nullable|integer|min:1',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveYieldRule($data, optional($request->user())->id);
        return redirect()->route('hotel-management.revenue-management.index')->with('status', 'Yield rule saved successfully.');
    }

    public function corporateContract(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:180',
            'contact_person' => 'nullable|string|max:160',
            'mobile' => 'nullable|string|max:60',
            'email' => 'nullable|email|max:160',
            'contract_from' => 'nullable|date',
            'contract_to' => 'nullable|date|after_or_equal:contract_from',
            'rate_type' => 'nullable|string|max:60',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
            'direct_billing_allowed' => 'nullable',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveCorporateContract($data, optional($request->user())->id);
        return redirect()->route('hotel-management.revenue-management.index')->with('status', 'Corporate contract saved successfully.');
    }

    public function agentContract(Request $request)
    {
        $data = $request->validate([
            'agent_name' => 'required|string|max:180',
            'agent_code' => 'required|string|max:60',
            'commission_type' => 'nullable|string|max:30',
            'commission_value' => 'nullable|numeric|min:0',
            'contract_from' => 'nullable|date',
            'contract_to' => 'nullable|date|after_or_equal:contract_from',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveAgentContract($data, optional($request->user())->id);
        return redirect()->route('hotel-management.revenue-management.index')->with('status', 'Travel agent contract saved successfully.');
    }

    public function groupBlock(Request $request)
    {
        $data = $request->validate([
            'group_name' => 'required|string|max:180',
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after:arrival_date',
            'blocked_rooms' => 'required|integer|min:1',
            'rate_amount' => 'nullable|numeric|min:0',
            'cutoff_date' => 'nullable|date',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveGroupBlock($data, optional($request->user())->id);
        return redirect()->route('hotel-management.revenue-management.index')->with('status', 'Group room block saved successfully.');
    }

    public function splitFolio(Request $request)
    {
        $data = $request->validate([
            'rule_name' => 'required|string|max:160',
            'payer_type' => 'required|string|max:60',
            'charge_category' => 'required|string|max:80',
            'split_type' => 'required|string|max:40',
            'split_value' => 'required|numeric|min:0',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveSplitFolioRule($data, optional($request->user())->id);
        return redirect()->route('hotel-management.revenue-management.index')->with('status', 'Split folio rule saved successfully.');
    }

    public function releaseRooms(Request $request, int $id)
    {
        $data = $request->validate([
            'release_rooms' => 'required|integer|min:1',
            'status' => 'nullable|string|max:40',
        ]);
        $this->service->releaseGroupRooms($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.revenue-management.index')->with('status', 'Group blocked rooms released successfully.');
    }
}
