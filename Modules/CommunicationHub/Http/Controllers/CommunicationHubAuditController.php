<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;

use Modules\CommunicationHub\Entities\CommunicationHubAuditLog;
use Modules\CommunicationHub\Services\Support\StandaloneDependencyAudit;

class CommunicationHubAuditController extends Controller
{
    public function index(StandaloneDependencyAudit $audit)
    {
        $checklist = $audit->checklist();
        $logs = CommunicationHubAuditLog::latest()->limit(50)->get();
        return view('communicationhub::audit.index', compact('checklist','logs'));
    }
}
