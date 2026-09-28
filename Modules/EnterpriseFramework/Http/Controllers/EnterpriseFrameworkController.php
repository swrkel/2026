<?php

namespace Modules\EnterpriseFramework\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EnterpriseFramework\Services\Registry\ReportRegistryService;
use Modules\EnterpriseFramework\Services\Notification\NotificationCenterService;
use Modules\EnterpriseFramework\Services\Scheduler\ReportSchedulerService;

class EnterpriseFrameworkController extends Controller
{
    public function dashboard(ReportRegistryService $registry)
    {
        return view('enterpriseframework::dashboard.index', [
            'title' => 'Enterprise Framework',
            'summary' => $registry->summary(),
        ]);
    }

    public function registry(ReportRegistryService $registry)
    {
        return view('enterpriseframework::admin.registry', [
            'reports' => $registry->all(),
        ]);
    }

    public function schedules(ReportSchedulerService $scheduler)
    {
        return view('enterpriseframework::admin.schedules', [
            'schedules' => $scheduler->all(),
        ]);
    }

    public function notifications(NotificationCenterService $notifications)
    {
        return view('enterpriseframework::admin.notifications', [
            'notifications' => $notifications->all(),
        ]);
    }

    public function admin(ReportRegistryService $registry)
    {
        return view('enterpriseframework::admin.index', [
            'summary' => $registry->summary(),
        ]);
    }

    public function refreshRegistry(Request $request, ReportRegistryService $registry)
    {
        $registry->refresh();
        return redirect()->back()->with('status', 'Enterprise report registry refreshed successfully.');
    }
}
