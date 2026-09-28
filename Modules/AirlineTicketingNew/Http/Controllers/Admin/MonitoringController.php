<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\PerformanceMetric;
use Modules\AirlineTicketingNew\Services\Monitoring\PerformanceMonitorService;

class MonitoringController extends Controller
{
    public function index(PerformanceMonitorService $service)
    {
        $businessId = (int) session('business.id');

        return view('airlineticketingnew::admin.monitoring.index', [
            'current' => $service->capture($businessId),
            'history' => PerformanceMetric::query()
                ->where('business_id', $businessId)
                ->latest('recorded_at')
                ->limit(100)
                ->get(),
        ]);
    }
}
