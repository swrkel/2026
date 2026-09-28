<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\Admin\MaintenanceService;

class MaintenanceController extends Controller
{
    protected $maintenance;

    public function __construct(MaintenanceService $maintenance)
    {
        $this->maintenance = $maintenance;
    }

    public function index(Request $request)
    {
        $businessId = (int) session('user.business_id');
        $filters = $request->only(['status', 'priority', 'owner_user_id', 'from_date', 'to_date']);
        $summary = $this->maintenance->summary($businessId, $filters);
        $items = $this->maintenance->list($businessId, $filters);

        return view('stocktransfernew::admin.maintenance.index', compact('summary', 'items', 'filters'));
    }

    public function store(Request $request)
    {
        $businessId = (int) session('user.business_id');
        $userId = (int) auth()->id();
        $this->maintenance->create($businessId, $userId, $request->all());

        return redirect()->back()->with('status', __('stocktransfernew::maintenance.created'));
    }

    public function updateStatus($id, Request $request)
    {
        $businessId = (int) session('user.business_id');
        $userId = (int) auth()->id();
        $this->maintenance->updateStatus($businessId, $userId, (int) $id, (string) $request->input('status'), (string) $request->input('remarks'));

        return redirect()->back()->with('status', __('stocktransfernew::maintenance.updated'));
    }

    public function calendar(Request $request)
    {
        $businessId = (int) session('user.business_id');
        $month = $request->input('month', now()->format('Y-m'));
        $calendar = $this->maintenance->calendar($businessId, $month);

        return view('stocktransfernew::admin.maintenance.calendar', compact('calendar', 'month'));
    }
}
