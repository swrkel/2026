<?php

namespace Modules\MyHealthMembers\Http\Controllers\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthImmunizationSchedule;
use Modules\MyHealthMembers\Entities\MyHealthVaccine;

class MyHealthImmunizationScheduleController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::vaccination.schedules.index', ['schedules' => MyHealthImmunizationSchedule::orderBy('schedule_type')->orderBy('recommended_age_days')->paginate(25)]);
    }

    public function create()
    {
        return view('myhealthmembers::vaccination.schedules.create', ['vaccines' => MyHealthVaccine::orderBy('vaccine_name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'schedule_name' => 'required|string|max:191', 'schedule_type' => 'required|string|max:50', 'vaccine_id' => 'required|integer', 'dose_no' => 'nullable|integer|min:1',
            'recommended_age_days' => 'nullable|integer|min:0', 'recommended_age_text' => 'nullable|string|max:191', 'interval_days' => 'nullable|integer|min:0', 'is_mandatory' => 'nullable|boolean', 'notes' => 'nullable|string'
        ]);
        $data['business_id'] = session('business.id') ?? session('user.business_id');
        $data['status'] = 'active';
        $data['created_by'] = auth()->id();
        $data['is_mandatory'] = $request->boolean('is_mandatory');
        MyHealthImmunizationSchedule::create($data);
        return redirect()->route('myhealth.vaccination.schedules.index')->with('status', 'Immunization schedule saved successfully.');
    }
}
