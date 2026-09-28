<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\POS\Services\POSEnterpriseMonitorService;

class EnterpriseMonitorController extends Controller
{
    public function __construct(private POSEnterpriseMonitorService $monitorService) {}
    public function index() { return view('pos::dashboard.enterprise_monitor', $this->monitorService->summary()); }
}
