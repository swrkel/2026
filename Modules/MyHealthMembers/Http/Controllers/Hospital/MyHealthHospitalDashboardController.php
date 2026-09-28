<?php

namespace Modules\MyHealthMembers\Http\Controllers\Hospital;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthAppointment;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthMember;

class MyHealthHospitalDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $summary = [
            'appointments_today' => MyHealthAppointment::whereDate('appointment_date', $today)->count(),
            'waiting' => MyHealthAppointment::whereDate('appointment_date', $today)->where('status', 'waiting')->count(),
            'in_consultation' => MyHealthAppointment::whereDate('appointment_date', $today)->where('status', 'in_consultation')->count(),
            'completed' => MyHealthAppointment::whereDate('appointment_date', $today)->where('status', 'completed')->count(),
            'members' => MyHealthMember::count(),
            'doctors' => MyHealthDoctor::count(),
        ];

        $queue = MyHealthAppointment::with(['member', 'doctor', 'room'])
            ->whereDate('appointment_date', $today)
            ->orderBy('queue_no')
            ->limit(20)
            ->get();

        return view('myhealthmembers::hospital.dashboard.index', compact('summary', 'queue'));
    }
}
