<?php

namespace Modules\DistributionNew\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Api\DisnewDriverApiService;

class DisnewDriverApiController extends Controller
{
    public function trips(Request $request, DisnewDriverApiService $service){ return response()->json($service->trips($request)); }
    public function acceptTrip($trip, Request $request, DisnewDriverApiService $service){ return response()->json($service->acceptTrip($trip, $request)); }
    public function checkpoint(Request $request, DisnewDriverApiService $service){ return response()->json($service->checkpoint($request)); }
    public function epod(Request $request, DisnewDriverApiService $service){ return response()->json($service->epod($request)); }
}
