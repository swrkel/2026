<?php

namespace Modules\MyHealthMembers\Http\Controllers\Laboratory;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthLabTestCatalogue;
use Modules\MyHealthMembers\Services\Laboratory\MyHealthLaboratoryService;

class MyHealthLabTestCatalogueController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::laboratory.catalogue.index', [
            'tests' => MyHealthLabTestCatalogue::latest()->paginate(25),
        ]);
    }

    public function create()
    {
        return view('myhealthmembers::laboratory.catalogue.create');
    }

    public function store(Request $request, MyHealthLaboratoryService $service)
    {
        $service->createTest($request->only([
            'business_id', 'location_id', 'test_code', 'test_name', 'department', 'category',
            'sample_type', 'normal_range', 'turnaround_time', 'price', 'instructions', 'status',
        ]));

        return redirect()->route('myhealth.laboratory.catalogue.index')->with('status', 'Laboratory test saved successfully.');
    }
}
