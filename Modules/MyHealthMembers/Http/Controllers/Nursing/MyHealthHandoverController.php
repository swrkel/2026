<?php

namespace Modules\MyHealthMembers\Http\Controllers\Nursing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthNursingHandover;
use Modules\MyHealthMembers\Services\Nursing\MyHealthNursingService;

class MyHealthHandoverController extends Controller
{
    public function index()
    {
        $handovers = MyHealthNursingHandover::with('member')->latest()->paginate(25);
        return view('myhealthmembers::nursing.handovers.index', compact('handovers'));
    }

    public function create()
    {
        $members = MyHealthMember::orderBy('name')->limit(200)->get();
        return view('myhealthmembers::nursing.handovers.create', compact('members'));
    }

    public function store(Request $request, MyHealthNursingService $service)
    {
        $data = $request->validate([
            'member_id' => 'required|integer',
            'to_nurse_id' => 'nullable|integer',
            'from_shift' => 'required|string|max:20',
            'to_shift' => 'required|string|max:20',
            'patient_summary' => 'nullable|string',
            'outstanding_tasks' => 'nullable|string',
            'critical_alerts' => 'nullable|string',
            'pending_investigations' => 'nullable|string',
            'pending_medications' => 'nullable|string',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['location_id'] = $request->session()->get('business_location_id');
        $service->createHandover($data);

        return redirect()->route('myhealth.nursing.handovers.index')->with('status', 'Handover saved successfully.');
    }
}
