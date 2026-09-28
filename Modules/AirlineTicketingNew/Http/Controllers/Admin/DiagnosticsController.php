<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Diagnostics\ModuleDiagnosticsService;

class DiagnosticsController extends Controller
{
    public function index(ModuleDiagnosticsService $service)
    {
        return view('airlineticketingnew::admin.diagnostics.index', [
            'diagnostics' => $service->run((int) session('business.id')),
        ]);
    }
}
