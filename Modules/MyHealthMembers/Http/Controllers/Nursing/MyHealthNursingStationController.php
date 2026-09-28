<?php

namespace Modules\MyHealthMembers\Http\Controllers\Nursing;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthAppointment;

class MyHealthNursingStationController extends Controller
{
    public function index()
    {
        $patients = class_exists(MyHealthAppointment::class)
            ? MyHealthAppointment::with('member')->latest()->limit(50)->get()
            : collect();

        return view('myhealthmembers::nursing.station.index', compact('patients'));
    }
}
