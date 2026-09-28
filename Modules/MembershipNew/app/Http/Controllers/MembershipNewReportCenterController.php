<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MembershipNew\app\Reports\MembershipNewReportCenter;

class MembershipNewReportCenterController extends Controller
{
    public function index(MembershipNewReportCenter $center)
    {
        $reports = $center->reports();

        return view('membershipnew::report_center.index', compact('reports'));
    }
}
