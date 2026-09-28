<?php

namespace Modules\EnterpriseFramework\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\EnterpriseFramework\Services\Admin\ReportAdministrationService;
use Modules\EnterpriseFramework\Services\Widget\EnterpriseWidgetRegistry;

class EnterpriseFrameworkAdminController extends Controller
{
    public function index(ReportAdministrationService $admin, EnterpriseWidgetRegistry $widgets)
    {
        return view('enterpriseframework::admin.index', [
            'menu_groups' => $admin->menuGroups(),
            'settings' => $admin->defaultSettings(),
            'widgets' => $widgets->all(),
        ]);
    }

    public function widgets(EnterpriseWidgetRegistry $widgets)
    {
        return response()->json(['widgets' => $widgets->all()]);
    }
}
