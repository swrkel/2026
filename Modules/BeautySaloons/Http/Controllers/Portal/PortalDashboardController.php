<?php

namespace Modules\BeautySaloons\Http\Controllers\Portal;

use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Portal\PortalCustomerSessionService;
use Modules\BeautySaloons\Services\Portal\PortalDashboardService;

class PortalDashboardController extends Controller
{
    public function index(PortalCustomerSessionService $session, PortalDashboardService $dashboard)
    {
        $customer = $session->customer();
        $summary = $dashboard->summary($customer->id);
        return view('beautysaloons::portal.dashboard', compact('customer', 'summary'));
    }
}
