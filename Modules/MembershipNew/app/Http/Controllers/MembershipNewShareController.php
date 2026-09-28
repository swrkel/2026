<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewShareHolding;

class MembershipNewShareController extends Controller
{
    public function index()
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $records = MembershipNewShareHolding::forBusiness($businessId)->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();
        return view('membershipnew::shares.index', compact('records'));
    }

    public function create()
    {
        return view('membershipnew::shares.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        MembershipNewShareHolding::create($data);
        return redirect()->back()->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function show(MembershipNewShareHolding $membershipNewShareHolding)
    {
        $this->ensureCurrentBusiness($membershipNewShareHolding);
        return view('membershipnew::shares.show', ['record' => $membershipNewShareHolding]);
    }

    public function edit(MembershipNewShareHolding $membershipNewShareHolding)
    {
        $this->ensureCurrentBusiness($membershipNewShareHolding);
        return view('membershipnew::shares.edit', ['record' => $membershipNewShareHolding]);
    }

    public function update(Request $request, MembershipNewShareHolding $membershipNewShareHolding)
    {
        $this->ensureCurrentBusiness($membershipNewShareHolding);
        $membershipNewShareHolding->update($request->except(['business_id']));
        return redirect()->back()->with('status', __('membershipnew::messages.updated_successfully'));
    }

    public function destroy(MembershipNewShareHolding $membershipNewShareHolding)
    {
        $this->ensureCurrentBusiness($membershipNewShareHolding);
        $membershipNewShareHolding->delete();
        return redirect()->back()->with('status', __('membershipnew::messages.deleted_successfully'));
    }

    private function ensureCurrentBusiness(MembershipNewShareHolding $membershipNewShareHolding): void
    {
        abort_if((int) $membershipNewShareHolding->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
    }
}
