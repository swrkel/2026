<?php

namespace Modules\MyHealthMembers\Http\Controllers\Nursing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthMedicationAdministration;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\Nursing\MyHealthNursingService;

class MyHealthMedicationAdministrationController extends Controller
{
    public function index()
    {
        $records = MyHealthMedicationAdministration::with('member')->latest()->paginate(25);
        return view('myhealthmembers::nursing.medications.index', compact('records'));
    }

    public function create()
    {
        $members = MyHealthMember::orderBy('name')->limit(200)->get();
        return view('myhealthmembers::nursing.medications.create', compact('members'));
    }

    public function store(Request $request, MyHealthNursingService $service)
    {
        $data = $request->validate([
            'member_id' => 'required|integer',
            'medicine_name' => 'required|string|max:255',
            'dose' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:100',
            'time_due' => 'nullable|date',
            'time_given' => 'nullable|date',
            'status' => 'required|string|max:30',
            'missed_reason' => 'nullable|string',
            'adverse_reaction' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['location_id'] = $request->session()->get('business_location_id');
        $service->createMedicationAdministration($data);

        return redirect()->route('myhealth.nursing.medications.index')->with('status', 'Medication administration record saved successfully.');
    }
}
