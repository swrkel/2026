<?php

namespace Modules\MyHealthMembers\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Admin\MyHealthAdministrationService;

class MyHealthAdministrationController extends Controller
{
    public function index(MyHealthAdministrationService $service)
    {
        return view('myhealthmembers::admin.dashboard.index', [
            'summary' => $service->dashboard(),
        ]);
    }
}
