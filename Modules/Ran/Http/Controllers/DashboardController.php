<?php

namespace Modules\Ran\Http\Controllers;

use Modules\Ran\Services\DashboardService;

class DashboardController extends RanController
{
    public function index(DashboardService $service)
    {
        return view('ran::dashboard.index', array_merge($service->summary(), $this->pageOptions()));
    }
}
