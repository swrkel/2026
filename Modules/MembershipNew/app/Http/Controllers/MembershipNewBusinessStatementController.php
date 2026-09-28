<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewMemberBusinessMap;
use Modules\MembershipNew\app\Reports\MembershipNewBusinessStatementReport;

class MembershipNewBusinessStatementController extends Controller
{
    public function index(Request $request, MembershipNewBusinessStatementReport $report)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $mapId = $request->member_business_map_id ? (int) $request->member_business_map_id : null;

        $perPage = in_array((int) $request->input('per_page', 50), [10, 25, 50, 100, 200], true) ? (int) $request->input('per_page', 50) : 50;
        $records = $report->query($businessId, $mapId, $request->from_date, $request->to_date, $request->input('q'))->paginate($perPage)->appends($request->query());
        $maps = MembershipNewMemberBusinessMap::with('centralMember')->forBusiness($businessId)->get();

        return view('membershipnew::business_statement.index', compact('records', 'maps'));
    }
}
