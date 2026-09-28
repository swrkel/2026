<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\AutoService\Services\AutoServiceReleaseAuditService;

class ReleaseAuditController extends Controller
{
    public function index(AutoServiceReleaseAuditService $audit)
    {
        $summary = $audit->summary();
        return view('autoservice::release.audit', compact('summary'));
    }
}
