<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewLinkedBusiness;

class MembershipNewLinkedBusinessController extends Controller
{
    public function index()
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $records = MembershipNewLinkedBusiness::forBusiness($businessId)->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();
        return view('membershipnew::linked_businesses.index', compact('records'));
    }

    public function create()
    {
        return view('membershipnew::linked_businesses.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        MembershipNewLinkedBusiness::create($data);
        return redirect()->back()->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function show(MembershipNewLinkedBusiness $membershipNewLinkedBusiness)
    {
        $this->ensureCurrentBusiness($membershipNewLinkedBusiness);
        return view('membershipnew::linked_businesses.show', ['record' => $membershipNewLinkedBusiness]);
    }

    public function edit(MembershipNewLinkedBusiness $membershipNewLinkedBusiness)
    {
        $this->ensureCurrentBusiness($membershipNewLinkedBusiness);
        return view('membershipnew::linked_businesses.edit', ['record' => $membershipNewLinkedBusiness]);
    }

    public function update(Request $request, MembershipNewLinkedBusiness $membershipNewLinkedBusiness)
    {
        $this->ensureCurrentBusiness($membershipNewLinkedBusiness);
        $membershipNewLinkedBusiness->update($request->except(['business_id']));
        return redirect()->back()->with('status', __('membershipnew::messages.updated_successfully'));
    }

    public function destroy(MembershipNewLinkedBusiness $membershipNewLinkedBusiness)
    {
        $this->ensureCurrentBusiness($membershipNewLinkedBusiness);
        $membershipNewLinkedBusiness->delete();
        return redirect()->back()->with('status', __('membershipnew::messages.deleted_successfully'));
    }

    private function ensureCurrentBusiness(MembershipNewLinkedBusiness $membershipNewLinkedBusiness): void
    {
        abort_if((int) $membershipNewLinkedBusiness->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
    }
}
