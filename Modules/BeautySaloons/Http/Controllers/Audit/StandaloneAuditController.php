<?php

namespace Modules\BeautySaloons\Http\Controllers\Audit;

use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Audit\StandaloneAuditService;

class StandaloneAuditController extends Controller
{
    public function index(StandaloneAuditService $auditService)
    {
        return view('beautysaloons::audit.index', [
            'auditRows' => $auditService->summary(),
            'readinessRows' => $auditService->releaseReadiness(),
        ]);
    }

    public function releaseCandidate(StandaloneAuditService $auditService)
    {
        return view('beautysaloons::release.rc1', [
            'readinessRows' => $auditService->releaseReadiness(),
            'version' => 'BS_RC1',
        ]);
    }
}
