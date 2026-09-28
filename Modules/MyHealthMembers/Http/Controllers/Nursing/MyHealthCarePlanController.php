<?php

namespace Modules\MyHealthMembers\Http\Controllers\Nursing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthNursingCarePlan;
use Modules\MyHealthMembers\Services\Nursing\MyHealthNursingService;

class MyHealthCarePlanController extends Controller
{
    public function index()
    {
        $plans = MyHealthNursingCarePlan::with('member')->latest()->paginate(25);
        return view('myhealthmembers::nursing.care_plans.index', compact('plans'));
    }

    public function create()
    {
        $members = MyHealthMember::orderBy('name')->limit(200)->get();
        return view('myhealthmembers::nursing.care_plans.create', compact('members'));
    }

    public function store(Request $request, MyHealthNursingService $service)
    {
        $data = $request->validate([
            'member_id' => 'required|integer',
            'nursing_diagnosis' => 'required|string|max:255',
            'goals' => 'nullable|string',
            'interventions' => 'nullable|string',
            'outcomes' => 'nullable|string',
            'review_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['location_id'] = $request->session()->get('business_location_id');
        $service->createCarePlan($data);

        return redirect()->route('myhealth.nursing.care_plans.index')->with('status', 'Care plan saved successfully.');
    }
}
