<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewLinkedBusiness;
use Modules\MembershipNew\app\Models\MembershipNewPointRule;
use Modules\MembershipNew\app\Models\MembershipNewShareHolding;

class MembershipNewSimpleCrudController extends Controller
{
    public function linkedBusinesses()
    {
        $records = MembershipNewLinkedBusiness::forBusiness(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId())->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();
        return view('membershipnew::linked_businesses.index', compact('records'));
    }

    public function storeLinkedBusiness(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $data['created_by'] = auth()->id();
        $data['is_active'] = $request->has('is_active');
        MembershipNewLinkedBusiness::create($data);
        return back()->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function pointRules()
    {
        $records = MembershipNewPointRule::forBusiness(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId())->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();
        return view('membershipnew::point_rules.index', compact('records'));
    }

    public function storePointRule(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $data['created_by'] = auth()->id();
        $data['is_active'] = $request->has('is_active');
        MembershipNewPointRule::create($data);
        return back()->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function shares()
    {
        $records = MembershipNewShareHolding::with('member')->forBusiness(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId())->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();
        return view('membershipnew::shares.index', compact('records'));
    }

    public function storeShare(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $data['created_by'] = auth()->id();

        MembershipNewShareHolding::updateOrCreate(
            ['business_id' => $data['business_id'], 'member_id' => $data['member_id']],
            $data
        );

        return back()->with('status', __('membershipnew::messages.saved_successfully'));
    }
}
