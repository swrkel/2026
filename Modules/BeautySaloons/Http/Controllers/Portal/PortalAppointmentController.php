<?php

namespace Modules\BeautySaloons\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyAppointment;
use Modules\BeautySaloons\Services\Portal\PortalAppointmentService;
use Modules\BeautySaloons\Services\Portal\PortalCustomerSessionService;

class PortalAppointmentController extends Controller
{
    public function index(PortalCustomerSessionService $session, PortalAppointmentService $service)
    {
        $appointments = $service->listForCustomer($session->customer()->id);
        return view('beautysaloons::portal.appointments.index', compact('appointments'));
    }

    public function create()
    {
        return view('beautysaloons::portal.appointments.create');
    }

    public function store(Request $request, PortalCustomerSessionService $session, PortalAppointmentService $service)
    {
        $data = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'service_id' => ['required', 'integer'],
            'staff_id' => ['nullable', 'integer'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $service->createForCustomer($session->customer()->id, $data);
        return redirect()->route('beautysaloons.portal.appointments.index')->with('status', 'Appointment booked successfully.');
    }

    public function show(BeautyAppointment $appointment)
    {
        return view('beautysaloons::portal.appointments.show', compact('appointment'));
    }

    public function edit(BeautyAppointment $appointment)
    {
        return view('beautysaloons::portal.appointments.edit', compact('appointment'));
    }

    public function update(Request $request, BeautyAppointment $appointment)
    {
        $appointment->update($request->only(['appointment_date', 'start_time', 'notes']));
        return redirect()->route('beautysaloons.portal.appointments.index')->with('status', 'Appointment updated successfully.');
    }

    public function cancel(BeautyAppointment $appointment, PortalAppointmentService $service)
    {
        $service->cancel($appointment);
        return back()->with('status', 'Appointment cancelled successfully.');
    }
}
