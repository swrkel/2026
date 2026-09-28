<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerDashboardService;
use Modules\Customers\Services\CustomerMenuService;
use Modules\Customers\Services\CustomerPermissionService;

/**
 * CUS_SEP_007
 * Customers-owned dashboard controller.
 *
 * This controller intentionally belongs to Modules/Customers and does not call
 * Contact module controllers or Contact module views. It prepares dashboard
 * widgets, quick actions and navigation data for the standalone Customers UI.
 */
class CustomerDashboardController extends Controller
{
    protected $dashboardService;
    protected $menuService;
    protected $permissionService;

    public function __construct(
        CustomerDashboardService $dashboardService,
        CustomerMenuService $menuService,
        CustomerPermissionService $permissionService
    ) {
        $this->dashboardService = $dashboardService;
        $this->menuService = $menuService;
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        $this->permissionService->authorize('dashboard');

        $businessId = (int) $request->session()->get('user.business_id');

        $data = $this->dashboardService->dashboardData($businessId);
        $data['customer_menu'] = $this->menuService->moduleMenu();
        $data['quick_actions'] = $this->menuService->quickActions();

        return view('customers::dashboard.index', $data);
    }
}
