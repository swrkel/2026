<?php

namespace Modules\MyHealthMembers\Http\Controllers\OperationTheatre;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthSurgeryChecklist;
use Modules\MyHealthMembers\Entities\MyHealthSurgerySchedule;

class MyHealthSurgeryChecklistController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::operation_theatre.checklists.index', [
            'checklists' => MyHealthSurgeryChecklist::orderByDesc('created_at')->paginate(25),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::operation_theatre.checklists.create', [
            'schedules' => MyHealthSurgerySchedule::orderByDesc('scheduled_start_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'surgery_schedule_id' => 'required|integer',
            'member_id' => 'required|integer',
            'remarks' => 'nullable|string',
        ]);

        foreach (['consent_verified','identity_verified','procedure_site_marked','allergy_checked','investigations_completed','blood_available','anaesthesia_clearance','fasting_confirmed','equipment_ready','implant_available','antibiotic_given'] as $field) {
            $data[$field] = $request->boolean($field);
        }

        $data['business_id'] = session('business.id') ?? session('user.business_id');
        $data['checklist_status'] = collect($data)->only(['consent_verified','identity_verified','procedure_site_marked','allergy_checked','investigations_completed','blood_available','anaesthesia_clearance','fasting_confirmed','equipment_ready','implant_available','antibiotic_given'])->every() ? 'completed' : 'pending';
        $data['checked_by'] = auth()->id();
        $data['checked_at'] = now();
        $data['created_by'] = auth()->id();

        MyHealthSurgeryChecklist::create($data);

        return redirect()->route('myhealth.operation_theatre.checklists.index')->with('status', 'Pre-operative checklist saved successfully.');
    }
}
