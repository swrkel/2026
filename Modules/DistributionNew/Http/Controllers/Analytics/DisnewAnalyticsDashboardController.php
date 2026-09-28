<?php

namespace Modules\DistributionNew\Http\Controllers\Analytics;

use Illuminate\Routing\Controller;
use Modules\DistributionNew\Entities\DisnewAnalyticsSnapshot;
use Modules\DistributionNew\Entities\DisnewKpiMetric;

class DisnewAnalyticsDashboardController extends Controller
{
    public function executive()
    {
        $businessId = session('business.id');
        $metrics = DisnewKpiMetric::where('business_id', $businessId)->latest('metric_date')->limit(12)->get();
        $snapshots = DisnewAnalyticsSnapshot::where('business_id', $businessId)->latest('snapshot_date')->limit(30)->get();
        return view('distributionnew::analytics.executive_dashboard', compact('metrics', 'snapshots'));
    }

    public function kpi()
    {
        $businessId = session('business.id');
        $metrics = DisnewKpiMetric::where('business_id', $businessId)->orderByDesc('metric_date')->paginate(50);
        return view('distributionnew::analytics.kpi_dashboard', compact('metrics'));
    }
}
