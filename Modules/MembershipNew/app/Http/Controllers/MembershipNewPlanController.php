<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewPlan;

class MembershipNewPlanController extends Controller
{
    public function index(Request $request)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $records = MembershipNewPlan::forBusiness($businessId)->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();

        return view('membershipnew::memberships.index', compact('records'));
    }

    public function create()
    {
        return view('membershipnew::memberships.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        MembershipNewPlan::create($data);

        return redirect()->route('membership-new.plans.index')->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function show(MembershipNewPlan $membershipNewPlan)
    {
        $this->ensureCurrentBusiness($membershipNewPlan);
        return view('membershipnew::memberships.show', ['record' => $membershipNewPlan]);
    }

    public function edit(MembershipNewPlan $membershipNewPlan)
    {
        $this->ensureCurrentBusiness($membershipNewPlan);
        return view('membershipnew::memberships.edit', ['record' => $membershipNewPlan]);
    }

    public function update(Request $request, MembershipNewPlan $membershipNewPlan)
    {
        $this->ensureCurrentBusiness($membershipNewPlan);
        $membershipNewPlan->update($request->except(['business_id']));

        return redirect()->route('membership-new.plans.index')->with('status', __('membershipnew::messages.updated_successfully'));
    }

    public function destroy(MembershipNewPlan $membershipNewPlan)
    {
        $this->ensureCurrentBusiness($membershipNewPlan);
        $membershipNewPlan->delete();

        return redirect()->route('membership-new.plans.index')->with('status', __('membershipnew::messages.deleted_successfully'));
    }

    private function ensureCurrentBusiness(MembershipNewPlan $membershipNewPlan): void
    {
        abort_if((int) $membershipNewPlan->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
    }
}
