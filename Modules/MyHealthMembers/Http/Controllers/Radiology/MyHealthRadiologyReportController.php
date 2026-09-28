<?php

namespace Modules\MyHealthMembers\Http\Controllers\Radiology;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthRadiologyReport;
use Modules\MyHealthMembers\Entities\MyHealthRadiologyRequest;
use Modules\MyHealthMembers\Services\Radiology\MyHealthRadiologyService;

class MyHealthRadiologyReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = MyHealthRadiologyReport::query()
            ->with('request')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('member_id'), fn ($query) => $query->where('member_id', $request->member_id))
            ->latest()
            ->paginate(25);

        return view('myhealthmembers::radiology.reports.index', compact('reports'));
    }

    public function create(MyHealthRadiologyService $service)
    {
        $requests = MyHealthRadiologyRequest::whereIn('status', ['performed', 'reporting', 'requested', 'scheduled'])
            ->latest()
            ->limit(100)
            ->get();

        return view('myhealthmembers::radiology.reports.create', [
            'requests' => $requests,
            'nextReportNo' => $service->nextReportNumber(),
        ]);
    }

    public function store(Request $request, MyHealthRadiologyService $service)
    {
        $data = $request->validate([
            'radiology_request_id' => ['required', 'integer'],
            'report_no' => ['nullable', 'string', 'max:50'],
            'findings' => ['required', 'string'],
            'impression' => ['nullable', 'string'],
            'recommendations' => ['nullable', 'string'],
            'critical_finding' => ['nullable'],
            'critical_notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);

        $service->saveReport($data);

        return redirect()->route('myhealth.radiology.reports.index')
            ->with('status', 'Radiology report saved successfully.');
    }
}
