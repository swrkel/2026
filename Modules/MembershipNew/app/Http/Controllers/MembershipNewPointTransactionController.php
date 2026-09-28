<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewMember;
use Modules\MembershipNew\app\Models\MembershipNewPointTransaction;
use Modules\MembershipNew\app\Services\MembershipNewPointService;

class MembershipNewPointTransactionController extends Controller
{
    public function index(Request $request)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $perPage = in_array((int) $request->input('per_page', 50), [10,25,50,100,200], true) ? (int) $request->input('per_page', 50) : 50;
        $search = $request->input('q');
        $records = MembershipNewPointTransaction::with('member')->forBusiness($businessId)
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('type', 'like', $like)->orWhere('reference_type', 'like', $like)->orWhere('reference_id', 'like', $like)
                        ->orWhereHas('member', function ($member) use ($like) {
                            $member->where('member_code', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like);
                        });
                });
            })->latest()->paginate($perPage)->appends($request->query());
        $members = MembershipNewMember::forBusiness($businessId)->where('is_active', 1)->orderBy('first_name')->get();

        return view('membershipnew::points.index', compact('records', 'members'));
    }

    public function memberLedger(Request $request, int $memberId, MembershipNewPointService $service)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $member = MembershipNewMember::forBusiness($businessId)->findOrFail($memberId);
        $perPage = in_array((int) $request->input('per_page', 50), [10,25,50,100,200], true) ? (int) $request->input('per_page', 50) : 50;
        $records = MembershipNewPointTransaction::forBusiness($businessId)->where('member_id', $memberId)
            ->when($request->filled('q'), function ($q) use ($request) {
                $like = '%' . $request->q . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('type', 'like', $like)->orWhere('reference_type', 'like', $like)->orWhere('reference_id', 'like', $like)->orWhere('note', 'like', $like);
                });
            })->latest()->paginate($perPage)->appends($request->query());
        $balance = $service->balance($businessId, $memberId);

        return view('membershipnew::points.member-ledger', compact('member', 'records', 'balance'));
    }

    public function earn(Request $request, MembershipNewPointService $service)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $data['created_by'] = auth()->id();
        $service->earn($data);

        return back()->with('status', __('membershipnew::messages.points_earned'));
    }

    public function redeem(Request $request, MembershipNewPointService $service)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $data['created_by'] = auth()->id();
        $service->redeem($data);

        return back()->with('status', __('membershipnew::messages.points_redeemed'));
    }
}
