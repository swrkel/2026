<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewOnlineOrder;
use Modules\RestaurantNew\Services\OnlineOrderingService;

class OnlineOrderingController extends Controller
{
    protected OnlineOrderingService $service;

    public function __construct(OnlineOrderingService $service)
    {
        $this->service = $service;
    }

    public function portal(Request $request)
    {
        return view('restaurantnew::online.portal');
    }

    public function menu(Request $request)
    {
        return view('restaurantnew::online.menu');
    }

    public function checkout(Request $request)
    {
        return view('restaurantnew::online.checkout');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => 'required|integer',
            'location_id' => 'nullable|integer',
            'customer_name' => 'required|string|max:255',
            'mobile' => 'required|string|max:50',
            'email' => 'nullable|email',
            'order_type' => 'required|in:delivery,pickup,dine_in',
            'delivery_address' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'delivery_charge' => 'nullable|numeric',
            'lines' => 'required|array|min:1',
            'lines.*.item_name' => 'required|string|max:255',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_price' => 'required|numeric|min:0',
        ]);

        $lines = $data['lines']; unset($data['lines']);
        $order = $this->service->createOrder($data, $lines);

        return response()->json(['success' => true, 'order' => $order]);
    }

    public function kitchenQueue(Request $request)
    {
        $businessId = (int)($request->get('business_id') ?: session('business.id'));
        $locationId = $request->get('location_id') ? (int)$request->get('location_id') : null;
        $orders = $this->service->buildKitchenQueue($businessId, $locationId);
        return view('restaurantnew::online.kitchen_queue', compact('orders'));
    }

    public function changeStatus(Request $request, RestaurantNewOnlineOrder $order)
    {
        $data = $request->validate(['status' => 'required|string|max:40', 'remarks' => 'nullable|string']);
        $order = $this->service->changeStatus($order, $data['status'], $data['remarks'] ?? null, optional(auth()->user())->id);
        return response()->json(['success' => true, 'order' => $order]);
    }

    public function track(string $orderNo)
    {
        $order = RestaurantNewOnlineOrder::with(['lines', 'customer'])->where('online_order_no', $orderNo)->firstOrFail();
        return view('restaurantnew::online.track', compact('order'));
    }
}
