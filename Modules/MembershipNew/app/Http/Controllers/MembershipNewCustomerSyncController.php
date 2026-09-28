<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewCustomerMap;
use Modules\MembershipNew\app\Models\MembershipNewMember;
use Modules\MembershipNew\app\Services\MembershipNewCustomerSyncService;

class MembershipNewCustomerSyncController extends Controller
{
    public function index()
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        $records = MembershipNewCustomerMap::with('member')
            ->forBusiness($businessId)
            ->latest()
            ->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(50))->withQueryString();

        $members = MembershipNewMember::forBusiness($businessId)->where('is_active', 1)->orderBy('first_name')->get();

        return view('membershipnew::customer_sync.index', compact('records', 'members'));
    }

    public function prepareAll(MembershipNewCustomerSyncService $service)
    {
        $count = $service->prepareAll(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId());

        return back()->with('status', $count . ' customer sync records prepared.');
    }

    public function prepareMember(Request $request, MembershipNewCustomerSyncService $service)
    {
        $count = $service->prepareMember(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), (int) $request->member_id);

        return back()->with('status', $count . ' customer sync records prepared for selected member.');
    }

    public function markSynced(Request $request, MembershipNewCustomerSyncService $service, int $mapId)
    {
        $service->markSynced(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $mapId, $request->linked_customer_id);

        return back()->with('status', 'Customer sync map marked as synced.');
    }
}
