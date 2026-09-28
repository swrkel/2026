<?php

namespace Modules\RiceMill\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\RiceMill\Services\DashboardAccessService;

class DashboardPortalController extends Controller
{
    public function __construct(private DashboardAccessService $access)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $businessId = (int) ($request->session()->get('rice_mill_dashboard_business_id') ?: $user->business_id);
        $business = $this->access->business($businessId);
        $actions = $this->access->actions($user, $businessId);

        return view('RiceMill::dashboard_portal.dashboard', compact('user', 'business', 'actions'));
    }
}
