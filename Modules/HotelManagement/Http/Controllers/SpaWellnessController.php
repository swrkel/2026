<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HotelManagement\Services\SpaWellnessService;

class SpaWellnessController extends Controller
{
    protected SpaWellnessService $service;

    public function __construct(SpaWellnessService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $spa = $this->service->dashboard();
        return view('hotelmanagement::spa.index', compact('spa'));
    }

    public function service(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:60',
            'category' => 'nullable|string|max:80',
            'duration_minutes' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable',
        ]);
        $this->service->saveService($data, optional($request->user())->id);
        return redirect()->route('hotel-management.spa.index')->with('status', 'Spa service saved successfully.');
    }

    public function appointment(Request $request)
    {
        $data = $request->validate([
            'appointment_no' => 'nullable|string|max:80',
            'service_id' => 'nullable|integer',
            'guest_id' => 'nullable|integer',
            'folio_id' => 'nullable|integer',
            'guest_name' => 'required|string|max:150',
            'mobile' => 'nullable|string|max:30',
            'room_no' => 'nullable|string|max:30',
            'therapist_name' => 'nullable|string|max:120',
            'appointment_date' => 'nullable|date',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:30',
            'remarks' => 'nullable|string|max:1000',
        ]);
        $this->service->saveAppointment($data, optional($request->user())->id);
        return redirect()->route('hotel-management.spa.index')->with('status', 'Spa appointment saved successfully.');
    }

    public function status(Request $request, int $id)
    {
        $request->validate(['status' => 'required|string|max:30']);
        $this->service->updateStatus($id, $request->input('status'), optional($request->user())->id);
        return redirect()->route('hotel-management.spa.index')->with('status', 'Spa appointment status updated.');
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
        return redirect()->route('hotel-management.spa.index')->with('status', 'Spa payment posted successfully.');
    }
}
