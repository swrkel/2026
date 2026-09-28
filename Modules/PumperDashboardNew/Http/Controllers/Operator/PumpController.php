<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Operator;

use Illuminate\Http\Request;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\MeterReadingRequest;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PonePrintService;
use Modules\PumperDashboardNew\Services\PonePumpService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class PumpController extends Controller
{
    public function __construct(
        private PoneContextService $context,
        private PonePumpService $pumps,
        private PoneSharedMasterDataService $masterData,
        private PonePrintService $prints
    ) {}

    public function index()
    {
        $shift = $this->context->shift();
        $assignments = $shift->assignments()->with(['events', 'readings'])->orderBy('pump_id')->get();
        $pumpMasters = $this->masterData->pumps($shift->business_id, $shift->location_id)->keyBy('id');
        return view('pumperdashboardnew::operator.pumps.index', compact('shift', 'assignments', 'pumpMasters'));
    }

    public function accept(Request $request, int $assignment)
    {
        $this->pumps->accept($assignment, $request->input('note'));
        return $this->ok(__('pumperdashboardnew::lang.pump_received'));
    }

    public function confirm(Request $request, int $assignment)
    {
        $this->pumps->confirm($assignment, $request->input('note'));
        return $this->ok(__('pumperdashboardnew::lang.pump_confirmed'));
    }

    public function history(int $assignment)
    {
        $assignment = $this->pumps->assignment($assignment)->load(['events', 'readings']);
        $pump = $this->masterData->pump($assignment->business_id, $assignment->pump_id);
        return view('pumperdashboardnew::operator.pumps.history', compact('assignment', 'pump'));
    }

    public function current(int $assignment)
    {
        $assignment = $this->pumps->assignment($assignment);
        $pump = $this->masterData->pump($assignment->business_id, $assignment->pump_id);
        return view('pumperdashboardnew::operator.pumps.current', compact('assignment', 'pump'));
    }

    public function storeCurrent(MeterReadingRequest $request, int $assignment)
    {
        $this->pumps->recordCurrent($assignment, (float) $request->validated('meter'),
            (float) ($request->validated('testing_quantity') ?? 0),
            $request->filled('unit_price') ? (float) $request->validated('unit_price') : null,
            $request->validated('note'));
        return redirect()->route('pumper-dashboard-new.operator.pumps.index', ['mode' => 'current'])
            ->with('status', ['success' => 1, 'msg' => __('pumperdashboardnew::lang.current_meter_saved')]);
    }

    public function closing(int $assignment)
    {
        $assignment = $this->pumps->assignment($assignment);
        $pump = $this->masterData->pump($assignment->business_id, $assignment->pump_id);
        return view('pumperdashboardnew::operator.pumps.close', compact('assignment', 'pump'));
    }

    public function close(MeterReadingRequest $request, int $assignment)
    {
        $this->pumps->close($assignment, (float) $request->validated('meter'),
            (float) ($request->validated('testing_quantity') ?? 0),
            $request->filled('unit_price') ? (float) $request->validated('unit_price') : null,
            $request->validated('note'));
        return redirect()->route('pumper-dashboard-new.operator.pumps.index', ['mode' => 'close'])
            ->with('status', ['success' => 1, 'msg' => __('pumperdashboardnew::lang.pump_closed')]);
    }

    public function closedStatement()
    {
        $shift = $this->context->shift(true)->load(['assignments.events']);
        $this->prints->record('shift', $shift, 'closed-pumps-statement', request('paper_size'), $shift->closed_statement_printed_at ? 'reprint' : 'original');
        $shift->forceFill(['closed_statement_printed_at' => now()])->saveQuietly();
        $pumps = $this->masterData->pumps($shift->business_id, $shift->location_id)->keyBy('id');
        $business = $this->masterData->business($shift->business_id);
        $location = $this->masterData->location($shift->location_id);
        return view('pumperdashboardnew::operator.pumps.closed-statement', compact('shift', 'pumps', 'business', 'location'));
    }
}
