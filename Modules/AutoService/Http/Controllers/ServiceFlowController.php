<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Modules\AutoService\Services\AutoServiceServiceFlowService;

class ServiceFlowController extends AutoServiceBaseController
{
    public function index(Request $request, AutoServiceServiceFlowService $service)
    {
        $jobs = $service->board($this->businessId(), $this->locationId())
            ->when($request->filled('status'), fn($q) => $q->where('j.status', $request->get('status')))
            ->paginate(25);
        $mechanics = $service->mechanics($this->businessId(), $this->locationId());
        return view('autoservice::service_flow.index', compact('jobs', 'mechanics'));
    }

    public function assign(Request $request, AutoServiceServiceFlowService $service)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'mechanic_id' => 'required|integer',
            'estimated_hours' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);
        $service->assignMechanic((int)$data['job_id'], (int)$data['mechanic_id'], $data, $this->businessId(), $this->locationId());
        return back()->with('status', 'Technician assigned successfully.');
    }

    public function mechanicStatus(Request $request, AutoServiceServiceFlowService $service, $assignmentId)
    {
        $data = $request->validate([
            'status' => 'required|string|in:assigned,in_progress,completed,on_hold',
            'actual_hours' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);
        $service->updateMechanicStatus((int)$assignmentId, $data['status'], $data);
        return back()->with('status', 'Technician status updated successfully.');
    }

    public function inspectionCheckpoint(Request $request, AutoServiceServiceFlowService $service)
    {
        $data = $request->validate([
            'job_id' => 'required|integer',
            'odometer' => 'nullable|integer|min:0',
            'fuel_level' => 'nullable|string',
            'customer_remarks' => 'nullable|string',
            'advisor_remarks' => 'nullable|string',
        ]);
        $data['items'] = [
            ['section' => 'Safety', 'item_name' => 'Brake check', 'condition' => $request->get('brake_condition')],
            ['section' => 'Safety', 'item_name' => 'Tyre check', 'condition' => $request->get('tyre_condition')],
            ['section' => 'Mechanical', 'item_name' => 'Engine bay check', 'condition' => $request->get('engine_condition')],
            ['section' => 'Electrical', 'item_name' => 'Lights and battery check', 'condition' => $request->get('electrical_condition')],
        ];
        $service->saveInspectionCheckpoint((int)$data['job_id'], $data, $this->businessId(), $this->locationId());
        return back()->with('status', 'Inspection checkpoint saved successfully.');
    }

    public function readyForQc(Request $request, AutoServiceServiceFlowService $service, $jobId)
    {
        $data = $request->validate(['remarks' => 'nullable|string']);
        $service->markReadyForQc((int)$jobId, $data['remarks'] ?? null);
        return back()->with('status', 'Job moved to Quality Control.');
    }
}
