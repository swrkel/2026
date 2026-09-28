<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Entities\RestaurantNewSaleOrder;
use Modules\RestaurantNew\Services\RestaurantSaleService;

class SaleController extends Controller
{
    protected RestaurantSaleService $service;

    public function __construct(RestaurantSaleService $service)
    {
        $this->service = $service;
    }

    public function create()
    {
        return view('restaurantnew::pos.sale-create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_id' => 'required|integer',
            'location_id' => 'nullable|integer',
            'order_type' => 'nullable|string',
            'table_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'waiter_id' => 'nullable|integer',
            'cashier_id' => 'nullable|integer',
            'discount_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'service_charge_amount' => 'nullable|numeric',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'nullable|integer',
            'items.*.menu_item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
        $data['created_by'] = auth()->id();
        $order = $this->service->createOrder($data);
        return redirect()->route('restaurantnew.sales.show', $order->id)->with('status', __('restaurantnew::restaurantnew.sale_created_sent_to_kitchen'));
    }

    public function show(RestaurantNewSaleOrder $sale)
    {
        $sale->load('lines');
        return view('restaurantnew::pos.sale-show', compact('sale'));
    }

    public function printBill(RestaurantNewSaleOrder $sale)
    {
        $sale->load('lines');
        return view('restaurantnew::print.bill', compact('sale'));
    }
}
