<?php

namespace Modules\BeautySaloons\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Api\BeautyPortalApiResponse;
use Modules\BeautySaloons\Services\Portal\PortalDashboardService;

class CustomerApiController extends Controller
{
    public function profile(Request $request)
    {
        return BeautyPortalApiResponse::success($request->user());
    }

    public function dashboard(Request $request, PortalDashboardService $dashboard)
    {
        return BeautyPortalApiResponse::success($dashboard->summary($request->user()->id));
    }
}
