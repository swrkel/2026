<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\CommunicationHub\Services\Hardening\ProductionHardeningService;

class CommunicationHubProductionController extends Controller
{
    protected ProductionHardeningService $hardening;

    public function __construct(ProductionHardeningService $hardening)
    {
        $this->hardening = $hardening;
    }

    public function index()
    {
        return view('communicationhub::production.index', $this->hardening->dashboard());
    }

    public function standalone()
    {
        return view('communicationhub::production.standalone', [
            'checks' => $this->hardening->checks(),
        ]);
    }

    public function security()
    {
        return view('communicationhub::production.security', [
            'security' => $this->hardening->securityChecklist(),
            'monitoring' => $this->hardening->monitoringChecklist(),
        ]);
    }
}
