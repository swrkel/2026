<?php

namespace Modules\MembershipNew\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MembershipNew\app\Services\MembershipNewBusinessAccessService;

class MembershipNewBusinessAccessController extends Controller
{
    public function edit(MembershipNewBusinessAccessService $service)
    {
        $rule = $service->rule(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId());

        return view('membershipnew::business_access.edit', compact('rule'));
    }

    public function update(Request $request, MembershipNewBusinessAccessService $service)
    {
        $service->updateRule(\Modules\MembershipNew\app\Support\MembershipNewContext::businessId(), $request->all());

        return back()->with('status', 'Business access rules updated.');
    }
}
