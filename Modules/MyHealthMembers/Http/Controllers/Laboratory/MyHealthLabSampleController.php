<?php

namespace Modules\MyHealthMembers\Http\Controllers\Laboratory;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthLabSample;
use Modules\MyHealthMembers\Services\Laboratory\MyHealthLaboratoryService;

class MyHealthLabSampleController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::laboratory.samples.index', [
            'samples' => MyHealthLabSample::latest()->paginate(25),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::laboratory.samples.create');
    }

    public function store(Request $request, MyHealthLaboratoryService $service)
    {
        $service->collectSample($request->only([
            'business_id', 'location_id', 'member_id', 'consultation_id', 'lab_request_id',
            'sample_type', 'priority', 'remarks', 'collected_at',
        ]));

        return redirect()->route('myhealth.laboratory.samples.index')->with('status', 'Sample collected successfully.');
    }

    public function stage(Request $request, MyHealthLabSample $sample, MyHealthLaboratoryService $service)
    {
        $service->updateSampleStage($sample, $request->input('status', 'processing'));
        return back()->with('status', 'Sample status updated successfully.');
    }
}
