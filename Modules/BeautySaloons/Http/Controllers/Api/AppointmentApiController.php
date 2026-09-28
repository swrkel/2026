<?php

namespace Modules\BeautySaloons\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyAppointment;
use Modules\BeautySaloons\Services\Api\BeautyPortalApiResponse;
use Modules\BeautySaloons\Services\Portal\PortalAppointmentService;

class AppointmentApiController extends Controller
{
    public function index(Request $request, PortalAppointmentService $service)
    {
        return BeautyPortalApiResponse::success($service->listForCustomer($request->user()->id));
    }

    public function store(Request $request, PortalAppointmentService $service)
    {
        $appointment = $service->createForCustomer($request->user()->id, $request->all());
        return BeautyPortalApiResponse::success($appointment, 'Appointment booked successfully.');
    }

    public function show(BeautyAppointment $appointment)
    {
        return BeautyPortalApiResponse::success($appointment);
    }

    public function update(Request $request, BeautyAppointment $appointment)
    {
        $appointment->update($request->all());
        return BeautyPortalApiResponse::success($appointment, 'Appointment updated successfully.');
    }

    public function cancel(BeautyAppointment $appointment, PortalAppointmentService $service)
    {
        return BeautyPortalApiResponse::success($service->cancel($appointment), 'Appointment cancelled successfully.');
    }
}
