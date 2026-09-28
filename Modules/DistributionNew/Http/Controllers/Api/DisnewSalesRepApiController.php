<?php

namespace Modules\DistributionNew\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Api\DisnewSalesRepApiService;

class DisnewSalesRepApiController extends Controller
{
    public function dashboard(Request $request, DisnewSalesRepApiService $service){ return response()->json($service->dashboard($request)); }
    public function storeOrder(Request $request, DisnewSalesRepApiService $service){ return response()->json($service->storeOrder($request)); }
    public function storeCollection(Request $request, DisnewSalesRepApiService $service){ return response()->json($service->storeCollection($request)); }
}
