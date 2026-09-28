<?php

namespace Modules\MyHealthMembers\Http\Controllers\Analytics;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\MyHealthAnalyticsService;

class MyHealthAnalyticsReportController extends Controller
{
    public function executive(Request $request, MyHealthAnalyticsService $service)
    {
        $analytics = $service->dashboard($request->only(['date_from', 'date_to']));
        return view('myhealthmembers::analytics.reports.executive', compact('analytics'));
    }

    public function clinical(Request $request, MyHealthAnalyticsService $service)
    {
        $analytics = $service->dashboard($request->only(['date_from', 'date_to']));
        return view('myhealthmembers::analytics.reports.clinical', compact('analytics'));
    }

    public function operational(Request $request, MyHealthAnalyticsService $service)
    {
        $analytics = $service->dashboard($request->only(['date_from', 'date_to']));
        return view('myhealthmembers::analytics.reports.operational', compact('analytics'));
    }

    public function financial(Request $request, MyHealthAnalyticsService $service)
    {
        $analytics = $service->dashboard($request->only(['date_from', 'date_to']));
        return view('myhealthmembers::analytics.reports.financial', compact('analytics'));
    }
}
