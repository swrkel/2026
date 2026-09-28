<?php

namespace Modules\PetroGeneral\Http\Controllers\PetroDashboard;

use Illuminate\Routing\Controller;
use Modules\PetroGeneral\Services\PetroDashboard\PetroDashboardService;

class PetroDashboardController extends Controller
{
    protected PetroDashboardService $dashboardService;

    public function __construct(PetroDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Standalone Petro General dashboard.
     *
     * This intentionally does not resolve any controller, model, utility,
     * view, translation or asset from the legacy Petro module (or another
     * feature module). All dashboard data is prepared by PetroGeneral's own
     * service before the view is rendered.
     */
    public function index()
    {
        $businessId = (int) (session('user.business_id') ?: session('business.id'));

        abort_if($businessId <= 0, 403, 'Business context is not available.');

        $dashboard = $this->dashboardService->build($businessId);

        return view('petrogeneral::petro_dashboard.index', compact('dashboard'));
    }
}
