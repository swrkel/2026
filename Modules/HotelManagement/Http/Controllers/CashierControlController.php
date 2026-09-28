<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\CashierControlService;

class CashierControlController extends Controller
{
    protected CashierControlService $service;

    public function __construct(CashierControlService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('hotelmanagement::cashier_control.index', ['cashierControl' => $this->service->dashboard()]);
    }

    public function openShift(Request $request)
    {
        $data = $request->validate([
            'cashier_user_id' => 'nullable|integer|min:1',
            'counter_name' => 'nullable|string|max:120',
            'opening_float' => 'nullable|numeric|min:0',
            'opened_at' => 'nullable|date',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->openShift($data, optional($request->user())->id);
        return redirect()->route('hotel-management.cashier-control.index')->with('status', 'Cashier shift opened successfully.');
    }

    public function safeDrop(Request $request)
    {
        $data = $request->validate([
            'shift_id' => 'required|integer|min:1',
            'drop_datetime' => 'nullable|date',
            'drop_amount' => 'required|numeric|min:0.01',
            'received_by' => 'nullable|string|max:120',
            'safe_bag_no' => 'nullable|string|max:120',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->safeDrop($data, optional($request->user())->id);
        return redirect()->route('hotel-management.cashier-control.index')->with('status', 'Safe drop recorded successfully.');
    }

    public function cashCount(Request $request)
    {
        $data = $request->validate([
            'shift_id' => 'required|integer|min:1',
            'denomination' => 'required|numeric|min:0.01',
            'quantity' => 'required|integer|min:0',
        ]);
        $this->service->cashCount($data, optional($request->user())->id);
        return redirect()->route('hotel-management.cashier-control.index')->with('status', 'Cash count line saved successfully.');
    }

    public function closeShift(Request $request)
    {
        $data = $request->validate([
            'shift_id' => 'required|integer|min:1',
            'declared_cash' => 'required|numeric|min:0',
            'closed_at' => 'nullable|date',
            'variance_reason' => 'nullable|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->closeShift($data, optional($request->user())->id);
        return redirect()->route('hotel-management.cashier-control.index')->with('status', 'Cashier shift closed successfully.');
    }

    public function reviewVariance(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|string|max:40',
            'review_note' => 'nullable|string|max:1000',
        ]);
        $this->service->reviewVariance($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.cashier-control.index')->with('status', 'Variance review updated successfully.');
    }
}
