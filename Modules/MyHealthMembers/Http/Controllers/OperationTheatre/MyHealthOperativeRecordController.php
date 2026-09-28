<?php

namespace Modules\MyHealthMembers\Http\Controllers\OperationTheatre;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthOperativeRecord;
use Modules\MyHealthMembers\Entities\MyHealthSurgerySchedule;
use Modules\MyHealthMembers\Services\OperationTheatre\MyHealthOperationTheatreService;

class MyHealthOperativeRecordController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::operation_theatre.records.index', [
            'records' => MyHealthOperativeRecord::orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::operation_theatre.records.create', [
            'schedules' => MyHealthSurgerySchedule::orderByDesc('scheduled_start_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request, MyHealthOperationTheatreService $service)
    {
        $data = $request->validate([
            'surgery_schedule_id' => 'required|integer',
            'member_id' => 'required|integer',
            'procedure_performed' => 'required|string|max:191',
            'anaesthesia_type' => 'nullable|string|max:100',
            'incision_time' => 'nullable|date',
            'closure_time' => 'nullable|date',
            'findings' => 'nullable|string',
            'procedure_notes' => 'nullable|string',
            'implants_used' => 'nullable|string',
            'consumables_used' => 'nullable|string',
            'blood_loss_ml' => 'nullable|numeric|min:0',
            'complications' => 'nullable|string',
            'surgeon_id' => 'nullable|integer',
            'anaesthetist_id' => 'nullable|integer',
            'scrub_nurse_id' => 'nullable|integer',
            'circulating_nurse_id' => 'nullable|integer',
        ]);

        $data['business_id'] = session('business.id') ?? session('user.business_id');
        $data['operation_no'] = $service->nextOperationNo();
        $data['blood_transfusion'] = $request->boolean('blood_transfusion');
        $data['specimen_sent'] = $request->boolean('specimen_sent');
        $data['status'] = 'recorded';
        $data['created_by'] = auth()->id();

        MyHealthOperativeRecord::create($data);
        MyHealthSurgerySchedule::where('id', $data['surgery_schedule_id'])->update(['status' => 'completed', 'actual_end_at' => now()]);

        return redirect()->route('myhealth.operation_theatre.records.index')->with('status', 'Operative record saved successfully.');
    }
}
