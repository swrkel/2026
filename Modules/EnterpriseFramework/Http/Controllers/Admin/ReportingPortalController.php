<?php

namespace Modules\EnterpriseFramework\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\EnterpriseFramework\Services\Portal\EnterpriseReportingPortalService;

class ReportingPortalController extends Controller
{
    public function index(EnterpriseReportingPortalService $portal)
    {
        return view('enterpriseframework::portal.index', ['summary' => $portal->summary(), 'menu' => $portal->menu()]);
    }
}
