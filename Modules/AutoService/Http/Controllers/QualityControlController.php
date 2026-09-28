<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Services\AutoServiceQualityControlService;

class QualityControlController extends AutoServiceBaseController
{
    public function index(AutoServiceQualityControlService $service)
    {
        $jobs = $service->listPending($this->businessId())->paginate(25);
        return view('autoservice::quality_control.index', compact('jobs'));
    }

    public function store(Request $request, AutoServiceQualityControlService $service)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'status' => 'nullable|string',
            'mechanical_checked' => 'nullable',
            'electrical_checked' => 'nullable',
            'road_test_done' => 'nullable',
            'wash_done' => 'nullable',
            'customer_concern_verified' => 'nullable',
            'remarks' => 'nullable|string',
        ]);
        $service->saveCheck((int)$data['job_id'], $data, $this->businessId(), $this->locationId());
        return redirect()->route('autoservice.quality_control.index')->with('status', 'Quality control saved successfully.');
    }
}
