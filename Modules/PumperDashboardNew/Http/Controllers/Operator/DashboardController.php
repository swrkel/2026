<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Operator;

use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Services\PoneDashboardService;

class DashboardController extends Controller
{
    public function __construct(private PoneDashboardService $dashboard) {}
    public function index() { return view('pumperdashboardnew::operator.dashboard', $this->dashboard->data()); }
}
