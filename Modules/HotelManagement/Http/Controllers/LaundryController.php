<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\LaundryService;
use Throwable;

class LaundryController extends Controller
{
    protected LaundryService $service;

    public function __construct(LaundryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $laundry = $this->service->dashboard();
        return view('hotelmanagement::laundry.index', compact('laundry'));
    }

    public function service(Request $request)
    {
        $data = $request->validate([
            'service_code' => 'required|string|max:80',
            'service_name' => 'required|string|max:150',
            'category' => 'nullable|string|max:80',
            'unit' => 'nullable|string|max:30',
            'standard_rate' => 'nullable|numeric|min:0',
            'express_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveService($data, optional($request->user())->id);
        return redirect()->route('hotel-management.laundry.index')->with('status', 'Laundry service saved successfully.');
    }

    public function order(Request $request)
    {
        $data = $request->validate([
            'order_no' => 'nullable|string|max:80',
            'order_date' => 'nullable|date',
            'room_id' => 'nullable|integer',
            'room_no' => 'nullable|string|max:30',
            'reservation_id' => 'nullable|integer',
            'folio_id' => 'nullable|integer',
            'guest_name' => 'nullable|string|max:150',
            'service_id' => 'required|integer',
            'qty' => 'required|numeric|min:0.0001',
            'rate_type' => 'nullable|string|max:30',
            'unit_price' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'pickup_time' => 'nullable|string|max:30',
            'delivery_time' => 'nullable|string|max:30',
            'status' => 'nullable|string|max:30',
            'remarks' => 'nullable|string|max:1000',
        ]);
        try {
            $this->service->saveOrder($data, optional($request->user())->id);
            return redirect()->route('hotel-management.laundry.index')->with('status', 'Laundry order saved successfully.');
        } catch (Throwable $e) {
            return redirect()->route('hotel-management.laundry.index')->with('error', $e->getMessage());
        }
    }

    public function status(Request $request, int $id)
    {
        $request->validate(['status' => 'required|string|max:30']);
        $this->service->updateStatus($id, $request->input('status'), optional($request->user())->id);
        return redirect()->route('hotel-management.laundry.index')->with('status', 'Laundry order status updated.');
    }

    public function payment(Request $request, int $id)
    {
        $data = $request->validate([
            'payment_method' => 'required|string|max:40',
            'paid_amount' => 'required|numeric|min:0',
            'payment_reference' => 'nullable|string|max:150',
        ]);
        $this->service->recordPayment($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.laundry.index')->with('status', 'Laundry payment recorded.');
    }

    public function postToFolio(Request $request, int $id)
    {
        $this->service->postToFolio($id, optional($request->user())->id);
        return redirect()->route('hotel-management.laundry.index')->with('status', 'Laundry charge posted to folio.');
    }
}
