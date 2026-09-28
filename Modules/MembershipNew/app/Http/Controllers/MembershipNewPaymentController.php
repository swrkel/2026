<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewPayment;

class MembershipNewPaymentController extends Controller
{
    public function index(Request $request)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        $records = MembershipNewPayment::forBusiness($businessId)->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();

        return view('membershipnew::payments.index', compact('records'));
    }

    public function create()
    {
        return view('membershipnew::payments.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();
        MembershipNewPayment::create($data);

        return redirect()->route('membership-new.payments.index')->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function show(MembershipNewPayment $membershipNewPayment)
    {
        $this->ensureCurrentBusiness($membershipNewPayment);
        return view('membershipnew::payments.show', ['record' => $membershipNewPayment]);
    }

    public function edit(MembershipNewPayment $membershipNewPayment)
    {
        $this->ensureCurrentBusiness($membershipNewPayment);
        return view('membershipnew::payments.edit', ['record' => $membershipNewPayment]);
    }

    public function update(Request $request, MembershipNewPayment $membershipNewPayment)
    {
        $this->ensureCurrentBusiness($membershipNewPayment);
        $membershipNewPayment->update($request->except(['business_id']));

        return redirect()->route('membership-new.payments.index')->with('status', __('membershipnew::messages.updated_successfully'));
    }

    public function destroy(MembershipNewPayment $membershipNewPayment)
    {
        $this->ensureCurrentBusiness($membershipNewPayment);
        $membershipNewPayment->delete();

        return redirect()->route('membership-new.payments.index')->with('status', __('membershipnew::messages.deleted_successfully'));
    }

    private function ensureCurrentBusiness(MembershipNewPayment $membershipNewPayment): void
    {
        abort_if((int) $membershipNewPayment->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
    }
}
