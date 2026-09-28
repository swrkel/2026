<?php

namespace Modules\RestaurantNew\Http\Controllers\Equipment;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Services\Equipment\EquipmentAssetService;

class EquipmentController extends Controller
{
    protected $service;
    public function __construct(EquipmentAssetService $service) { $this->service = $service; }

    public function dashboard(Request $request) { return view('restaurantnew::equipment.dashboard', $this->service->dashboard($request)); }
    public function assets(Request $request) { return view('restaurantnew::equipment.assets.index', ['assets' => $this->service->assets($request)]); }
    public function storeAsset(Request $request) { $this->service->storeAsset($request->all()); return redirect()->back()->with('status', __('restaurantnew::equipment.asset_saved')); }
    public function workOrders(Request $request) { return view('restaurantnew::equipment.work_orders.index', ['workOrders' => $this->service->workOrders($request)]); }
    public function storeWorkOrder(Request $request) { $this->service->storeWorkOrder($request->all()); return redirect()->back()->with('status', __('restaurantnew::equipment.work_order_saved')); }
    public function closeWorkOrder(Request $request, $id) { $this->service->closeWorkOrder($id, $request->all()); return redirect()->back()->with('status', __('restaurantnew::equipment.work_order_closed')); }
    public function spareParts(Request $request) { return view('restaurantnew::equipment.spare_parts.index', ['parts' => $this->service->spareParts($request)]); }
    public function schedules(Request $request) { return view('restaurantnew::equipment.schedules.index', ['schedules' => $this->service->schedules($request)]); }
    public function alerts(Request $request) { return view('restaurantnew::equipment.alerts.index', ['alerts' => $this->service->alerts($request)]); }
    public function reports(Request $request) { return view('restaurantnew::equipment.reports.index', $this->service->reports($request)); }
}
