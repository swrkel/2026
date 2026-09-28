<?php

namespace Modules\MyHealthMembers\Http\Controllers\Enterprise;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Enterprise\MyHealthEnterpriseReleaseService;

class MyHealthEnterpriseReleaseController extends Controller
{
    protected MyHealthEnterpriseReleaseService $service;

    public function __construct(MyHealthEnterpriseReleaseService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('myhealthmembers::enterprise.index', [
            'summary' => $this->service->releaseSummary(),
            'modules' => $this->service->moduleReadiness(),
        ]);
    }

    public function endToEnd()
    {
        return view('myhealthmembers::enterprise.e2e', [
            'steps' => $this->service->endToEndChecklist(),
        ]);
    }

    public function security()
    {
        return view('myhealthmembers::enterprise.security', [
            'checks' => $this->service->securityChecklist(),
        ]);
    }

    public function performance()
    {
        return view('myhealthmembers::enterprise.performance', [
            'checks' => $this->service->performanceChecklist(),
        ]);
    }

    public function releaseNotes()
    {
        return view('myhealthmembers::enterprise.release_notes', [
            'notes' => $this->service->releaseNotes(),
        ]);
    }
}
