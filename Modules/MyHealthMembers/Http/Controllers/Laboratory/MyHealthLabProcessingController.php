<?php

namespace Modules\MyHealthMembers\Http\Controllers\Laboratory;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthLabResult;
use Modules\MyHealthMembers\Entities\MyHealthLabSample;
use Modules\MyHealthMembers\Services\Laboratory\MyHealthLaboratoryService;

class MyHealthLabProcessingController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::laboratory.processing.index', [
            'samples' => MyHealthLabSample::whereIn('status', ['received', 'processing', 'verified', 'approved'])->latest()->paginate(25),
            'results' => MyHealthLabResult::latest()->paginate(25),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::laboratory.processing.create', [
            'samples' => MyHealthLabSample::latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request, MyHealthLaboratoryService $service)
    {
        $service->enterResult($request->only([
            'business_id', 'location_id', 'sample_id', 'test_id', 'member_id', 'result_value',
            'unit', 'reference_range', 'interpretation', 'is_abnormal', 'is_critical',
            'technician_comments', 'doctor_comments', 'status',
        ]));

        return redirect()->route('myhealth.laboratory.processing.index')->with('status', 'Laboratory result entered successfully.');
    }
}
