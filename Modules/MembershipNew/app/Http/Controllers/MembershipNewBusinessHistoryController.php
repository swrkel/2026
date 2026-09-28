<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewMemberBusinessMap;
use Modules\MembershipNew\app\Reports\MembershipNewCentralReport;
use Modules\MembershipNew\app\Services\MembershipNewBusinessHistoryService;

class MembershipNewBusinessHistoryController extends Controller
{
    public function index(Request $request, MembershipNewCentralReport $report)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $mapId = $request->member_business_map_id ? (int) $request->member_business_map_id : null;

        $perPage = in_array((int) $request->input('per_page', 50), [10,25,50,100,200], true) ? (int) $request->input('per_page', 50) : 50;
        $records = $report->businessHistory($businessId, $mapId, $request->input('q'))->paginate($perPage)->appends($request->query());
        $maps = MembershipNewMemberBusinessMap::with('centralMember')->forBusiness($businessId)->get();

        return view('membershipnew::business_history.index', compact('records', 'maps'));
    }

    public function ledgerEntry(Request $request, MembershipNewBusinessHistoryService $service)
    {
        $service->addLedgerEntry([
            'business_id' => \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(),
            'member_business_map_id' => (int) $request->member_business_map_id,
            'transaction_type' => $request->transaction_type,
            'debit' => (float) $request->debit,
            'credit' => (float) $request->credit,
            'reference_type' => $request->reference_type,
            'reference_id' => $request->reference_id,
            'note' => $request->note,
            'meta' => $request->meta ? json_decode($request->meta, true) : null,
        ]);

        return back()->with('status', 'Business customer ledger entry saved.');
    }
}
