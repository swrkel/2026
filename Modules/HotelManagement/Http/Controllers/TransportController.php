<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\TransportService;

class TransportController extends Controller
{
    protected TransportService $service;

    public function __construct(TransportService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $transport = $this->service->dashboard();
        return view('hotelmanagement::transport.index', compact('transport'));
    }

    public function vehicle(Request $request)
    {
        $data = $request->validate([
            'vehicle_no' => 'required|string|max:60',
            'vehicle_type' => 'nullable|string|max:80',
            'driver_name' => 'nullable|string|max:120',
            'driver_mobile' => 'nullable|string|max:30',
            'seating_capacity' => 'nullable|integer|min:0',
            'base_rate' => 'nullable|numeric|min:0',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveVehicle($data, optional($request->user())->id);
        return redirect()->route('hotel-management.transport.index')->with('status', 'Hotel vehicle saved successfully.');
    }

    public function booking(Request $request)
    {
        $data = $request->validate([
            'booking_no' => 'nullable|string|max:80',
            'vehicle_id' => 'nullable|integer',
            'reservation_id' => 'nullable|integer',
            'folio_id' => 'nullable|integer',
            'guest_name' => 'required|string|max:150',
            'mobile' => 'nullable|string|max:30',
            'room_no' => 'nullable|string|max:30',
            'trip_type' => 'required|string|max:40',
            'pickup_date' => 'nullable|date',
            'pickup_time' => 'nullable',
            'pickup_location' => 'nullable|string|max:255',
            'drop_location' => 'nullable|string|max:255',
            'flight_no' => 'nullable|string|max:80',
            'driver_name' => 'nullable|string|max:120',
            'rate' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveBooking($data, optional($request->user())->id);
        return redirect()->route('hotel-management.transport.index')->with('status', 'Transport booking saved successfully.');
    }

    public function status(Request $request, int $id)
    {
        $request->validate(['status' => 'required|string|max:30']);
        $this->service->updateStatus($id, $request->input('status'), optional($request->user())->id);
        return redirect()->route('hotel-management.transport.index')->with('status', 'Transport status updated.');
    }

    public function payment(Request $request, int $id)
    {
        $data = $request->validate([
            'payment_date' => 'nullable|date',
            'method' => 'required|string|max:40',
            'amount' => 'required|numeric|min:0',
            'reference_no' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->postPayment($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.transport.index')->with('status', 'Transport payment posted successfully.');
    }
}
