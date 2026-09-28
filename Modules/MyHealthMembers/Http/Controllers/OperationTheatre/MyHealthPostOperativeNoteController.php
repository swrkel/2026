<?php

namespace Modules\MyHealthMembers\Http\Controllers\OperationTheatre;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthOperativeRecord;
use Modules\MyHealthMembers\Entities\MyHealthPostOperativeNote;
use Modules\MyHealthMembers\Entities\MyHealthSurgerySchedule;

class MyHealthPostOperativeNoteController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::operation_theatre.post_op.index', [
            'notes' => MyHealthPostOperativeNote::orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::operation_theatre.post_op.create', [
            'schedules' => MyHealthSurgerySchedule::orderByDesc('scheduled_start_at')->limit(100)->get(),
            'records' => MyHealthOperativeRecord::orderByDesc('created_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'surgery_schedule_id' => 'required|integer',
            'member_id' => 'required|integer',
            'operative_record_id' => 'nullable|integer',
            'recovery_status' => 'nullable|string|max:100',
            'pain_score' => 'nullable|integer|min:0|max:10',
            'vital_status' => 'nullable|string|max:191',
            'post_op_instructions' => 'nullable|string',
            'medications' => 'nullable|string',
            'follow_up_plan' => 'nullable|string',
            'discharge_recommendations' => 'nullable|string',
        ]);

        $data['business_id'] = session('business.id') ?? session('user.business_id');
        $data['icu_transfer_required'] = $request->boolean('icu_transfer_required');
        $data['ward_transfer_required'] = $request->boolean('ward_transfer_required');
        $data['noted_by'] = auth()->id();
        $data['noted_at'] = now();
        $data['created_by'] = auth()->id();

        MyHealthPostOperativeNote::create($data);

        return redirect()->route('myhealth.operation_theatre.post_op.index')->with('status', 'Post-operative note saved successfully.');
    }
}
