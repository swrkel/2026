<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\ProcurementService;

class ProcurementController extends Controller
{
    protected ProcurementService $service;

    public function __construct(ProcurementService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $procurement = $this->service->dashboard();
        return view('hotelmanagement::procurement.index', compact('procurement'));
    }

    public function supplier(Request $request)
    {
        $data = $request->validate([
            'supplier_name' => 'required|string|max:160',
            'contact_person' => 'nullable|string|max:120',
            'mobile' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:160',
            'address' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:80',
            'credit_days' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveSupplier($data, optional($request->user())->id);
        return redirect()->route('hotel-management.procurement.index')->with('status', 'Hotel supplier saved successfully.');
    }

    public function purchaseRequest(Request $request)
    {
        $data = $request->validate([
            'request_no' => 'nullable|string|max:80',
            'request_date' => 'nullable|date',
            'department' => 'required|string|max:100',
            'requested_by' => 'nullable|string|max:120',
            'required_date' => 'nullable|date',
            'priority' => 'nullable|string|max:30',
            'item_name' => 'required|string|max:160',
            'description' => 'nullable|string|max:1000',
            'qty' => 'required|numeric|min:0.001',
            'estimated_unit_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->savePurchaseRequest($data, optional($request->user())->id);
        return redirect()->route('hotel-management.procurement.index')->with('status', 'Purchase request saved successfully.');
    }

    public function purchaseOrder(Request $request)
    {
        $data = $request->validate([
            'po_no' => 'nullable|string|max:80',
            'po_date' => 'nullable|date',
            'supplier_id' => 'required|integer',
            'request_id' => 'nullable|integer',
            'item_name' => 'required|string|max:160',
            'description' => 'nullable|string|max:1000',
            'qty' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date',
            'status' => 'nullable|string|max:30',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->savePurchaseOrder($data, optional($request->user())->id);
        return redirect()->route('hotel-management.procurement.index')->with('status', 'Purchase order saved successfully.');
    }

    public function grn(Request $request, int $poId)
    {
        $data = $request->validate([
            'grn_no' => 'nullable|string|max:80',
            'received_date' => 'nullable|date',
            'received_qty' => 'required|numeric|min:0.001',
            'accepted_qty' => 'nullable|numeric|min:0',
            'rejected_qty' => 'nullable|numeric|min:0',
            'received_by' => 'nullable|string|max:120',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->receiveGoods($poId, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.procurement.index')->with('status', 'Goods received note posted successfully.');
    }

    public function status(Request $request, string $type, int $id)
    {
        $data = $request->validate(['status' => 'required|string|max:30', 'remarks' => 'nullable|string|max:1000']);
        $this->service->updateStatus($type, $id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.procurement.index')->with('status', 'Procurement status updated.');
    }
}
