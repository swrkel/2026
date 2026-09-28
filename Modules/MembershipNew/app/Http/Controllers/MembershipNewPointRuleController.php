<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewPointRule;

class MembershipNewPointRuleController extends Controller
{
    public function index()
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $records = MembershipNewPointRule::forBusiness($businessId)->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();
        return view('membershipnew::point_rules.index', compact('records'));
    }

    public function create()
    {
        return view('membershipnew::point_rules.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        MembershipNewPointRule::create($data);
        return redirect()->back()->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function show(MembershipNewPointRule $membershipNewPointRule)
    {
        $this->ensureCurrentBusiness($membershipNewPointRule);
        return view('membershipnew::point_rules.show', ['record' => $membershipNewPointRule]);
    }

    public function edit(MembershipNewPointRule $membershipNewPointRule)
    {
        $this->ensureCurrentBusiness($membershipNewPointRule);
        return view('membershipnew::point_rules.edit', ['record' => $membershipNewPointRule]);
    }

    public function update(Request $request, MembershipNewPointRule $membershipNewPointRule)
    {
        $this->ensureCurrentBusiness($membershipNewPointRule);
        $membershipNewPointRule->update($request->except(['business_id']));
        return redirect()->back()->with('status', __('membershipnew::messages.updated_successfully'));
    }

    public function destroy(MembershipNewPointRule $membershipNewPointRule)
    {
        $this->ensureCurrentBusiness($membershipNewPointRule);
        $membershipNewPointRule->delete();
        return redirect()->back()->with('status', __('membershipnew::messages.deleted_successfully'));
    }

    private function ensureCurrentBusiness(MembershipNewPointRule $membershipNewPointRule): void
    {
        abort_if((int) $membershipNewPointRule->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
    }
}
