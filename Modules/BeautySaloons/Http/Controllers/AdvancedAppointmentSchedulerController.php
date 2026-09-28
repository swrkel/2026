<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\AdvancedSchedulerService;

class AdvancedAppointmentSchedulerController extends Controller
{
    protected AdvancedSchedulerService $scheduler;

    public function __construct(AdvancedSchedulerService $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    public function index()
    {
        return view('beautysaloons::scheduler.index');
    }

    public function events(Request $request)
    {
        return response()->json($this->scheduler->calendarEvents($request->all())->values());
    }

    public function availability(Request $request)
    {
        $available = $this->scheduler->isSlotAvailable(
            $request->integer('staff_id') ?: null,
            $request->integer('room_id') ?: null,
            $request->input('appointment_date', now()->toDateString()),
            $request->input('start_time', '00:00:00'),
            $request->input('end_time', '00:30:00'),
            $request->integer('appointment_id') ?: null
        );

        return response()->json(['available' => $available]);
    }

    public function board()
    {
        return view('beautysaloons::scheduler.board');
    }
}
