<?php

namespace Modules\MyHealthMembers\Http\Controllers\OperationTheatre;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthOperationTheatreRoom;
use Modules\MyHealthMembers\Entities\MyHealthSurgerySchedule;
use Modules\MyHealthMembers\Services\OperationTheatre\MyHealthOperationTheatreService;

class MyHealthSurgeryScheduleController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::operation_theatre.schedules.index', [
            'schedules' => MyHealthSurgerySchedule::orderByDesc('scheduled_start_at')->paginate(25),
        ]);
    }

    public function create(MyHealthOperationTheatreService $service)
    {
        return view('myhealthmembers::operation_theatre.schedules.create', [
            'rooms' => MyHealthOperationTheatreRoom::orderBy('room_name')->get(),
            'priorities' => $service->priorities(),
        ]);
    }

    public function store(Request $request, MyHealthOperationTheatreService $service)
    {
        $data = $request->validate([
            'member_id' => 'required|integer',
            'consultation_id' => 'nullable|integer',
            'procedure_name' => 'required|string|max:191',
            'procedure_category' => 'nullable|string|max:191',
            'priority' => 'nullable|string|max:50',
            'theatre_room_id' => 'nullable|integer',
            'surgeon_id' => 'nullable|integer',
            'assistant_surgeon_id' => 'nullable|integer',
            'anaesthetist_id' => 'nullable|integer',
            'nurse_in_charge_id' => 'nullable|integer',
            'scheduled_start_at' => 'nullable|date',
            'scheduled_end_at' => 'nullable|date',
            'estimated_duration_minutes' => 'nullable|integer|min:0',
            'diagnosis' => 'nullable|string',
            'clinical_notes' => 'nullable|string',
            'special_instructions' => 'nullable|string',
        ]);

        $data['business_id'] = session('business.id') ?? session('user.business_id');
        $data['location_id'] = session('business_location_id') ?? null;
        $data['surgery_no'] = $service->nextSurgeryNo();
        $data['status'] = 'scheduled';
        $data['created_by'] = auth()->id();

        MyHealthSurgerySchedule::create($data);

        return redirect()->route('myhealth.operation_theatre.schedules.index')->with('status', 'Surgery scheduled successfully.');
    }

    public function status(Request $request, MyHealthSurgerySchedule $schedule)
    {
        $data = $request->validate(['status' => 'required|string|max:50']);
        $schedule->update(['status' => $data['status'], 'updated_by' => auth()->id()]);

        return back()->with('status', 'Surgery status updated successfully.');
    }
}
