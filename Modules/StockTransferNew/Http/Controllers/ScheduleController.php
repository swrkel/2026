<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Entities\TransferSchedule;
use Modules\StockTransferNew\Entities\ScheduleExecutionLog;
use Modules\StockTransferNew\Services\StockTransferSchedulingService;

class ScheduleController extends Controller
{
    protected StockTransferSchedulingService $service;

    public function __construct(StockTransferSchedulingService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $schedules = TransferSchedule::with('items')
            ->where('business_id', $businessId)
            ->latest()
            ->paginate(25);
        return view('stocktransfernew::schedules.index', compact('schedules'));
    }

    public function create()
    {
        return view('stocktransfernew::schedules.create');
    }

    public function store(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $data = $request->validate([
            'schedule_name' => 'required|string|max:191',
            'schedule_type' => 'required|in:one_time,daily,weekly,monthly',
            'next_run_date' => 'required|date',
            'run_time' => 'nullable',
            'from_location_id' => 'nullable|integer',
            'from_store_id' => 'nullable|integer',
            'to_location_id' => 'nullable|integer',
            'to_store_id' => 'nullable|integer',
            'auto_create_draft' => 'nullable|boolean',
            'remarks' => 'nullable|string',
        ]);
        $data['business_id'] = $businessId;
        $data['auto_create_draft'] = $request->boolean('auto_create_draft');
        $items = $request->input('items', []);
        $this->service->store($data, $items, (int) auth()->id());
        return redirect()->route('stock-transfer-new.schedules.index')->with('status', __('stocktransfernew::lang.schedule_saved'));
    }

    public function logs(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $logs = ScheduleExecutionLog::where('business_id', $businessId)->latest()->paginate(50);
        return view('stocktransfernew::schedules.logs', compact('logs'));
    }

    public function pause(Request $request, TransferSchedule $schedule)
    {
        $schedule->update(['status' => 'paused', 'updated_by' => auth()->id()]);
        return back()->with('status', __('stocktransfernew::lang.schedule_paused'));
    }

    public function resume(Request $request, TransferSchedule $schedule)
    {
        $schedule->update(['status' => 'active', 'updated_by' => auth()->id()]);
        return back()->with('status', __('stocktransfernew::lang.schedule_resumed'));
    }
}
