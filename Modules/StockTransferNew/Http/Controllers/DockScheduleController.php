<?php

namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\DockScheduleService;

class DockScheduleController extends Controller
{
    protected DockScheduleService $service;

    public function __construct(DockScheduleService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['business_id','location_id','store_id','dock_no','date','status']);
        $schedules = $this->service->list($filters);
        return view('stocktransfernew::dock_schedules.index', compact('schedules','filters'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => 'required|integer',
            'location_id' => 'required|integer',
            'store_id' => 'nullable|integer',
            'dock_no' => 'required|string|max:50',
            'schedule_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'capacity_units' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $this->service->create($data);
        return redirect()->back()->with('status', __('stocktransfernew::messages.dock_schedule_saved'));
    }

    public function close($id)
    {
        $this->service->close((int)$id);
        return redirect()->back()->with('status', __('stocktransfernew::messages.dock_schedule_closed'));
    }
}
