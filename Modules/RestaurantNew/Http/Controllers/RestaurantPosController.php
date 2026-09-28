<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewOrder;
use Modules\RestaurantNew\Services\RestaurantPosService;

class RestaurantPosController extends Controller
{
    protected RestaurantPosService $posService;

    public function __construct(RestaurantPosService $posService)
    {
        $this->posService = $posService;
        $this->middleware(['web', 'auth']);
    }

    public function index(): Renderable
    {
        return view('restaurantnew::pos.index');
    }

    public function runningOrders(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');

        $orders = RestaurantNewOrder::with('lines')
            ->where('business_id', $businessId)
            ->when($request->location_id, fn ($q) => $q->where('location_id', $request->location_id))
            ->whereIn('order_status', ['open', 'sent_to_kitchen', 'served'])
            ->latest('id')
            ->get();

        return response()->json(['data' => $orders]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'location_id' => 'nullable|integer',
            'dining_area_id' => 'nullable|integer',
            'restaurant_table_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'guest_count' => 'nullable|integer|min:1',
            'items' => 'array',
        ]);

        $payload['business_id'] = $request->session()->get('user.business_id');
        $order = $this->posService->createOrder($payload);

        return response()->json(['success' => true, 'order' => $order]);
    }

    public function addItem(Request $request, RestaurantNewOrder $order)
    {
        $line = $this->posService->addLine($order, $request->all());
        $this->posService->recalculate($order);

        return response()->json(['success' => true, 'line' => $line, 'order' => $order->fresh('lines')]);
    }

    public function addPayment(Request $request, RestaurantNewOrder $order)
    {
        $payment = $request->validate([
            'payment_method' => 'required|string|max:50',
            'payment_account_id' => 'nullable|integer',
            'amount' => 'required|numeric|min:0.0001',
            'reference_no' => 'nullable|string|max:191',
            'payment_note' => 'nullable|string',
        ]);

        $row = $this->posService->addPayment($order, $payment);

        return response()->json(['success' => true, 'payment' => $row, 'order' => $order->fresh(['lines', 'payments'])]);
    }

    public function close(RestaurantNewOrder $order)
    {
        $this->posService->recalculate($order);
        $order->update(['order_status' => 'completed', 'completed_at' => now()]);

        return response()->json(['success' => true, 'order' => $order->fresh(['lines', 'payments'])]);
    }
}
