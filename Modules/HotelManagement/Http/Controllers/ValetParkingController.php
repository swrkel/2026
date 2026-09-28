<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\ValetParkingService;
use Throwable;

class ValetParkingController extends Controller
{
    protected ValetParkingService $service;

    public function __construct(ValetParkingService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $valet = $this->service->dashboard();
        return view('hotelmanagement::valet.index', compact('valet'));
    }

    public function zone(Request $request)
    {
        $data = $request->validate([
            'zone_code' => 'required|string|max:50',
            'zone_name' => 'required|string|max:120',
            'capacity' => 'nullable|integer|min:0',
            'is_active' => 'nullable',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveZone($data, optional($request->user())->id);
        return redirect()->route('hotel-management.valet.index')->with('status', 'Valet parking zone saved successfully.');
    }

    public function ticket(Request $request)
    {
        $data = $request->validate([
            'ticket_no' => 'nullable|string|max:80',
            'ticket_date' => 'nullable|date',
            'zone_id' => 'nullable|integer',
            'room_no' => 'nullable|string|max:30',
            'guest_name' => 'nullable|string|max:150',
            'mobile' => 'nullable|string|max:50',
            'vehicle_no' => 'required|string|max:80',
            'vehicle_type' => 'nullable|string|max:80',
            'vehicle_colour' => 'nullable|string|max:80',
            'key_tag_no' => 'nullable|string|max:80',
            'parked_slot' => 'nullable|string|max:80',
            'check_in_time' => 'nullable|string|max:30',
            'expected_out_time' => 'nullable|string|max:30',
            'driver_name' => 'nullable|string|max:150',
            'rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);
        try {
            $this->service->saveTicket($data, optional($request->user())->id);
            return redirect()->route('hotel-management.valet.index')->with('status', 'Valet ticket saved successfully.');
        } catch (Throwable $e) {
            return redirect()->route('hotel-management.valet.index')->with('error', $e->getMessage());
        }
    }

    public function status(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|string|max:40',
            'retrieved_time' => 'nullable|string|max:30',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->updateStatus($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.valet.index')->with('status', 'Valet ticket status updated.');
    }

    public function payment(Request $request, int $id)
    {
        $data = $request->validate([
            'payment_method' => 'required|string|max:40',
            'paid_amount' => 'required|numeric|min:0',
            'payment_reference' => 'nullable|string|max:150',
        ]);
        $this->service->recordPayment($id, $data, optional($request->user())->id);
        return redirect()->route('hotel-management.valet.index')->with('status', 'Valet payment recorded.');
    }
}
