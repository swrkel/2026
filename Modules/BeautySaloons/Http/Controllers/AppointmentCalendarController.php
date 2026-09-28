<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\BeautyAppointmentCalendarService;

class AppointmentCalendarController extends Controller
{
    public function index()
    {
        return view('beautysaloons::appointments.calendar');
    }

    public function events(BeautyAppointmentCalendarService $service)
    {
        return response()->json($service->events((int) request()->session()->get('user.business_id'), request('location_id')));
    }
}
