<?php

namespace Modules\Membership\Services\Reports;

use Illuminate\Http\Request;
use Modules\Membership\Entities\MembershipMember;

class MemberRegisterReportService
{
    public function query(int $businessId, Request $request)
    {
        $query = MembershipMember::query()
            ->where('business_id', $businessId)
            ->with(['membershipSetting', 'membershipType', 'membershipStatus'])
            ->orderByDesc('id');

        if ($request->filled('region_id')) {
            $query->where('membership_setting_id', $request->input('region_id'));
        }

        if ($request->filled('status_id')) {
            $query->where('membership_status_id', $request->input('status_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_joined', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_joined', '<=', $request->input('date_to'));
        }

        return $query;
    }
}
