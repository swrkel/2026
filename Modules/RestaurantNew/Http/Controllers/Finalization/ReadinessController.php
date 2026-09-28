<?php

namespace Modules\RestaurantNew\Http\Controllers\Finalization;

use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\Finalization\RestaurantNewReadinessService;

class ReadinessController extends Controller
{
    public function index(RestaurantNewReadinessService $service)
    {
        return response()->json($service->checklist());
    }
}
