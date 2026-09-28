<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenQueue;
use Modules\RestaurantNew\Services\RestaurantSaleService;

class KitchenScreenController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $orders = RestaurantNewKitchenQueue::with('order.lines')
            ->when($status, fn($q) => $q->where('status', $status))
            ->whereIn('status', ['received', 'preparing', 'ready', 'served'])
            ->orderByRaw("FIELD(status, 'received', 'preparing', 'ready', 'served')")
            ->latest('received_at')
            ->paginate(30);
        return view('restaurantnew::kitchen.screen', compact('orders', 'status'));
    }

    public function status(RestaurantNewKitchenQueue $queue, Request $request, RestaurantSaleService $service)
    {
        $request->validate(['status' => 'required|in:received,preparing,ready,served,cancelled']);
        $service->updateKitchenStatus($queue, $request->status);
        return back()->with('status', __('restaurantnew::restaurantnew.kitchen_status_updated'));
    }

    public function printKot(RestaurantNewKitchenQueue $queue, RestaurantSaleService $service)
    {
        $queue->load('order.lines');
        $service->markPrinted($queue);
        return view('restaurantnew::print.kot', compact('queue'));
    }
}
