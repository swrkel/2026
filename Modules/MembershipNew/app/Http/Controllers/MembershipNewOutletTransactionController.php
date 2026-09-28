<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewOutletTransactionQueue;
use Modules\MembershipNew\app\Services\MembershipNewOutletTransactionService;
use Modules\MembershipNew\app\Integration\MembershipNewPurchaseIntegration;

class MembershipNewOutletTransactionController extends Controller
{
    public function index()
    {
        $records = MembershipNewOutletTransactionQueue::forBusiness(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId())
            ->latest()
            ->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(50))->withQueryString();

        return view('membershipnew::outlet_transactions.index', compact('records'));
    }

    public function store(Request $request, MembershipNewOutletTransactionService $service)
    {
        $payload = $request->all();
        $payload['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $service->queue($payload);

        return back()->with('status', 'Outlet membership transaction queued.');
    }

    public function process(int $queueId, MembershipNewOutletTransactionService $service, MembershipNewPurchaseIntegration $integration)
    {
        $service->process($queueId, $integration);

        return back()->with('status', 'Outlet membership transaction processed.');
    }
}
