<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewDividendBatch;
use Modules\MembershipNew\app\Services\MembershipNewDividendService;

class MembershipNewDividendController extends Controller
{
    public function index()
    {
        $records = MembershipNewDividendBatch::withCount('payments')->forBusiness(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId())->latest()->paginate(\Modules\MembershipNew\app\Support\MembershipNewContext::perPage(25))->withQueryString();
        return view('membershipnew::dividends.index', compact('records'));
    }

    public function create()
    {
        return view('membershipnew::dividends.create');
    }

    public function store(Request $request, MembershipNewDividendService $service)
    {
        $batch = $service->createBatch(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $request->all());
        return redirect()->route('membership-new.dividends.show', $batch->id)->with('status', __('membershipnew::messages.saved_successfully'));
    }

    public function show(MembershipNewDividendBatch $dividend)
    {
        abort_if((int) $dividend->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
        $dividend->load(['payments.member']);
        return view('membershipnew::dividends.show', ['record' => $dividend]);
    }

    public function edit(MembershipNewDividendBatch $dividend)
    {
        abort_if((int) $dividend->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
        return view('membershipnew::dividends.edit', ['record' => $dividend]);
    }

    public function update(Request $request, MembershipNewDividendBatch $dividend)
    {
        abort_if((int) $dividend->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
        abort_if($dividend->is_posted, 403);
        $dividend->update($request->all());
        return redirect()->route('membership-new.dividends.index')->with('status', __('membershipnew::messages.updated_successfully'));
    }

    public function destroy(MembershipNewDividendBatch $dividend)
    {
        abort_if((int) $dividend->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
        abort_if($dividend->is_posted, 403);
        $dividend->delete();
        return back()->with('status', __('membershipnew::messages.deleted_successfully'));
    }

    public function post(MembershipNewDividendBatch $dividend, MembershipNewDividendService $service)
    {
        abort_if((int) $dividend->business_id !== \Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), 403);
        $service->post($dividend);
        return back()->with('status', __('membershipnew::messages.dividend_posted'));
    }
}
