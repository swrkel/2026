<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingUI\Services\BankingUiSmokeTestService;

class BankingUiSmokeTestController extends Controller
{
    public function __construct(private BankingUiSmokeTestService $service) {}

    public function index()
    {
        return view('bankingui::smoke.index', [
            'releaseChecklist' => $this->service->releaseChecklist(),
        ]);
    }

    public function routes()
    {
        return view('bankingui::smoke.routes', ['checks' => $this->service->routeChecks()]);
    }

    public function permissions()
    {
        return view('bankingui::smoke.permissions', ['checks' => $this->service->permissionChecks()]);
    }

    public function sidebar()
    {
        return view('bankingui::smoke.sidebar', ['checks' => $this->service->sidebarChecks()]);
    }
}
