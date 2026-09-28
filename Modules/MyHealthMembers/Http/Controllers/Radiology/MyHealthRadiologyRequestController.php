<?php

namespace Modules\MyHealthMembers\Http\Controllers\Radiology;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthRadiologyRequest;
use Modules\MyHealthMembers\Services\Radiology\MyHealthRadiologyService;

class MyHealthRadiologyRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = MyHealthRadiologyRequest::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('modality'), fn ($query) => $query->where('modality', $request->modality))
            ->when($request->filled('member_id'), fn ($query) => $query->where('member_id', $request->member_id))
            ->latest()
            ->paginate(25);

        return view('myhealthmembers::radiology.requests.index', compact('requests'));
    }

    public function create(MyHealthRadiologyService $service)
    {
        return view('myhealthmembers::radiology.requests.create', [
            'nextRequestNo' => $service->nextRequestNumber(),
        ]);
    }

    public function store(Request $request, MyHealthRadiologyService $service)
    {
        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'consultation_id' => ['nullable', 'integer'],
            'appointment_id' => ['nullable', 'integer'],
            'request_no' => ['nullable', 'string', 'max:50'],
            'study_type' => ['required', 'string', 'max:100'],
            'modality' => ['required', 'string', 'max:50'],
            'body_part' => ['nullable', 'string', 'max:100'],
            'clinical_notes' => ['nullable', 'string'],
            'priority' => ['nullable', 'string', 'max:30'],
            'scheduled_at' => ['nullable', 'date'],
            'equipment_name' => ['nullable', 'string', 'max:100'],
            'room_no' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        $service->createRequest($data);

        return redirect()->route('myhealth.radiology.requests.index')
            ->with('status', 'Radiology request created successfully.');
    }

    public function stage(Request $request, MyHealthRadiologyRequest $radiologyRequest, MyHealthRadiologyService $service)
    {
        $request->validate(['status' => ['required', 'string', 'max:30']]);
        $service->updateStage($radiologyRequest, $request->status);

        return back()->with('status', 'Radiology request status updated.');
    }
}
