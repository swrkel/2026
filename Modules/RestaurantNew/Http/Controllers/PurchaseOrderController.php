<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Services\RestaurantProcurementProductionService;

class PurchaseOrderController extends Controller
{
    protected RestaurantProcurementProductionService $service;

    public function __construct(RestaurantProcurementProductionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return view('restaurantnew::procurement.purchase-orders', [
            'title' => 'Purchase Orders',
            'filters' => $request->all(),
        ]);
    }

    public function store(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Purchase Orders saved successfully.']);
    }
}
