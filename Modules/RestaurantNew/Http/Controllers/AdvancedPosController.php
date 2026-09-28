<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewHeldOrder;
use Modules\RestaurantNew\Entities\RestaurantNewSaleOrder;
use Modules\RestaurantNew\Services\RestaurantAdvancedPosService;
use Modules\RestaurantNew\Services\RestaurantTableOperationService;

class AdvancedPosController extends Controller
{
    protected RestaurantAdvancedPosService $advancedPos;
    protected RestaurantTableOperationService $tableOps;

    public function __construct(RestaurantAdvancedPosService $advancedPos, RestaurantTableOperationService $tableOps)
    {
        $this->advancedPos = $advancedPos;
        $this->tableOps = $tableOps;
    }

    public function advanced(Request $request)
    {
        $businessId = session('business.id');
        $orders = RestaurantNewSaleOrder::with('lines')
            ->where('business_id', $businessId)
            ->whereIn('status', ['received', 'preparing', 'ready', 'served', 'held'])
            ->latest()
            ->limit(50)
            ->get();

        return view('restaurantnew::pos.advanced', compact('orders'));
    }

    public function hold(Request $request, RestaurantNewSaleOrder $order)
    {
        $held = $this->advancedPos->holdOrder($order, $request->input('reason'));
        return response()->json(['success' => true, 'held_order' => $held]);
    }

    public function resume(RestaurantNewHeldOrder $heldOrder)
    {
        $order = $this->advancedPos->resumeOrder($heldOrder);
        return response()->json(['success' => true, 'order' => $order]);
    }

    public function split(Request $request, RestaurantNewSaleOrder $order)
    {
        $request->validate(['bills' => 'required|array|min:1']);
        $splits = $this->advancedPos->splitBill($order, $request->input('bills', []));
        return response()->json(['success' => true, 'split_bills' => $splits]);
    }

    public function payments(Request $request, RestaurantNewSaleOrder $order)
    {
        $request->validate(['payments' => 'required|array|min:1']);
        $payments = $this->advancedPos->recordMultiplePayments($order, $request->input('payments', []));
        return response()->json(['success' => true, 'payments' => $payments, 'order' => $order->fresh()]);
    }

    public function transferTable(Request $request, RestaurantNewSaleOrder $order)
    {
        $request->validate(['to_table_id' => 'required|integer']);
        $updated = $this->tableOps->transferTable($order, $order->table_id, (int)$request->input('to_table_id'), $request->all());
        return response()->json(['success' => true, 'order' => $updated]);
    }

    public function changeWaiter(Request $request, RestaurantNewSaleOrder $order)
    {
        $request->validate(['to_waiter_id' => 'required|integer']);
        $updated = $this->tableOps->changeWaiter($order, (int)$request->input('to_waiter_id'), $request->all());
        return response()->json(['success' => true, 'order' => $updated]);
    }

    public function merge(Request $request, RestaurantNewSaleOrder $order)
    {
        $request->validate(['secondary_order_ids' => 'required|array|min:1']);
        $updated = $this->tableOps->mergeOrders($order, $request->input('secondary_order_ids', []), $request->all());
        return response()->json(['success' => true, 'order' => $updated]);
    }
}
