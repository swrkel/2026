<?php

namespace Modules\MyHealthMembers\Http\Controllers\Nursing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthNursingVitalSign;
use Modules\MyHealthMembers\Services\Nursing\MyHealthNursingService;

class MyHealthVitalSignController extends Controller
{
    public function index()
    {
        $vitals = MyHealthNursingVitalSign::with('member')->latest()->paginate(25);
        return view('myhealthmembers::nursing.vitals.index', compact('vitals'));
    }

    public function create()
    {
        $members = MyHealthMember::orderBy('name')->limit(200)->get();
        return view('myhealthmembers::nursing.vitals.create', compact('members'));
    }

    public function store(Request $request, MyHealthNursingService $service)
    {
        $data = $request->validate([
            'member_id' => 'required|integer',
            'temperature' => 'nullable|numeric',
            'pulse' => 'nullable|integer',
            'respiration' => 'nullable|integer',
            'systolic_bp' => 'nullable|integer',
            'diastolic_bp' => 'nullable|integer',
            'spo2' => 'nullable|integer|min:0|max:100',
            'height_feet' => 'nullable|integer|min:0|max:9',
            'height_inches' => 'nullable|integer|min:0|max:11',
            'weight' => 'nullable|numeric',
            'blood_sugar' => 'nullable|numeric',
            'pain_scale' => 'nullable|integer|min:0|max:10',
            'remarks' => 'nullable|string',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['location_id'] = $request->session()->get('business_location_id');
        $service->createVitalSign($data);

        return redirect()->route('myhealth.nursing.vitals.index')->with('status', 'Vital signs saved successfully.');
    }
}
