<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Reports\MembershipNewReportBuilder;

class MembershipNewReportController extends Controller
{
    private function perPage(Request $request): int
    {
        $value = (int) $request->input('per_page', 50);
        return in_array($value, [10, 25, 50, 100, 200], true) ? $value : 50;
    }

    public function memberBalances(Request $request, MembershipNewReportBuilder $report)
    {
        $records = $report->memberBalances(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $request->input('q'))->paginate($this->perPage($request))->appends($request->query());
        return view('membershipnew::reports.member-balances', compact('records'));
    }

    public function pointLedger(Request $request, MembershipNewReportBuilder $report)
    {
        $records = $report->pointLedger(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $request->input('q'))->paginate($this->perPage($request))->appends($request->query());
        return view('membershipnew::reports.point-ledger', compact('records'));
    }

    public function shareRegister(Request $request, MembershipNewReportBuilder $report)
    {
        $records = $report->shareRegister(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $request->input('q'))->paginate($this->perPage($request))->appends($request->query());
        return view('membershipnew::reports.share-register', compact('records'));
    }

    public function dividendRegister(Request $request, MembershipNewReportBuilder $report)
    {
        $records = $report->dividendRegister(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $request->input('q'))->paginate($this->perPage($request))->appends($request->query());
        return view('membershipnew::reports.dividend-register', compact('records'));
    }
}
