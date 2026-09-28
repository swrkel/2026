<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Manifest\MembershipNewManifest;
use Modules\MembershipNew\app\Support\MembershipNewServerReadiness;

class MembershipNewHandoverController extends Controller
{
    public function index(MembershipNewServerReadiness $readiness)
    {
        $version = MembershipNewManifest::version();
        $modules = MembershipNewManifest::modules();
        $routeFiles = MembershipNewManifest::routeFiles();
        $sqlFiles = MembershipNewManifest::sqlFiles();
        $checklist = $readiness->checklist();

        return view('membershipnew::handover.index', compact('version', 'modules', 'routeFiles', 'sqlFiles', 'checklist'));
    }
}
