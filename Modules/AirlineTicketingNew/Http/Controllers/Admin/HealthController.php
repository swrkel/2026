<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Health\ModuleHealthCheckService;

class HealthController extends Controller
{
    public function index(ModuleHealthCheckService $service)
    {
        return view('airlineticketingnew::admin.health.index', ['health' => $service->run()]);
    }
}
