<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Admin;

use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\AdminShiftStoreRequest;
use Modules\PumperDashboardNew\Services\PoneAdminShiftService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class ShiftController extends Controller
{
    public function __construct(private PoneAdminShiftService $shifts, private PoneSharedMasterDataService $masterData) {}
    public function index()
    {
        $businessId = $this->businessId();
        $shifts = PoneShift::query()->where('business_id', $businessId)->with('operatorProfile')->latest('opened_at')->paginate(50);
        return view('pumperdashboardnew::admin.shifts.index', compact('shifts'));
    }
    public function create()
    {
        $businessId = $this->businessId();
        $operators = PonePdOperator::query()->where('business_id', $businessId)->where('status', 'active')->where('login_enabled', true)->orderBy('display_name')->get();
        $pumps = $this->masterData->pumps($businessId);
        $locations = $this->masterData->locations($businessId);
        return view('pumperdashboardnew::admin.shifts.create', compact('operators', 'pumps', 'locations'));
    }
    public function store(AdminShiftStoreRequest $request)
    {
        $shift = $this->shifts->create($this->businessId(), $request->validated(), (int) auth()->id());
        return redirect()->route('pumper-dashboard-new.admin.shifts.show', $shift)->with('status', ['success' => 1, 'msg' => __('pumperdashboardnew::lang.shift_created')]);
    }
    public function show(int $shift)
    {
        $shift = PoneShift::query()->whereKey($shift)->where('business_id', $this->businessId())
            ->with(['operatorProfile', 'assignments.events', 'payments.creditSale.lines', 'payments.cashDenominations', 'payments.cardLines', 'otherSales.lines', 'unloadStocks.lines', 'dayEntries', 'collections', 'settlementReferences', 'shortageRecoveries', 'excessCommissions'])->firstOrFail();
        $pumps = $this->masterData->pumps($shift->business_id, $shift->location_id)->keyBy('id');
        $availablePumps = $this->masterData->pumps($shift->business_id, $shift->location_id);
        return view('pumperdashboardnew::admin.shifts.show', compact('shift', 'pumps', 'availablePumps'));
    }
}
