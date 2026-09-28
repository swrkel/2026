<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewDeliveryOrder;
use Modules\RestaurantNew\Services\RestaurantDeliveryService;

class DeliveryController extends Controller
{
    protected RestaurantDeliveryService $service;

    public function __construct(RestaurantDeliveryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $orders = RestaurantNewDeliveryOrder::query()
            ->where('business_id', session('business.id'))
            ->when($request->status, fn ($q) => $q->where('delivery_status', $request->status))
            ->latest()
            ->paginate(25);

        return view('restaurantnew::delivery.index', compact('orders'));
    }

    public function storeZone(Request $request)
    {
        $this->service->createZone($request->all());
        return back()->with('status', __('restaurantnew::lang.delivery_zone_saved'));
    }

    public function storeRider(Request $request)
    {
        $this->service->createRider($request->all());
        return back()->with('status', __('restaurantnew::lang.rider_saved'));
    }

    public function storeAddress(Request $request)
    {
        $this->service->saveCustomerAddress($request->all());
        return back()->with('status', __('restaurantnew::lang.customer_address_saved'));
    }

    public function store(Request $request)
    {
        $order = $this->service->createDeliveryOrder($request->all());
        return redirect()->route('restaurantnew.delivery.show', $order->id)->with('status', __('restaurantnew::lang.delivery_order_saved'));
    }

    public function show(RestaurantNewDeliveryOrder $delivery)
    {
        return view('restaurantnew::delivery.show', ['order' => $delivery]);
    }

    public function assignRider(Request $request, RestaurantNewDeliveryOrder $delivery)
    {
        $this->service->assignRider($delivery, (int) $request->delivery_rider_id);
        return back()->with('status', __('restaurantnew::lang.rider_assigned'));
    }

    public function status(Request $request, RestaurantNewDeliveryOrder $delivery)
    {
        $this->service->changeStatus($delivery, $request->delivery_status, $request->note);
        return back()->with('status', __('restaurantnew::lang.delivery_status_updated'));
    }
}
