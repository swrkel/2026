<?php

namespace Modules\MyHealthMembers\Http\Controllers\Certification;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\Certification\MyHealthProductionCertificationService;

class MyHealthProductionCertificationController extends Controller
{
    protected MyHealthProductionCertificationService $service;

    public function __construct(MyHealthProductionCertificationService $service)
    {
        $this->service = $service;
    }

    public function dashboard()
    {
        return view('myhealthmembers::certification.dashboard', [
            'summary' => $this->service->summary(),
        ]);
    }

    public function standalone()
    {
        return view('myhealthmembers::certification.standalone', [
            'checklist' => $this->service->standaloneChecklist(),
        ]);
    }

    public function security()
    {
        return view('myhealthmembers::certification.security', [
            'checklist' => $this->service->securityChecklist(),
        ]);
    }

    public function performance()
    {
        return view('myhealthmembers::certification.performance', [
            'checklist' => $this->service->performanceChecklist(),
        ]);
    }

    public function workflow()
    {
        return view('myhealthmembers::certification.workflow', [
            'checklist' => $this->service->workflowChecklist(),
        ]);
    }

    public function releaseNotes()
    {
        return view('myhealthmembers::certification.release_notes');
    }
}
