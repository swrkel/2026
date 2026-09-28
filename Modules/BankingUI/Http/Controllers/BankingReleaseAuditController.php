<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingUI\Services\BankingReleaseAuditService;

class BankingReleaseAuditController extends Controller
{
    public function __construct(private BankingReleaseAuditService $service) {}

    public function index()
    {
        return view('bankingui::audit.index', ['summary' => $this->service->summary()]);
    }

    public function sidebar()
    {
        return view('bankingui::audit.sidebar', ['items' => $this->service->sidebarItems()]);
    }

    public function routes()
    {
        return view('bankingui::audit.routes', ['routes' => $this->service->routeCoverage()]);
    }

    public function permissions()
    {
        return view('bankingui::audit.permissions', ['permissions' => $this->service->permissionCoverage()]);
    }

    public function checklist()
    {
        return view('bankingui::audit.checklist', ['checklist' => $this->service->releaseChecklist()]);
    }
}
