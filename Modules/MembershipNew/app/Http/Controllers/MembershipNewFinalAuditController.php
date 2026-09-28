<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Audit\MembershipNewStandaloneAudit;

class MembershipNewFinalAuditController extends Controller
{
    public function index(MembershipNewStandaloneAudit $audit)
    {
        $checklist = $audit->checklist();
        $status = $audit->status();

        return view('membershipnew::final_audit.index', compact('checklist', 'status'));
    }
}
