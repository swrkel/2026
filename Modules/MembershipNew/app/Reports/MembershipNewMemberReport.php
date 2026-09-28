<?php

namespace Modules\MembershipNew\app\Reports;

use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewMember;

class MembershipNewMemberReport
{
    public function query(Request $request)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        return MembershipNewMember::forBusiness($businessId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->search . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('member_code', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('mobile', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('nic', 'like', $search);
                });
            })
            ->latest();
    }

    public function expiring(Request $request)
    {
        return $this->query($request)->where('is_active', 1);
    }
}
