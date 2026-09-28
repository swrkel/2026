<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Services\RestaurantProcurementProductionService;

class CommissaryTransferController extends Controller
{
    protected RestaurantProcurementProductionService $service;

    public function __construct(RestaurantProcurementProductionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return view('restaurantnew::procurement.commissary-transfers', [
            'title' => 'Commissary Transfers',
            'filters' => $request->all(),
        ]);
    }

    public function store(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Commissary Transfers saved successfully.']);
    }
}
