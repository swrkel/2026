<?php

namespace Modules\DistributionNew\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Api\DisnewCustomerApiService;

class DisnewCustomerApiController extends Controller
{
    public function orders(Request $request, DisnewCustomerApiService $service){ return response()->json($service->orders($request)); }
    public function storeOrder(Request $request, DisnewCustomerApiService $service){ return response()->json($service->storeOrder($request)); }
    public function invoices(Request $request, DisnewCustomerApiService $service){ return response()->json($service->invoices($request)); }
    public function deliveries(Request $request, DisnewCustomerApiService $service){ return response()->json($service->deliveries($request)); }
}
