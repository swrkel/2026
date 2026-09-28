<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\TaxComplianceService;

class TaxComplianceController extends Controller
{
    protected TaxComplianceService $service;

    public function __construct(TaxComplianceService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('hotelmanagement::tax_compliance.index', ['taxCompliance' => $this->service->dashboard()]);
    }

    public function taxRule(Request $request)
    {
        $data = $request->validate([
            'tax_code' => 'nullable|string|max:60',
            'tax_name' => 'required|string|max:160',
            'tax_type' => 'required|string|max:40',
            'applies_to' => 'required|string|max:60',
            'rate' => 'required|numeric|min:0',
            'inclusive_type' => 'required|string|max:40',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);
        $this->service->taxRule($data, optional($request->user())->id);
        return redirect()->route('hotel-management.tax-compliance.index')->with('status', 'Hotel tax rule saved successfully.');
    }

    public function serviceChargeRule(Request $request)
    {
        $data = $request->validate([
            'rule_code' => 'nullable|string|max:60',
            'rule_name' => 'required|string|max:160',
            'applies_to' => 'required|string|max:60',
            'rate' => 'required|numeric|min:0',
            'distribution_method' => 'nullable|string|max:80',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'nullable|boolean',
        ]);
        $this->service->serviceChargeRule($data, optional($request->user())->id);
        return redirect()->route('hotel-management.tax-compliance.index')->with('status', 'Service charge rule saved successfully.');
    }

    public function taxInvoice(Request $request)
    {
        $data = $request->validate([
            'invoice_no' => 'nullable|string|max:60',
            'source_type' => 'nullable|string|max:60',
            'source_id' => 'nullable|integer|min:1',
            'guest_name' => 'nullable|string|max:160',
            'invoice_date' => 'nullable|date',
            'net_amount' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'service_charge_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:40',
        ]);
        $this->service->taxInvoice($data, optional($request->user())->id);
        return redirect()->route('hotel-management.tax-compliance.index')->with('status', 'Tax invoice snapshot posted successfully.');
    }

    public function periodSummary(Request $request)
    {
        $data = $request->validate([
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'room_revenue' => 'nullable|numeric|min:0',
            'fb_revenue' => 'nullable|numeric|min:0',
            'other_revenue' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'service_charge_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->periodSummary($data, optional($request->user())->id);
        return redirect()->route('hotel-management.tax-compliance.index')->with('status', 'Tax period summary saved successfully.');
    }
}
