<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewDividendPayment;
use Modules\MembershipNew\app\Models\MembershipNewDividendPayout;
use Modules\MembershipNew\app\Services\MembershipNewDividendPayoutService;

class MembershipNewDividendPayoutController extends Controller
{
    public function index()
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $perPage = in_array((int) request('per_page', 50), [10,25,50,100,200], true) ? (int) request('per_page', 50) : 50;
        $search = request('q');

        $pending = MembershipNewDividendPayment::where('business_id', $businessId)
            ->where('is_paid', 0)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('member_id', $search)->orWhere('amount', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate($perPage, ['*'], 'pending_page')->appends(request()->query());

        $payouts = MembershipNewDividendPayout::forBusiness($businessId)
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like, $search) {
                    $sub->where('member_id', $search)->orWhere('payment_method', 'like', $like)->orWhere('payment_ref_no', 'like', $like);
                });
            })
            ->latest()
            ->paginate($perPage, ['*'], 'payout_page')->appends(request()->query());

        return view('membershipnew::dividend_payouts.index', compact('pending', 'payouts'));
    }

    public function pay(Request $request, int $paymentId, MembershipNewDividendPayoutService $service)
    {
        $service->pay(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $paymentId, $request->all());

        return back()->with('status', 'Dividend paid.');
    }

    public function reverse(Request $request, int $payoutId, MembershipNewDividendPayoutService $service)
    {
        $service->reverse(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $payoutId, $request->reversal_note);

        return back()->with('status', 'Dividend payout reversed.');
    }
}
