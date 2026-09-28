<?php

namespace Modules\MyHealthMembers\Http\Controllers\Analytics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\MyHealthAnalyticsService;

class MyHealthAnalyticsDashboardController extends Controller
{
    public function index(Request $request, MyHealthAnalyticsService $service)
    {
        $analytics = $service->dashboard($request->only(['date_from', 'date_to']));

        return view('myhealthmembers::analytics.dashboard', compact('analytics'));
    }
}
