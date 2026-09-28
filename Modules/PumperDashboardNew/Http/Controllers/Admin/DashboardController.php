<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Admin;

use Modules\PumperDashboardNew\Entities\PoneIntegrationOutbox;
use Modules\PumperDashboardNew\Entities\PonePdOperator;
use Modules\PumperDashboardNew\Entities\PoneShift;
use Modules\PumperDashboardNew\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $businessId = $this->businessId();
        $metrics = [
            'operators' => PonePdOperator::query()->where('business_id', $businessId)->where('status', 'active')->count(),
            'open_shifts' => PoneShift::query()->where('business_id', $businessId)->whereIn('status', ['open', 'closing'])->count(),
            'closed_today' => PoneShift::query()->where('business_id', $businessId)->where('status', 'closed')->whereDate('closed_at', today())->count(),
            'integration_issues' => PoneIntegrationOutbox::query()->where('business_id', $businessId)->whereIn('status', ['pending', 'failed'])->count(),
        ];
        $recentShifts = PoneShift::query()->where('business_id', $businessId)->with('operatorProfile')->latest('opened_at')->limit(12)->get();
        return view('pumperdashboardnew::admin.dashboard', compact('metrics', 'recentShifts'));
    }
}
