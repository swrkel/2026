<?php

namespace Modules\MyHealthMembers\Http\Controllers\Hospital;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthAppointment;

class MyHealthQueueController extends Controller
{
    public function index()
    {
        $queue = MyHealthAppointment::with(['member', 'doctor', 'room'])
            ->whereDate('appointment_date', now()->toDateString())
            ->whereIn('status', ['waiting', 'in_consultation'])
            ->orderBy('queue_no')
            ->get();

        return view('myhealthmembers::hospital.queue.index', compact('queue'));
    }
}
