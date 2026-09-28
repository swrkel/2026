<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\ManagementReport\Entities\ReportRun;
use Modules\ManagementReport\Entities\ReportShare;
use Modules\ManagementReport\Support\TenantConnection;

class DashboardController extends Controller
{
    public function index()
    {
        // Force and verify the dynamic tenant database before the first query.
        TenantConnection::activate();

        $businessId = (int) session('user.business_id');
        $stats = [
            'reports_today' => ReportRun::where('business_id', $businessId)->whereDate('generated_at', today())->count(),
            'reports_month' => ReportRun::where('business_id', $businessId)->whereBetween('generated_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'shared_reports' => ReportShare::where('business_id', $businessId)->whereIn('status', ['sent', 'queued', 'ready'])->count(),
            'active_links' => ReportShare::where('business_id', $businessId)->whereNull('revoked_at')->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count(),
        ];
        $recent = ReportRun::where('business_id', $businessId)->latest('generated_at')->limit(8)->get();

        return view('managementreport::dashboard.index', compact('stats', 'recent'));
    }
}
