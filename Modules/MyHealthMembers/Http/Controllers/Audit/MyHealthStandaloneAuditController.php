<?php

namespace Modules\MyHealthMembers\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Modules\MyHealthMembers\Services\Audit\MyHealthStandaloneAuditService;

class MyHealthStandaloneAuditController extends Controller
{
    protected MyHealthStandaloneAuditService $auditService;

    public function __construct(MyHealthStandaloneAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function index()
    {
        $audit = $this->auditService->run();

        return view('myhealthmembers::audit.index', compact('audit'));
    }
}
