<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyRecurringAppointment;
use Modules\BeautySaloons\Services\RecurringAppointmentService;

class RecurringAppointmentController extends Controller
{
    public function index()
    {
        $templates = BeautyRecurringAppointment::orderByDesc('id')->paginate(25);
        return view('beautysaloons::scheduler.recurring', compact('templates'));
    }

    public function store(Request $request, RecurringAppointmentService $service)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|integer',
            'service_id' => 'nullable|integer',
            'staff_id' => 'nullable|integer',
            'frequency' => 'required|string|max:30',
            'week_days' => 'nullable|array',
            'starts_on' => 'required|date',
            'ends_on' => 'nullable|date',
            'start_time' => 'nullable',
            'duration_minutes' => 'nullable|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $service->store($data);
        return redirect()->back()->with('status', __('beautysaloons::scheduler.recurring_saved'));
    }
}
